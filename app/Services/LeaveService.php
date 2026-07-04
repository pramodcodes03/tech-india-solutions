<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function generateCode(): string
    {
        $prefix = 'LR-'.date('Ym').'-';
        $last = LeaveRequest::where('request_code', 'like', $prefix.'%')
            ->orderByDesc('request_code')->first();
        $next = $last ? (int) substr($last->request_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Number of *leave* days in a range. Week-offs and public holidays are NOT
     * counted as leave — only actual working days are. Pass $businessId so the
     * correct week-off pattern / holiday calendar is used; without it the raw
     * calendar-day count is returned (legacy behaviour).
     */
    public function computeDays(string $from, string $to, string $dayPortion = 'full', ?int $businessId = null): float
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        if ($end->lt($start)) {
            return 0;
        }

        $attendance = app(\App\Services\AttendanceService::class);
        $isNonWorking = fn (Carbon $d): bool => $businessId
            && ($attendance->isBusinessWeekOff($d->toDateString(), $businessId)
                || $attendance->isPublicHoliday($d->toDateString(), $businessId));

        // Single date: a half-day portion is 0.5, full is 1 — but a week-off /
        // holiday on that date means no leave is consumed at all.
        if ($start->eq($end)) {
            if ($isNonWorking($start)) {
                return 0;
            }

            return $dayPortion !== 'full' ? 0.5 : 1.0;
        }

        // Multi-day: count working days only, skipping week-offs and holidays.
        $days = 0.0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! $isNonWorking($d)) {
                $days += 1;
            }
        }

        return (float) $days;
    }

    /**
     * Available balance for an employee + leave type in the given year.
     * available = allocated + carried_forward - used - pending
     */
    public function availableBalance(int $employeeId, int $leaveTypeId, int $year): float
    {
        $b = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (! $b) {
            return 0;
        }

        return (float) ($b->allocated + $b->carried_forward - $b->used - $b->pending);
    }

    public function submit(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $employee = \App\Models\Employee::find($data['employee_id']);

            // Half-day portions only apply to a single-date request; a multi-day
            // range is always full days.
            if (($data['from_date'] ?? null) !== ($data['to_date'] ?? null)) {
                $data['day_portion'] = 'full';
            }

            $data['request_code'] = $this->generateCode();
            // Exclude week-offs and public holidays from the leave-day count.
            $data['days'] = $this->computeDays(
                $data['from_date'],
                $data['to_date'],
                $data['day_portion'] ?? 'full',
                $employee?->business_id,
            );
            $data['status'] = 'pending';
            $data['paid_days'] = 0;
            $data['unpaid_days'] = 0;

            // All selected dates fell on week-offs / holidays — nothing to apply.
            if ($data['days'] <= 0) {
                throw new \RuntimeException('The selected dates are all week-offs or holidays — there are no working days to apply leave for.');
            }

            $leaveType = LeaveType::findOrFail($data['leave_type_id']);

            // Probation gate: paid leave cannot be taken while serving probation.
            // Unpaid (Leave Without Pay) types are still allowed.
            if ($leaveType->is_paid) {
                if ($employee && $employee->isOnProbation()) {
                    $until = $employee->probation_end_date
                        ? ' (until '.$employee->probation_end_date->format('d M Y').')'
                        : '';
                    throw new \RuntimeException(
                        "Paid leave cannot be applied during your probation period{$until}. "
                        .'You may apply for Leave Without Pay, or apply once your probation is completed.'
                    );
                }
            }

            $request = LeaveRequest::create($data);

            // Hold as pending only up to the available balance (for paid types).
            // Any excess will be treated as LWP at approval time — employees can
            // still submit over-balance requests; HR decides paid/unpaid split.
            if ($leaveType->is_paid) {
                $year = Carbon::parse($request->from_date)->year;
                $available = $this->availableBalance($request->employee_id, $request->leave_type_id, $year);
                $holdDays = min((float) $request->days, $available);
                if ($holdDays > 0) {
                    $this->adjustBalance($request->employee_id, $request->leave_type_id, $holdDays, 'pending_add', $request->from_date);
                }
            }

            return $request;
        });
    }

    /**
     * Approve a leave request with an optional paid/unpaid split.
     * If $paidDays is null, the full request is approved as paid against the chosen type.
     * Any unpaid portion is recorded as unpaid_days and shows up as LOP on payroll.
     */
    public function approve(LeaveRequest $request, ?int $approverId, ?string $remarks = null, ?float $paidDays = null, ?int $approverEmployeeId = null): LeaveRequest
    {
        return DB::transaction(function () use ($request, $approverId, $remarks, $paidDays, $approverEmployeeId) {
            if ($request->status !== 'pending') {
                return $request;
            }

            $total = (float) $request->days;
            $paid = $paidDays ?? $total;
            $paid = max(0, min($paid, $total));
            $unpaid = round($total - $paid, 1);

            // If it's already an LWP type, everything is unpaid.
            if (! $request->leaveType->is_paid) {
                $paid = 0;
                $unpaid = $total;
            }

            // Release any previously-held pending for this paid type, then book the real paid amount.
            if ($request->leaveType->is_paid) {
                $this->adjustBalance($request->employee_id, $request->leave_type_id, $total, 'pending_release', $request->from_date);
                if ($paid > 0) {
                    $this->adjustBalance($request->employee_id, $request->leave_type_id, $paid, 'used_add', $request->from_date);
                }
            }

            $request->update([
                'status' => 'approved',
                'paid_days' => $paid,
                'unpaid_days' => $unpaid,
                'approver_id' => $approverId,
                'approver_employee_id' => $approverEmployeeId,
                'actioned_at' => now(),
                'approver_remarks' => $remarks,
            ]);

            // Mark attendance as on_leave for the whole period
            $from = Carbon::parse($request->from_date);
            $to = Carbon::parse($request->to_date);
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                \App\Models\Attendance::updateOrCreate(
                    ['employee_id' => $request->employee_id, 'date' => $d->toDateString()],
                    ['status' => 'on_leave', 'source' => 'leave_approval']
                );
            }

            return $request->refresh();
        });
    }

    public function reject(LeaveRequest $request, ?int $approverId, ?string $remarks = null, ?int $approverEmployeeId = null): LeaveRequest
    {
        return DB::transaction(function () use ($request, $approverId, $remarks, $approverEmployeeId) {
            if ($request->status !== 'pending') {
                return $request;
            }

            // Release pending hold on paid types
            if ($request->leaveType->is_paid) {
                $this->adjustBalance($request->employee_id, $request->leave_type_id, $request->days, 'pending_release', $request->from_date);
            }

            $request->update([
                'status' => 'rejected',
                'approver_id' => $approverId,
                'approver_employee_id' => $approverEmployeeId,
                'actioned_at' => now(),
                'approver_remarks' => $remarks,
            ]);

            return $request->refresh();
        });
    }

    public function cancel(LeaveRequest $request): LeaveRequest
    {
        return DB::transaction(function () use ($request) {
            if (! in_array($request->status, ['pending', 'approved'])) {
                return $request;
            }

            $wasApproved = $request->status === 'approved';

            if ($request->leaveType->is_paid) {
                if ($wasApproved) {
                    // Return the paid portion that was consumed
                    if ($request->paid_days > 0) {
                        $this->adjustBalance($request->employee_id, $request->leave_type_id, (float) $request->paid_days, 'used_release', $request->from_date);
                    }
                } else {
                    // Pending → release the hold
                    $this->adjustBalance($request->employee_id, $request->leave_type_id, (float) $request->days, 'pending_release', $request->from_date);
                }
            }

            $request->update([
                'status' => 'cancelled',
                'actioned_at' => now(),
            ]);

            return $request->refresh();
        });
    }

    /**
     * Adjust balance for a specific (employee, type, year) bucket.
     */
    private function adjustBalance(int $employeeId, int $leaveTypeId, float $days, string $op, ?string $anchorDate = null): void
    {
        if ($days <= 0) {
            return;
        }
        $year = Carbon::parse($anchorDate ?: 'now')->year;
        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['allocated' => 0]
        );

        match ($op) {
            'pending_add' => $balance->increment('pending', $days),
            'pending_release' => $balance->decrement('pending', min($days, (float) $balance->pending)),
            'used_add' => $balance->increment('used', $days),
            'used_release' => $balance->decrement('used', min($days, (float) $balance->used)),
            default => null,
        };
    }

    /**
     * HR: set/override an employee's leave balance for a (type, year).
     * Pass only the fields you want to change.
     */
    public function setBalance(int $employeeId, int $leaveTypeId, int $year, array $fields): LeaveBalance
    {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $year, $fields) {
            $balance = LeaveBalance::firstOrCreate(
                ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
                ['allocated' => 0, 'used' => 0, 'pending' => 0, 'carried_forward' => 0]
            );
            $update = array_intersect_key($fields, array_flip(['allocated', 'used', 'pending', 'carried_forward', 'accrual_rate']));
            if (! empty($update)) {
                $balance->update($update);
            }

            return $balance->refresh();
        });
    }
}
