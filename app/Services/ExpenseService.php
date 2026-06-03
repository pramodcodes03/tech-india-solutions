<?php

namespace App\Services;

use App\Models\Expense;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseService
{
    public function generateCode(): string
    {
        $year = date('Y');
        $prefix = 'EXP-'.$year.'-';
        $last = Expense::withTrashed()
            ->where('expense_code', 'like', $prefix.'%')
            ->orderByDesc('expense_code')
            ->first();
        $next = $last ? (int) substr($last->expense_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, ?\Illuminate\Http\UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($data, $attachment) {
            $businessId = app(CurrentBusiness::class)->id();

            $data['business_id'] = $businessId;
            $data['expense_code'] ??= $this->generateCode();
            $data['created_by'] = Auth::guard('admin')->id();

            // Recurring guards: every recurring row carries an explicit frequency.
            // For backwards compat, missing frequency defaults to monthly.
            if (($data['type'] ?? null) === Expense::TYPE_RECURRING) {
                $data['recurrence_frequency'] ??= Expense::FREQ_MONTHLY;

                // due_day_of_month is only meaningful for monthly recurrences.
                // For other frequencies the next-instance generator rolls
                // forward from the previous due_date by the cadence offset.
                if ($data['recurrence_frequency'] === Expense::FREQ_MONTHLY) {
                    $data['due_day_of_month'] = min(28, max(1, (int) ($data['due_day_of_month'] ?? 1)));
                    if (empty($data['due_date'])) {
                        $data['due_date'] = $this->computeDueDate($data['due_day_of_month']);
                    }
                } else {
                    // Non-monthly cadences: user provides the first due_date directly.
                    // Drop due_day_of_month so it doesn't mislead later reads.
                    $data['due_day_of_month'] = null;
                    if (empty($data['due_date'])) {
                        $data['due_date'] = now()->toDateString();
                    }
                }
            }

            // expense_date defaults to today.
            $data['expense_date'] ??= now()->toDateString();

            if ($attachment) {
                $data['attachment'] = $attachment->store(
                    'businesses/'.$businessId.'/expenses',
                    'public'
                );
            }

            return Expense::create($data);
        });
    }

    public function update(Expense $expense, array $data, ?\Illuminate\Http\UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($expense, $data, $attachment) {
            $data['updated_by'] = Auth::guard('admin')->id();

            if (($data['type'] ?? $expense->type) === Expense::TYPE_RECURRING) {
                $freq = $data['recurrence_frequency'] ?? $expense->recurrence_frequency ?? Expense::FREQ_MONTHLY;
                $data['recurrence_frequency'] = $freq;
                if ($freq === Expense::FREQ_MONTHLY && isset($data['due_day_of_month'])) {
                    $data['due_day_of_month'] = min(28, max(1, (int) $data['due_day_of_month']));
                } elseif ($freq !== Expense::FREQ_MONTHLY) {
                    $data['due_day_of_month'] = null;
                }
            }

            if ($attachment) {
                $data['attachment'] = $attachment->store(
                    'businesses/'.$expense->business_id.'/expenses',
                    'public'
                );
            }

            $expense->update($data);

            return $expense->fresh();
        });
    }

    public function markPaid(Expense $expense, array $data): Expense
    {
        $expense->update([
            'status' => Expense::STATUS_PAID,
            'paid_date' => $data['paid_date'] ?? now()->toDateString(),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'paid_by_admin_id' => Auth::guard('admin')->id(),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return $expense->fresh();
    }

    /**
     * Auto-generate the next instance from a recurring template, using the
     * template's `recurrence_frequency` to decide how far ahead to roll the
     * due date. Called by the scheduled command (daily).
     *
     * Idempotent: if an instance already exists for the computed next-due
     * date, returns null. The "stay one cycle ahead" lookahead also scales
     * with frequency so we don't spawn yearly rows 11 months early.
     */
    public function generateNextRecurring(Expense $template): ?Expense
    {
        if (! $template->isRecurring() || $template->recurring_template_id) {
            // Skip: only original templates spawn instances.
            return null;
        }

        // Find the most recent instance for this template (incl. the template itself).
        $latest = Expense::withoutGlobalScopes()
            ->where(function ($q) use ($template) {
                $q->where('id', $template->id)
                  ->orWhere('recurring_template_id', $template->id);
            })
            ->orderByDesc('due_date')
            ->first();

        if (! $latest || ! $latest->due_date) {
            return null;
        }

        $frequency = $template->recurrence_frequency ?? Expense::FREQ_MONTHLY;
        $nextDue   = $this->advanceDueDate($latest->due_date->copy(), $frequency);

        // For monthly, honour the template's preferred day-of-month (capped
        // at 28 to avoid Feb edge cases). Other frequencies use whatever day
        // the rolling math produced.
        $day = null;
        if ($frequency === Expense::FREQ_MONTHLY) {
            $day = min(28, max(1, $template->due_day_of_month ?? $nextDue->day));
            $nextDue->day($day);
        }

        // Stay roughly one cycle ahead. Lookahead window scales with cadence
        // so yearly templates aren't generated 11 months early, and weekly
        // templates aren't blocked because the next due is 7 days away.
        $lookaheadDays = match ($frequency) {
            Expense::FREQ_WEEKLY      => 10,    // ~1.5 weeks ahead
            Expense::FREQ_MONTHLY     => 35,    // ~5 weeks ahead (existing)
            Expense::FREQ_QUARTERLY   => 100,   // ~3.3 months
            Expense::FREQ_HALF_YEARLY => 200,   // ~6.5 months
            Expense::FREQ_YEARLY      => 380,   // ~12.5 months
            default                   => 35,
        };

        if ($nextDue->isAfter(now()->addDays($lookaheadDays))) {
            return null;
        }

        // Don't double-create.
        $exists = Expense::withoutGlobalScopes()
            ->where('recurring_template_id', $template->id)
            ->whereDate('due_date', $nextDue->toDateString())
            ->exists();

        if ($exists) {
            return null;
        }

        return Expense::create([
            'business_id' => $template->business_id,
            'expense_code' => $this->generateCodeForBusiness($template->business_id),
            'expense_category_id' => $template->expense_category_id,
            'expense_subcategory_id' => $template->expense_subcategory_id,
            'type' => Expense::TYPE_RECURRING,
            'title' => $template->title,
            'description' => $template->description,
            'amount' => $template->amount,
            'expense_date' => $nextDue->copy()->startOfMonth()->toDateString(),
            'due_date' => $nextDue->toDateString(),
            'due_day_of_month' => $day,
            'recurrence_frequency' => $frequency,
            'recurring_template_id' => $template->id,
            'status' => Expense::STATUS_UNPAID,
            'created_by' => $template->created_by,
        ]);
    }

    /**
     * Roll a due-date forward by one cycle of the given frequency.
     * `addMonthNoOverflow()` keeps Feb edge cases sane for month-based steps.
     */
    protected function advanceDueDate(Carbon $from, string $frequency): Carbon
    {
        return match ($frequency) {
            Expense::FREQ_WEEKLY      => $from->addWeek(),
            Expense::FREQ_QUARTERLY   => $from->addMonthsNoOverflow(3),
            Expense::FREQ_HALF_YEARLY => $from->addMonthsNoOverflow(6),
            Expense::FREQ_YEARLY      => $from->addYearNoOverflow(),
            default                   => $from->addMonthNoOverflow(),
        };
    }

    protected function generateCodeForBusiness(int $businessId): string
    {
        $year = date('Y');
        $prefix = 'EXP-'.$year.'-';
        $last = Expense::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('expense_code', 'like', $prefix.'%')
            ->orderByDesc('expense_code')
            ->first();
        $next = $last ? (int) substr($last->expense_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    protected function computeDueDate(int $dayOfMonth): string
    {
        $today = Carbon::now();
        $thisMonthDue = $today->copy()->day(min(28, $dayOfMonth));

        // If this month's due date is already past, set due date to next month.
        return $thisMonthDue->isBefore($today->startOfDay())
            ? $thisMonthDue->addMonthNoOverflow()->toDateString()
            : $thisMonthDue->toDateString();
    }
}
