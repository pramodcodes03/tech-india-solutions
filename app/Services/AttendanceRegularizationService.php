<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Notifications\NotificationDispatcher;
use App\Support\HrSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceRegularizationService
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    /**
     * Employee raises a correction request. Stamps the SLA due time from the
     * configurable TAT window (default 48h).
     */
    public function create(array $data): AttendanceRegularization
    {
        $hours = HrSettings::int('attendance_correction_tat_hours', 48);

        // Link to the existing attendance row for that day, if any.
        $existing = Attendance::where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])->first();

        $reg = AttendanceRegularization::create(array_merge($data, [
            'attendance_id' => $existing?->id,
            'status' => 'pending',
            'sla_due_at' => now()->addHours($hours),
        ]));

        NotificationDispatcher::fire('attendance.regularization_requested', $reg);

        return $reg;
    }

    /**
     * HR approves and (optionally) writes the corrected punches into attendance.
     */
    public function approve(AttendanceRegularization $reg, ?string $remarks = null): AttendanceRegularization
    {
        return DB::transaction(function () use ($reg, $remarks) {
            // Apply the correction to the attendance record.
            $this->attendance->upsert([
                'employee_id' => $reg->employee_id,
                'date' => $reg->date->toDateString(),
                'check_in' => $reg->expected_in?->format('H:i:s') ?? optional($reg->attendance)->check_in?->format('H:i:s'),
                'check_out' => $reg->expected_out?->format('H:i:s') ?? optional($reg->attendance)->check_out?->format('H:i:s'),
                'source' => 'regularization',
                'status' => 'present',
                'remarks' => 'Regularized: '.$reg->type_label,
            ]);

            $reg->update([
                'status' => 'approved',
                'reviewed_by' => Auth::guard('admin')->id(),
                'reviewed_at' => now(),
                'review_remarks' => $remarks,
                'applied' => true,
            ]);

            \App\Models\AdminNotification::markRelatedAsRead($reg, ['attendance.regularization_requested']);
            NotificationDispatcher::fire('attendance.regularization_approved', $reg);

            return $reg;
        });
    }

    public function reject(AttendanceRegularization $reg, ?string $remarks = null): AttendanceRegularization
    {
        $reg->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'review_remarks' => $remarks,
        ]);

        \App\Models\AdminNotification::markRelatedAsRead($reg, ['attendance.regularization_requested']);
        NotificationDispatcher::fire('attendance.regularization_rejected', $reg);

        return $reg;
    }

    /**
     * Flag every pending request past its SLA as escalated. Run from scheduler.
     *
     * @return int number newly escalated
     */
    public function escalateBreaches(): int
    {
        $breaching = AttendanceRegularization::pending()
            ->where('escalated', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->get();

        foreach ($breaching as $reg) {
            $reg->update(['escalated' => true]);
            NotificationDispatcher::fire('attendance.regularization_escalated', $reg);
        }

        return $breaching->count();
    }
}
