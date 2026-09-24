<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Support\HrSettings;

/**
 * The Leave Balance Gate: stop a leave request at submission when the employee
 * does not have the balance to cover it.
 *
 * Before this gate, an employee could apply for more days than they held and HR
 * decided the paid / unpaid split at approval. With the gate on, the request
 * never reaches HR — the employee is told what they have and what they asked
 * for, up front.
 *
 * Two switches, both per business, both set from HR → Leave Settings:
 *
 *   leave_balance_gate_enabled   Master switch. Off = the previous behaviour,
 *                                over-balance requests are allowed through.
 *
 *   leave_lwp_exception_enabled  What happens to genuine Leave Without Pay when
 *                                the balance is exhausted.
 *                                On  (default) — only PAID types are blocked;
 *                                    an unpaid type stays open, so a medical
 *                                    emergency after balances run out can still
 *                                    be applied for.
 *                                Off — the block is absolute: an employee with
 *                                    no paid balance left cannot apply for
 *                                    anything, LWP included.
 *
 * Nothing here touches HR's own approval screen — HR can still split a request
 * paid/unpaid. The gate is about what an employee may submit.
 */
class LeaveBalanceGateService
{
    public function isEnabled(?int $businessId): bool
    {
        return HrSettings::boolForBusiness('leave_balance_gate_enabled', $businessId, true);
    }

    public function lwpExceptionAllowed(?int $businessId): bool
    {
        return HrSettings::boolForBusiness('leave_lwp_exception_enabled', $businessId, true);
    }

    /**
     * Can this employee submit this request?
     *
     * @param  float  $days  working days the request consumes (already excludes
     *                       week-offs and holidays — see LeaveService::computeDays)
     * @return array{
     *   allowed: bool, gate_enabled: bool, lwp_exception: bool, is_paid: bool,
     *   available: float, requested: float, shortfall: float, reason: ?string
     * }
     */
    public function evaluate(Employee $employee, LeaveType $type, float $days, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');
        $gateOn = $this->isEnabled($employee->business_id);
        $lwpOk = $this->lwpExceptionAllowed($employee->business_id);
        $isPaid = (bool) $type->is_paid;

        $available = $isPaid
            ? $this->availableFor($employee->id, $type->id, $year)
            : 0.0;

        $base = [
            'gate_enabled' => $gateOn,
            'lwp_exception' => $lwpOk,
            'is_paid' => $isPaid,
            'available' => $available,
            'requested' => $days,
            'shortfall' => 0.0,
            'allowed' => true,
            'reason' => null,
        ];

        if (! $gateOn) {
            return $base;
        }

        // ── Unpaid (LWP) type ────────────────────────────────────────────
        // With the exception on, an unpaid type is never gated — that is the
        // whole point of the exception. With it off, the block is absolute and
        // applies once every paid bucket is exhausted.
        if (! $isPaid) {
            if ($lwpOk || $this->hasAnyPaidBalance($employee, $year)) {
                return $base;
            }

            return array_merge($base, [
                'allowed' => false,
                'reason' => 'You have no paid leave balance remaining, and Leave Without Pay '
                    .'is not permitted as an exception under the current policy. '
                    .'Please contact HR.',
            ]);
        }

        // ── Paid type ────────────────────────────────────────────────────
        $shortfall = round($days - $available, 2);
        if ($shortfall <= 0) {
            return $base;
        }

        $tail = $lwpOk
            ? ' If you still need the time off, apply under a Leave Without Pay type instead.'
            : ' Please contact HR.';

        // The client asked for this exact sentence when the bucket is empty;
        // the type name and the way forward follow it rather than replacing it.
        $reason = $available <= 0
            ? sprintf(
                'You do not have any leave balance. No %s remains (0 days available), so this request cannot be submitted.%s',
                $type->name,
                $tail,
            )
            : sprintf(
                'You have %s day(s) of %s available but applied for %s. This request cannot be submitted.%s',
                $this->fmt($available),
                $type->name,
                $this->fmt($days),
                $tail,
            );

        return array_merge($base, [
            'allowed' => false,
            'shortfall' => $shortfall,
            'reason' => $reason,
        ]);
    }

    /**
     * available = allocated + carried_forward − used − pending, for one bucket.
     *
     * Mirrors LeaveService::availableBalance(); kept here so the gate can be
     * evaluated for a leave type the employee has no balance row for at all
     * (which reads as 0, not as an error).
     */
    public function availableFor(int $employeeId, int $leaveTypeId, int $year): float
    {
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            return 0.0;
        }

        return round(
            (float) $balance->allocated + (float) $balance->carried_forward
            - (float) $balance->used - (float) $balance->pending,
            2,
        );
    }

    /** Does the employee have anything left in ANY paid leave type this year? */
    public function hasAnyPaidBalance(Employee $employee, int $year): bool
    {
        $paidTypeIds = LeaveType::where('status', 'active')
            ->where('is_paid', true)
            ->pluck('id');

        if ($paidTypeIds->isEmpty()) {
            return false;
        }

        return LeaveBalance::where('employee_id', $employee->id)
            ->whereIn('leave_type_id', $paidTypeIds)
            ->where('year', $year)
            ->get()
            ->contains(fn (LeaveBalance $b) => $b->available > 0);
    }

    /** 2.0 → "2", 2.5 → "2.5" — leave counts read badly with trailing zeros. */
    private function fmt(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1, '.', ''), '0'), '.') ?: '0';
    }
}
