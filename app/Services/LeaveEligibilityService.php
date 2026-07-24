<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Support\HrSettings;
use Carbon\Carbon;

/**
 * Single source of truth for the "working-days since joining" leave gate.
 *
 * Two buckets, mirroring the client's policy sheet:
 *   - EL      → the Earned-Leave threshold (higher, e.g. 330 days)
 *   - CL & SL → the shared Casual/Sick threshold (lower, e.g. 90 days)
 * A leave type whose code starts with "EL" is the EL bucket; everything else
 * (CL, SL, LWP, …) is the CL & SL bucket.
 *
 * Each bucket's threshold is resolved most-specific-first:
 *   Employee override → Department override → Business (global) default.
 * NULL / blank at a level means "inherit the next level down".
 *
 * "Working days" here = calendar days since joining_date (today - DOJ). No
 * attendance lookup — deliberate, so the rule is predictable and identical in
 * the web request and the nightly scheduler (which has no session / business
 * context). Everything reads plain columns + HrSettings, so it is safe to call
 * from a cron job.
 */
class LeaveEligibilityService
{
    public const BUCKET_EL = 'el';
    public const BUCKET_CL_SL = 'cl_sl';

    /**
     * Which bucket a leave type falls into: the EL (Earned Leave) bucket, or the
     * shared CL & SL bucket for everything else.
     *
     * Leave-type codes in this system are often arbitrary (e.g. "0444", "4335"),
     * so we can't rely on the code alone. We treat a type as Earned Leave when
     * its code OR its name signals it — code starting with "EL", or the name
     * containing "earned" or a standalone "EL" token. Casual/Sick/LWP/anything
     * else falls in the CL & SL bucket.
     */
    public function bucketFor(LeaveType $type): string
    {
        $code = strtoupper(trim((string) $type->code));
        $name = strtoupper((string) $type->name);

        $isEl = str_starts_with($code, 'EL')
            || str_contains($name, 'EARNED')
            || preg_match('/\bEL\b/', $name) === 1;

        return $isEl ? self::BUCKET_EL : self::BUCKET_CL_SL;
    }

    /**
     * The required working-days threshold for this employee + leave type,
     * resolved employee → department → business-global.
     *
     * @return int  0 means "no gate" (immediately eligible).
     */
    public function requiredDays(Employee $employee, LeaveType $type): int
    {
        $bucket = $this->bucketFor($type);

        // Column that carries this bucket's override on employee / department.
        $col = $bucket === self::BUCKET_EL ? 'el_working_days_required' : 'cl_sl_working_days';

        // 1) Per-employee override.
        if ($employee->{$col} !== null) {
            return (int) $employee->{$col};
        }

        // 2) Per-department override (only when the employee has a department).
        if ($employee->department_id) {
            $dept = $employee->relationLoaded('department')
                ? $employee->department
                : $employee->department()->withoutGlobalScopes()->first();
            if ($dept && $dept->{$col} !== null) {
                return (int) $dept->{$col};
            }
        }

        // 3) Business-global default from HR settings.
        $settingKey = $bucket === self::BUCKET_EL ? 'el_working_days_required' : 'cl_sl_working_days_required';

        return HrSettings::intForBusiness($settingKey, $employee->business_id);
    }

    /**
     * Calendar days the employee has completed since joining, as of $asOf
     * (default now). Never negative; 0 if no joining date is set.
     */
    public function daysCompleted(Employee $employee, ?Carbon $asOf = null): int
    {
        if (! $employee->joining_date) {
            return 0;
        }

        $asOf = $asOf ?? now();
        $doj = Carbon::parse($employee->joining_date)->startOfDay();

        return $doj->gt($asOf) ? 0 : $doj->diffInDays($asOf->copy()->startOfDay());
    }

    /**
     * Full eligibility picture for one employee + leave type. Used by the
     * application gate, the accrual cron, and the employee-facing screen.
     *
     * @return array{
     *   eligible: bool, bucket: string, required: int, completed: int,
     *   remaining: int, unlock_date: ?string, reason: ?string
     * }
     */
    public function evaluate(Employee $employee, LeaveType $type, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $bucket = $this->bucketFor($type);
        $required = $this->requiredDays($employee, $type);
        $completed = $this->daysCompleted($employee, $asOf);

        // No gate configured → always eligible.
        if ($required <= 0) {
            return [
                'eligible' => true, 'bucket' => $bucket, 'required' => 0,
                'completed' => $completed, 'remaining' => 0,
                'unlock_date' => null, 'reason' => null,
            ];
        }

        $remaining = max(0, $required - $completed);
        $eligible = $remaining <= 0;

        $unlockDate = null;
        $reason = null;
        if (! $eligible) {
            $unlockDate = $employee->joining_date
                ? Carbon::parse($employee->joining_date)->startOfDay()->addDays($required)->toDateString()
                : null;

            $label = $bucket === self::BUCKET_EL ? 'Earned Leave' : 'Casual / Sick Leave';
            $tail = $unlockDate
                ? ' (available from '.Carbon::parse($unlockDate)->format('d M Y').').'
                : '.';
            $reason = "{$label} unlocks after {$required} working days from joining. "
                ."You have completed {$completed} — {$remaining} more to go{$tail}";
        }

        return [
            'eligible' => $eligible, 'bucket' => $bucket, 'required' => $required,
            'completed' => $completed, 'remaining' => $remaining,
            'unlock_date' => $unlockDate, 'reason' => $reason,
        ];
    }

    /**
     * Convenience boolean for the accrual cron.
     */
    public function isEligible(Employee $employee, LeaveType $type, ?Carbon $asOf = null): bool
    {
        return $this->evaluate($employee, $type, $asOf)['eligible'];
    }
}
