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

            // Recurring guards: cap due_day_of_month at 28 to avoid Feb edge case.
            if (($data['type'] ?? null) === Expense::TYPE_RECURRING) {
                $data['due_day_of_month'] = min(28, max(1, (int) ($data['due_day_of_month'] ?? 1)));

                // For the first instance: compute due_date from today's month + due_day_of_month.
                if (empty($data['due_date'])) {
                    $data['due_date'] = $this->computeDueDate($data['due_day_of_month']);
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

            if (($data['type'] ?? $expense->type) === Expense::TYPE_RECURRING && isset($data['due_day_of_month'])) {
                $data['due_day_of_month'] = min(28, max(1, (int) $data['due_day_of_month']));
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
     * Auto-generate the next month's instance from a recurring template.
     * Called by the scheduled command.
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

        $nextDue = $latest->due_date->copy()->addMonthNoOverflow();
        // Adjust to template's preferred day of month (capped at 28).
        $day = min(28, max(1, $template->due_day_of_month ?? $nextDue->day));
        $nextDue->day($day);

        // Don't generate beyond today + ~35 days (we want to stay one cycle ahead).
        if ($nextDue->isAfter(now()->addDays(35))) {
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
            'recurring_template_id' => $template->id,
            'status' => Expense::STATUS_UNPAID,
            'created_by' => $template->created_by,
        ]);
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
