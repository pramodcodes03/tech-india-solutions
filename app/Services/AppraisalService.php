<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Penalty;
use App\Models\Warning;
use Carbon\Carbon;

class AppraisalService
{
    private const DEFAULT_WEIGHTS = [
        'performance' => 50,
        'attendance' => 20,
        'leaves' => 10,
        'penalties' => 10,
        'warnings' => 10,
    ];

    public function generateCode(): string
    {
        $prefix = 'APR-'.date('Y').'-';
        $last = Appraisal::where('appraisal_code', 'like', $prefix.'%')
            ->orderByDesc('appraisal_code')->first();
        $next = $last ? (int) substr($last->appraisal_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-compute HR signals for the period + roll up to an overall score & rating.
     *
     * @return array<string,mixed>
     */
    public function snapshot(
        Employee $employee,
        string $periodStart,
        string $periodEnd,
        float $performanceScore = 0,
    ): array {
        $start = Carbon::parse($periodStart)->toDateString();
        $end = Carbon::parse($periodEnd)->toDateString();

        $present = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->count();
        $absent = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->where('status', 'absent')
            ->count();

        $leaveDays = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('from_date', [$start, $end])
                    ->orWhereBetween('to_date', [$start, $end]);
            })
            ->sum('days');

        $penalties = Penalty::where('employee_id', $employee->id)
            ->whereBetween('incident_date', [$start, $end])
            ->get();

        $warningCount = Warning::where('employee_id', $employee->id)
            ->whereBetween('issued_on', [$start, $end])
            ->count();

        $totalWorking = max(1, $present + $absent);
        $attendanceScore = round(($present / $totalWorking) * 100, 2);
        $leaveScore = max(0, 100 - ($leaveDays * 3));
        $penaltyScore = max(0, 100 - ($penalties->count() * 10));
        $warningScore = max(0, 100 - ($warningCount * 25));

        $scores = [
            'performance' => $performanceScore,
            'attendance' => $attendanceScore,
            'leaves' => $leaveScore,
            'penalties' => $penaltyScore,
            'warnings' => $warningScore,
        ];
        $overall = round($this->weightedOverall($scores, self::DEFAULT_WEIGHTS), 2);
        $rating = $this->rating($overall);

        return [
            'present_days' => $present,
            'absent_days' => $absent,
            'leave_days' => round((float) $leaveDays, 2),
            'penalty_count' => $penalties->count(),
            'penalty_total' => (float) $penalties->sum('amount'),
            'warning_count' => $warningCount,
            'attendance_score' => $attendanceScore,
            'leave_score' => $leaveScore,
            'penalty_score' => $penaltyScore,
            'warning_score' => $warningScore,
            'performance_score' => round($performanceScore, 2),
            'overall_score' => $overall,
            'rating' => $rating,
        ];
    }

    private function weightedOverall(array $scores, array $weights): float
    {
        $total = 0.0;
        $weightSum = 0.0;
        foreach ($weights as $key => $w) {
            if (! isset($scores[$key])) {
                continue;
            }
            $total += (float) $scores[$key] * (float) $w;
            $weightSum += (float) $w;
        }

        return $weightSum > 0 ? ($total / $weightSum) : 0;
    }

    private function rating(float $overall): string
    {
        return match (true) {
            $overall >= 90 => 'Outstanding',
            $overall >= 75 => 'Exceeds Expectations',
            $overall >= 60 => 'Meets Expectations',
            $overall >= 40 => 'Needs Improvement',
            default => 'Below Expectations',
        };
    }
}
