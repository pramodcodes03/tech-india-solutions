<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveAccrualLog;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Support\HrSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Automated leave accrual + year-end lapse / carry-forward.
 *
 * Everything is driven by per-leave-type config (accrual_rate, frequency,
 * after-probation gate, min-working-days rule) and the global probation
 * default — no hard-coded rates. Idempotent via leave_accrual_logs so the
 * daily scheduler can run safely.
 */
class LeaveAccrualService
{
    /**
     * Run accrual for all enabled leave types across all employees. Safe to
     * run daily — each (employee, type, period) is credited at most once.
     *
     * @return array{credited:int, skipped:int}
     */
    public function accrueAll(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $credited = 0;
        $skipped = 0;

        $types = LeaveType::withoutGlobalScopes()
            ->where('accrual_enabled', true)
            ->where('status', 'active')
            ->get();

        foreach ($types as $type) {
            $periodKey = $this->currentPeriodKey($type, $asOf);
            if ($periodKey === null) {
                continue; // not an accrual moment for this frequency
            }

            $employees = Employee::withoutGlobalScopes()
                ->where('business_id', $type->business_id)
                ->where('status', 'active')
                ->get();

            foreach ($employees as $emp) {
                if (! $this->isEligible($emp, $type, $asOf)) {
                    $skipped++;
                    continue;
                }

                // Per-employee override: if this employee's balance row carries a
                // custom accrual_rate, use it instead of the leave type's default.
                $override = LeaveBalance::withoutGlobalScopes()
                    ->where('employee_id', $emp->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $asOf->year)
                    ->value('accrual_rate');
                $rate = $override !== null ? (float) $override : (float) $type->accrual_rate;

                $done = $this->credit($emp, $type, $asOf->year, $periodKey, $rate);
                $done ? $credited++ : $skipped++;
            }
        }

        return ['credited' => $credited, 'skipped' => $skipped];
    }

    /**
     * Whether a given accrual period applies right now for this frequency.
     * Returns the period_key string, or null if today isn't an accrual day.
     */
    private function currentPeriodKey(LeaveType $type, Carbon $asOf): ?string
    {
        // Fall back to the global Default Accrual Frequency when the type hasn't
        // set its own.
        $frequency = $type->accrual_frequency ?: HrSettings::get('leave_accrual_frequency', 'monthly');

        // Credit on the configured day of the period (default: 11th). Using >=
        // means a missed run still credits later that month — never before the
        // day — and the once-per-period ledger check keeps it to a single credit.
        // The credit day is per-business, so read it for this type's business
        // (falls back to the global value, then 11).
        $accrualDay = HrSettings::intForBusiness('leave_accrual_day', $type->business_id, 11);
        if ($asOf->day < $accrualDay) {
            return null;
        }

        return match ($frequency) {
            'monthly' => $asOf->format('Y-m'),
            // Credit at the start of each half (Jan & Jul) — on the accrual day.
            'half_yearly' => in_array($asOf->month, [1, 7]) ? $asOf->format('Y').'-H'.($asOf->month === 1 ? '1' : '2') : null,
            // Credit at the start of the year (Jan) — on the accrual day.
            'annual' => $asOf->month === 1 ? $asOf->format('Y') : null,
            default => null,
        };
    }

    private function isEligible(Employee $emp, LeaveType $type, Carbon $asOf): bool
    {
        // Probation gate.
        if ($type->accrue_after_probation) {
            $probEnd = $emp->probation_end_date
                ?? ($emp->joining_date ? Carbon::parse($emp->joining_date)->addDays(HrSettings::int('probation_period_days', 90)) : null);
            if ($probEnd && $asOf->lt($probEnd)) {
                return false;
            }
        }

        // Earned-leave style minimum-working-days rule. EL types fall back to the
        // global "EL Working-Days Required" setting when no per-type value is set.
        $minDays = (int) $type->min_working_days;
        if ($minDays <= 0 && str_starts_with(strtoupper((string) $type->code), 'EL')) {
            $minDays = HrSettings::int('el_working_days_required', 240);
        }
        if ($minDays > 0 && $this->workingDaysSinceJoining($emp) < $minDays) {
            return false;
        }

        return true;
    }

    /**
     * Count actual working days since joining — present / half-day / WFH /
     * overtime days, excluding holidays, week-offs, absences and leave.
     */
    public function workingDaysSinceJoining(Employee $emp): int
    {
        return Attendance::withoutGlobalScopes()
            ->where('employee_id', $emp->id)
            ->whereIn('status', ['present', 'half_day', 'wfh', 'overtime'])
            ->count();
    }

