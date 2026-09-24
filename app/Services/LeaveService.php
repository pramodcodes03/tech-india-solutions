<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
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

        $attendance = app(AttendanceService::class);
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

    /**
     * Balance available to fund THIS request while it is being reviewed.
     *
     * When a request is submitted we park its days in `pending` (capped at the
     * balance then available). Plain availableBalance() therefore counts the
     * request against itself — an employee with exactly 0.5 CL left who applies
     * for 0.5 CL shows 0.0 available, so the approver sees "Max paid = 0" and
     * the leave silently falls to LOP. Add the request's own hold back so the
     * approver sees what this request can actually draw.
     */
    public function availableForRequest(LeaveRequest $request): float
    {
        $request->loadMissing('leaveType');

        // Summed across every paid type the request draws on, so a Combined
        // Leave request shows the approver the total it can actually be paid
        // from rather than just the primary type's balance.
        $year = Carbon::parse($request->from_date)->year;
        $available = 0.0;

        foreach ($this->fundingLines($request) as $line) {
            if (! $line['type']->is_paid) {
                continue;
            }

            $b = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $line['type']->id)
                ->where('year', $year)
                ->first();

            if (! $b) {
                continue;
            }

            // Only a still-pending request is holding anything. The hold was
            // capped at the balance available at submission time, so
            // reconstruct it the same way approve() releases it.
            $ownHold = $request->status === 'pending'
                ? min($line['days'], (float) $b->pending)
                : 0.0;

            $available += (float) ($b->allocated + $b->carried_forward - $b->used - $b->pending + $ownHold);
        }

        return round($available, 1);
    }

    /**
     * Validate and normalise the Combined Leave splits posted with a request.
     *
     * Returns null only when no usable rows were posted. A single surviving row
     * is still returned — it carries the leave type the request must be filed
     * under — and submit() decides that one type is not a combination.
     *
     * Ordered largest-first so callers can take element 0 as the primary type.
     *
     * @param  array<int,array{leave_type_id:int|string, days:float|string}>|null  $splits
     * @return array<int,array{leave_type_id:int, days:float}>|null
     *
     * @throws \RuntimeException when the split does not add up to the request
     */
    private function normaliseSplits(?array $splits, float $totalDays): ?array
    {
        if (! $splits) {
            return null;
        }

        $clean = [];
        foreach ($splits as $split) {
            $typeId = (int) ($split['leave_type_id'] ?? 0);
            $days = round((float) ($split['days'] ?? 0), 1);

            if ($typeId <= 0 || $days <= 0) {
                continue;   // a blank row on the form, not an error
            }

            // Two rows on the same type is a data error, not a combination.
            if (isset($clean[$typeId])) {
                throw new \RuntimeException('Each leave type can only appear once in a combined request.');
            }

            $clean[$typeId] = $days;
        }

        if (empty($clean)) {
            return null;
        }

        // Halves are the whole point of the feature, so compare on one decimal
        // rather than exact float equality.
        $sum = round(array_sum($clean), 1);
        if (abs($sum - round($totalDays, 1)) > 0.001) {
            throw new \RuntimeException(sprintf(
                'The combined days must add up to the %s day(s) being applied for — they currently total %s.',
                rtrim(rtrim(number_format($totalDays, 1, '.', ''), '0'), '.'),
                rtrim(rtrim(number_format($sum, 1, '.', ''), '0'), '.'),
            ));
        }

        arsort($clean);

        return array_map(
            fn ($typeId, $days) => ['leave_type_id' => (int) $typeId, 'days' => (float) $days],
            array_keys($clean),
            array_values($clean),
        );
    }

    /**
     * How a request draws on leave types: one line per contributing type for a
     * combined request, a single line for an ordinary one.
     *
     * Everything that moves balances goes through here, so the combined and
     * single-type paths can never diverge.
     *
     * @return array<int,array{type: LeaveType, days: float, split: ?LeaveRequestSplit}>
     */
    private function fundingLines(LeaveRequest $request): array
    {
        if ($request->is_combined) {
            $request->loadMissing('splits.leaveType');

            return $request->splits
                ->filter(fn ($split) => $split->leaveType !== null)
                ->map(fn ($split) => [
                    'type' => $split->leaveType,
                    'days' => (float) $split->days,
                    'split' => $split,
                ])->values()->all();
        }

        $type = $request->leaveType
            ?? LeaveType::withoutGlobalScopes()->find($request->leave_type_id);

        return $type
            ? [['type' => $type, 'days' => (float) $request->days, 'split' => null]]
            : [];
    }

    /**
     * A live leave request for this employee that already covers any part of
     * the given range, or null.
     *
     * "Live" means pending or approved — a rejected or cancelled request has
     * released its dates and must not block a fresh application.
     *
     * Half-day portions are deliberately NOT treated as separate slots: an
     * employee needing first-half Casual and second-half Sick files that as one
     * Combination Leave request, not two overlapping ones.
     */
    /**
     * Day-by-day account of a request: which dates it consumed and which were
     * skipped, and why.
     *
     * The stored `days` is a snapshot taken when the request was made. If the
     * week-off pattern or holiday calendar changes afterwards, recomputing the
     * range today gives a different answer than the number on the record — so
     * this reports both and says when they disagree.
     *
     * @return array{dates: array<int, array{date: \Illuminate\Support\Carbon, counted: bool, reason: ?string}>,
     *               counted: float, stored: float, recomputed: float, drifted: bool}
     */
    public function dayBreakdown(LeaveRequest $request): array
    {
        $attendance = app(AttendanceService::class);
        $businessId = $request->employee?->business_id;

        $dates = [];
        $counted = 0.0;

        for ($d = $request->from_date->copy(); $d->lte($request->to_date); $d->addDay()) {
            $reason = null;

            if ($businessId && $attendance->isBusinessWeekOff($d->toDateString(), $businessId)) {
                $reason = 'Week-off';
            } elseif ($businessId && $attendance->isPublicHoliday($d->toDateString(), $businessId)) {
                $reason = 'Holiday';
            }

            if ($reason === null) {
                $counted++;
            }

            $dates[] = ['date' => $d->copy(), 'counted' => $reason === null, 'reason' => $reason];
        }

        // A single date carries the half-day portion.
        if ($request->from_date->isSameDay($request->to_date) && $request->day_portion !== 'full') {
            $counted = min($counted, 0.5);
        }

        $stored = (float) $request->days;

        return [
            'dates' => $dates,
            'counted' => $counted,
            'stored' => $stored,
            'recomputed' => $counted,
            // The calendar has been edited since this request was filed.
            'drifted' => abs($counted - $stored) > 0.01,
        ];
    }

    /**
     * The request already on file that would collide with this one, if any.
     *
     * A date is not indivisible: one working day is two halves, and an employee
     * may legitimately fund each from a different leave type — a first-half
     * Casual and a second-half Earned are two requests on one date that do not
     * overlap at all. So the opposite half of the same single date is excluded
     * from the clash, while every other pairing (either side a full day, or
     * both claiming the same half) still collides.
     */
    public function overlappingRequest(
        int $employeeId,
        string $from,
        string $to,
        ?int $ignoreRequestId = null,
        array $statuses = ['pending', 'approved'],
        string $dayPortion = 'full',
    ): ?LeaveRequest {
        $complement = match ($dayPortion) {
            'first_half' => 'second_half',
            'second_half' => 'first_half',
            default => null,
        };

        // Only a single-date request has a half to give away; a multi-day range
        // is always full days (submit() forces that before we get here).
        $singleDate = $complement !== null
            && Carbon::parse($from)->isSameDay(Carbon::parse($to));

        return LeaveRequest::withoutGlobalScopes()
            ->where('employee_id', $employeeId)
            ->whereIn('status', $statuses)
            ->when($ignoreRequestId, fn ($q, $id) => $q->whereKeyNot($id))
            // Two ranges overlap when each starts on or before the other ends.
            ->whereDate('from_date', '<=', $to)
            ->whereDate('to_date', '>=', $from)
            ->when($singleDate, fn ($q) => $q->whereNot(fn ($q) => $q
                ->where('day_portion', $complement)
                ->whereDate('from_date', $from)
                ->whereDate('to_date', $from)))
            ->orderBy('from_date')
            ->first();
    }

    /** The message an employee sees when they apply for a date twice. */
    public function duplicateMessage(LeaveRequest $clash): string
    {
        $range = $clash->from_date->isSameDay($clash->to_date)
            ? $clash->from_date->format('d M Y')
            : $clash->from_date->format('d M Y').' to '.$clash->to_date->format('d M Y');

        // Naming the half matters now that the other half is applyable: without
        // it, "already applied for this date" reads as though the whole day is
        // spoken for when only one half is.
        // The full-day sentence is the wording the client specified, so it stays
        // exactly as it was. Only a half-day clash gains the qualifier, because
        // there "this date" would now be wrong — the other half is still free.
        $subject = match ($clash->day_portion) {
            'first_half' => 'the first half of this date',
            'second_half' => 'the second half of this date',
            default => 'this date',
        };

        return "You have already applied for leave for {$subject}. "
            ."Request {$clash->request_code} ({$range}) is already "
            .($clash->status === 'approved' ? 'approved' : 'awaiting approval')
            .'. Cancel it first if you need to change it.';
    }

    public function submit(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::find($data['employee_id']);

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

            // One date, one request. Checked inside the transaction so two
            // submissions racing each other cannot both get through.
            $clash = $this->overlappingRequest(
                (int) $data['employee_id'],
                $data['from_date'],
                $data['to_date'],
                null,
                ['pending', 'approved'],
                $data['day_portion'] ?? 'full',
            );

            if ($clash) {
                throw new \RuntimeException($this->duplicateMessage($clash));
            }

            // Combined Leave: the request is funded from several types
            // (0.5 Casual + 0.5 Sick = one day). Normalise the splits first so
            // leave_type_id can be set to the largest contributor before the
            // single-type gates below run against it.
            $splits = $this->normaliseSplits($data['splits'] ?? null, (float) $data['days']);
            unset($data['splits']);

            if ($splits) {
                // The largest contributor becomes leave_type_id, so every screen
                // and report that reads a single type still works.
                $data['leave_type_id'] = $splits[0]['leave_type_id'];
                $data['is_combined'] = count($splits) > 1;

                // One type is not a combination — file it as an ordinary request
                // with no split rows at all.
                if (count($splits) === 1) {
                    $splits = null;
                }
            }

            $leaveType = LeaveType::findOrFail($data['leave_type_id']);

            // Every contributing type is gated, not just the primary one — a
            // combined request must not slip an ineligible or unfunded type in
            // behind an eligible one.
            $gatedTypes = $splits
                ? collect($splits)->map(fn ($s) => [
                    'type' => LeaveType::findOrFail($s['leave_type_id']),
                    'days' => $s['days'],
                ])->all()
                : [['type' => $leaveType, 'days' => (float) $data['days']]];

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

                // Working-days gate (two buckets: CL & SL vs EL), resolved
                // employee → department → business default. An employee cannot
                // apply for a paid leave type until they have completed the
                // required calendar days since joining. LWP is unaffected.
                if ($employee) {
                    foreach ($gatedTypes as $gated) {
                        if (! $gated['type']->is_paid) {
                            continue;
                        }
                        $eligibility = app(LeaveEligibilityService::class)->evaluate($employee, $gated['type']);
                        if (! $eligibility['eligible']) {
                            throw new \RuntimeException($eligibility['reason']
                                ?? 'This leave type is not yet available based on your working days since joining.');
                        }
                    }
                }
            }

            // Leave Balance Gate: when enabled, a request the employee cannot
            // fund is refused here rather than being passed to HR to split
            // paid/unpaid. Runs for unpaid types too, because the "absolute
            // block" policy option also closes LWP once balances are exhausted.
            if ($employee) {
                $year = Carbon::parse($data['from_date'])->year;
                foreach ($gatedTypes as $gated) {
                    $gate = app(LeaveBalanceGateService::class)->evaluate(
                        $employee,
                        $gated['type'],
                        $gated['days'],
                        $year,
                    );

                    if (! $gate['allowed']) {
                        throw new \RuntimeException($gate['reason']
                            ?? 'You do not have enough leave balance to submit this request.');
                    }
                }
            }

            $request = LeaveRequest::create($data);

            if ($splits) {
                foreach ($splits as $split) {
                    $request->splits()->create([
                        'business_id' => $request->business_id,
                        'leave_type_id' => $split['leave_type_id'],
                        'days' => $split['days'],
                    ]);
                }
            }

            // Hold as pending only up to the available balance (for paid types).
            // Any excess will be treated as LWP at approval time — employees can
            // still submit over-balance requests; HR decides paid/unpaid split.
            foreach ($this->fundingLines($request) as $line) {
                if (! $line['type']->is_paid) {
                    continue;
                }
                $available = $this->availableBalance(
                    $request->employee_id,
                    $line['type']->id,
                    Carbon::parse($request->from_date)->year,
                );
                $holdDays = min($line['days'], $available);
                if ($holdDays > 0) {
                    $this->adjustBalance($request->employee_id, $line['type']->id, $holdDays, 'pending_add', $request->from_date);
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

            // Requests raised before duplicate-prevention existed can still sit
            // in the queue in pairs. Approving both would deduct the same days
            // twice, so the second one is refused here rather than at the form.
            $clash = $this->overlappingRequest(
                (int) $request->employee_id,
                $request->from_date->toDateString(),
                $request->to_date->toDateString(),
                $request->id,
                ['approved'],
                $request->day_portion ?? 'full',
            );

            if ($clash) {
                throw new \RuntimeException(
                    "This employee already has approved leave covering these dates ({$clash->request_code}). "
                    .'Cancel that request first if this one should replace it.'
                );
            }

            $total = (float) $request->days;
            $paid = $paidDays ?? $total;
            $paid = max(0, min($paid, $total));
            $unpaid = round($total - $paid, 1);

            // If nothing this request draws on is paid, everything is unpaid.
            if (! $this->hasPaidFunding($request)) {
                $paid = 0;
                $unpaid = $total;
            }

            // Release the pending hold on every paid type this request draws on,
            // then book the real paid amount back against them. For a combined
            // request the approved paid days are shared out across the
            // contributing types in proportion to what each one funds, so
            // "0.5 Casual + 0.5 Sick" deducts correctly from both.
            $lines = $this->fundingLines($request);
            $paidRemaining = $paid;
            $lastPaidIndex = $this->lastPaidLineIndex($lines);

            foreach ($lines as $i => $line) {
                if (! $line['type']->is_paid) {
                    $line['split']?->update(['paid_days' => 0, 'unpaid_days' => $line['days']]);

                    continue;
                }

                $this->adjustBalance($request->employee_id, $line['type']->id, $line['days'], 'pending_release', $request->from_date);

                // The final paid line absorbs any rounding remainder so the
                // per-type paid days always add up to the approved total.
                $linePaid = $i === $lastPaidIndex
                    ? round($paidRemaining, 1)
                    : round(min($line['days'], $total > 0 ? $paid * ($line['days'] / $total) : 0), 1);
                $linePaid = max(0, min($linePaid, $line['days']));
                $paidRemaining = round($paidRemaining - $linePaid, 1);

                if ($linePaid > 0) {
                    $this->adjustBalance($request->employee_id, $line['type']->id, $linePaid, 'used_add', $request->from_date);
                }

                $line['split']?->update([
                    'paid_days' => $linePaid,
                    'unpaid_days' => round($line['days'] - $linePaid, 1),
                ]);
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

            // Mark attendance for the whole period. A full-day leave is a
            // plain 'on_leave' day. A HALF-day leave on a day the employee
            // actually worked is not: stamping the whole day 'on_leave' there
            // erased the worked half, so a manager who put in the morning and
            // took the afternoon off read as absent-all-day on the attendance
            // list, the calendar and the register. Such a day is
            // 'half_day_leave', and the half that was worked is whichever one
            // the leave did not cover.
            $halfPortion = in_array($request->day_portion, ['first_half', 'second_half'], true)
                ? $request->day_portion
                : null;
            $workedHalf = match ($halfPortion) {
                'first_half' => 'second_half',
                'second_half' => 'first_half',
                default => null,
            };

            $from = Carbon::parse($request->from_date);
            $to = Carbon::parse($request->to_date);
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $existing = Attendance::where('employee_id', $request->employee_id)
                    ->whereDate('date', $d->toDateString())
                    ->first();

                // Punch data is what proves the other half was worked. Without
                // it there is nothing to preserve and the day stays on_leave,
                // exactly as before.
                $worked = $halfPortion && $existing && $existing->check_in && $existing->hours_worked > 0;

                if ($worked) {
                    $existing->update([
                        'status' => 'half_day_leave',
                        'half_day_portion' => $workedHalf,
                        'source' => 'leave_approval',
                    ]);

                    continue;
                }

                Attendance::updateOrCreate(
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

            // Release the pending hold on every paid type this request drew on.
            foreach ($this->fundingLines($request) as $line) {
                if ($line['type']->is_paid) {
                    $this->adjustBalance($request->employee_id, $line['type']->id, $line['days'], 'pending_release', $request->from_date);
                }
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

    /**
     * Cancel a leave request.
     *
     * Only a PENDING request can be cancelled. An approved one is a settled
     * decision: the days have been consumed, the balance has been debited and
     * — for a past month — payroll has already been run against it. Cancelling
     * it used to credit the days straight back, which let an employee reclaim
     * balance for leave they had actually taken, including from closed months.
     *
     * Reversing an approved leave is a deliberate HR correction, not something
     * an employee does from their own screen, so this refuses it outright
     * rather than half-doing it.
     *
     * @throws \RuntimeException when the request is not pending
     */
    public function cancel(LeaveRequest $request): LeaveRequest
    {
        return DB::transaction(function () use ($request) {
            if ($request->status === 'approved') {
                throw new \RuntimeException(
                    'This leave has already been approved and cannot be cancelled. '
                    .'Please contact HR if it needs to be reversed.'
                );
            }

            if ($request->status !== 'pending') {
                return $request;
            }

            // Pending only: release the hold each paid type is carrying. No
            // 'used' balance was ever debited, so nothing is credited back.
            foreach ($this->fundingLines($request) as $line) {
                if (! $line['type']->is_paid) {
                    continue;
                }

                $this->adjustBalance(
                    $request->employee_id,
                    $line['type']->id,
                    $line['days'],
                    'pending_release',
                    $request->from_date,
                );
            }

            $request->update([
                'status' => 'cancelled',
                'actioned_at' => now(),
            ]);

            return $request->refresh();
        });
    }

    /** Does any type this request draws on carry paid leave? */
    private function hasPaidFunding(LeaveRequest $request): bool
    {
        foreach ($this->fundingLines($request) as $line) {
            if ($line['type']->is_paid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Index of the last paid funding line, which absorbs the rounding remainder
     * when approved paid days are shared across types. -1 when none are paid.
     *
     * @param  array<int,array{type: LeaveType, days: float, split: ?LeaveRequestSplit}>  $lines
     */
    private function lastPaidLineIndex(array $lines): int
    {
        $last = -1;
        foreach ($lines as $i => $line) {
            if ($line['type']->is_paid) {
                $last = $i;
            }
        }

        return $last;
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