    /**
     * Credit one accrual period, recording the ledger row first to enforce
     * idempotency. Returns true if a credit happened, false if already done.
     */
    private function credit(Employee $emp, LeaveType $type, int $year, string $periodKey, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($emp, $type, $year, $periodKey, $amount) {
            // The lal_unique index is (employee_id, leave_type_id, period_key,
            // event) — business-independent. Strip the tenant scope so this
            // idempotency check matches the constraint exactly; otherwise, when
            // accrual runs across businesses and the active business differs from
            // the type's, the scoped check misses the existing row and the insert
            // hits a duplicate-key error.
            $exists = LeaveAccrualLog::withoutGlobalScopes()
                ->where('employee_id', $emp->id)
                ->where('leave_type_id', $type->id)
                ->where('period_key', $periodKey)
                ->where('event', 'accrual')
                ->exists();
            if ($exists) {
                return false;
            }

            LeaveAccrualLog::create([
                'business_id' => $type->business_id,
                'employee_id' => $emp->id,
                'leave_type_id' => $type->id,
                'year' => $year,
                'period_key' => $periodKey,
                'event' => 'accrual',
                'amount' => $amount,
                'note' => 'Auto accrual',
            ]);

            $balance = LeaveBalance::firstOrNew([
                'employee_id' => $emp->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ]);
            $balance->business_id = $type->business_id;
            $balance->allocated = (float) $balance->allocated + $amount;
            $balance->save();

            return true;
        });
    }

    /**
     * Year-end processing (run on / after Dec 31): lapse unused balances for
     * non-carry-forward types, carry forward eligible earned leave up to the
     * per-type cap, and seed next year's balance rows.
     *
     * @return array{lapsed:int, carried:int}
     */
    public function processYearEnd(?int $year = null): array
    {
        $year = $year ?? (int) now()->year;
        $next = $year + 1;
        $lapsed = 0;
        $carried = 0;

        $balances = LeaveBalance::withoutGlobalScopes()
            ->with('leaveType')
            ->where('year', $year)
            ->get();

        foreach ($balances as $bal) {
            $type = $bal->leaveType;
            if (! $type) {
                continue;
            }

            $available = (float) ($bal->allocated + $bal->carried_forward - $bal->used - $bal->pending);
            $available = max(0, $available);

            $carryAmt = 0.0;
            if ($type->carry_forward && $available > 0) {
                $cap = (float) $type->max_carry_forward;
                // EL types fall back to the global "EL Carry-Forward Cap" setting
                // when no per-type cap is configured.
                if ($cap <= 0 && str_starts_with(strtoupper((string) $type->code), 'EL')) {
                    $cap = HrSettings::float('el_carry_forward_cap', 30);
                }
                $carryAmt = $cap > 0 ? min($available, $cap) : $available;
            }
            $lapseAmt = round($available - $carryAmt, 2);

            DB::transaction(function () use ($bal, $type, $year, $next, $carryAmt, $lapseAmt, &$lapsed, &$carried) {
                if ($lapseAmt > 0) {
                    $this->logEvent($bal, $year, $year.'-LAPSE', 'lapse', $lapseAmt, 'Year-end lapse');
                    $lapsed++;
                }

                // Seed / update next year's balance with the carried amount.
                $nextBal = LeaveBalance::firstOrNew([
                    'employee_id' => $bal->employee_id,
                    'leave_type_id' => $bal->leave_type_id,
                    'year' => $next,
                ]);
                $nextBal->business_id = $bal->business_id;
                $nextBal->carried_forward = (float) $nextBal->carried_forward + $carryAmt;
                $nextBal->save();

                if ($carryAmt > 0) {
                    $this->logEvent($bal, $next, $next.'-CF', 'carry_forward', $carryAmt, 'Year-end carry forward');
                    $carried++;
                }
            });
        }

        return ['lapsed' => $lapsed, 'carried' => $carried];
    }

    private function logEvent(LeaveBalance $bal, int $year, string $periodKey, string $event, float $amount, string $note): void
    {
        LeaveAccrualLog::firstOrCreate(
            [
                'employee_id' => $bal->employee_id,
                'leave_type_id' => $bal->leave_type_id,
                'period_key' => $periodKey,
                'event' => $event,
            ],
            [
                'business_id' => $bal->business_id,
                'year' => $year,
                'amount' => $amount,
                'note' => $note,
            ]
        );
    }
}
