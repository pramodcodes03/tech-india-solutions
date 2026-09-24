<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * One definition of "the leave list": the filters and the columns behind the
 * Leave Requests screen, its Excel export and its PDF report.
 *
 * The three used to be able to drift — a filter added to the screen would not
 * reach the export, and a column added to the export would not reach the PDF.
 * Everything now goes through here, so an export always contains exactly the
 * rows the person was looking at.
 */
class LeaveRequestReportService
{
    /**
     * Export column headings, in order. The on-screen table keeps its own
     * (narrower) layout — these are the audit columns the client asked for.
     */
    public const HEADINGS = [
        'Leave Code',
        'Employee ID',
        'Employee Name',
        'Department',
        'Leave Type',
        'Leave From Date',
        'Leave To Date',
        'Day Portion',
        'Days',
        'Status',
        'Leave Approver Name',
        'Submitted Date / Time',
    ];

    /**
     * The filtered query. Every filter is optional and each narrows on its own,
     * so any combination is valid.
     *
     * @param  array{status?:string, leave_type_id?:mixed, department_id?:mixed,
     *               search?:string, year?:mixed, month?:mixed, date?:string}  $filters
     * @return Builder<LeaveRequest>
     */
    public function query(array $filters): Builder
    {
        $f = fn (string $key) => ($filters[$key] ?? null) === '' ? null : ($filters[$key] ?? null);

        return LeaveRequest::with([
            'employee.department', 'leaveType', 'approver', 'approverEmployee', 'splits.leaveType',
        ])
            ->when($f('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($f('leave_type_id'), fn ($q, $id) => $q->where('leave_type_id', $id))
            ->when($f('department_id'), fn ($q, $id) => $q->whereHas(
                'employee', fn ($e) => $e->where('department_id', $id),
            ))
            ->when($f('search'), fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            // Year and month read off the date the leave starts, which is how a
            // leave register is normally cut.
            ->when($f('year'), fn ($q, $y) => $q->whereYear('leave_requests.from_date', $y))
            ->when($f('month'), fn ($q, $m) => $q->whereMonth('leave_requests.from_date', $m))
            // A specific date means "leave that covers this day", not "leave
            // that starts on it" — otherwise a week-long absence disappears
            // from every day but its first.
            ->when($f('date'), fn ($q, $d) => $q
                ->whereDate('leave_requests.from_date', '<=', $d)
                ->whereDate('leave_requests.to_date', '>=', $d))
            ->latest();
    }

    /**
     * The filtered rows, shaped for both the spreadsheet and the PDF.
     *
     * @return array<int, array<int, string>>
     */
    public function rows(array $filters): array
    {
        return $this->query($filters)->get()->map(fn (LeaveRequest $r) => [
            (string) $r->request_code,
            // Employee ID and name are deliberately separate columns — combined
            // in one cell they cannot be sorted or matched against payroll.
            (string) ($r->employee?->employee_code ?? ''),
            (string) ($r->employee?->full_name ?? ''),
            (string) ($r->employee?->department?->name ?? ''),
            $this->typeLabel($r),
            $r->from_date?->format('d-M-Y') ?? '',
            $r->to_date?->format('d-M-Y') ?? '',
            $this->portionLabel($r),
            rtrim(rtrim(number_format((float) $r->days, 2, '.', ''), '0'), '.'),
            ucfirst((string) $r->status),
            // Whoever actually actioned it — a reporting manager from the
            // employee portal, or an admin from the HR screen.
            (string) ($r->approver_name ?? '—'),
            $r->created_at?->format('d-M-Y h:i A') ?? '',
        ])->all();
    }

    /** A one-line description of the filters, printed under the PDF heading. */
    public function periodLabel(array $filters): ?string
    {
        $parts = [];

        if (! empty($filters['date'])) {
            $parts[] = 'Covering '.Carbon::parse($filters['date'])->format('d M Y');
        } elseif (! empty($filters['month']) || ! empty($filters['year'])) {
            $year = $filters['year'] ?? now()->year;
            $parts[] = empty($filters['month'])
                ? 'Year '.$year
                : Carbon::create((int) $year, (int) $filters['month'], 1)->format('F Y');
        }

        if (! empty($filters['status'])) {
            $parts[] = ucfirst($filters['status']);
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * A combined request is funded from several types, so naming only the
     * primary one would misreport it.
     */
    private function typeLabel(LeaveRequest $r): string
    {
        if ($r->is_combined && $r->splits->isNotEmpty()) {
            return $r->splits
                ->map(fn ($s) => rtrim(rtrim(number_format((float) $s->days, 2, '.', ''), '0'), '.')
                    .' '.($s->leaveType?->code ?? $s->leaveType?->name ?? '?'))
                ->implode(' + ');
        }

        return (string) ($r->leaveType?->name ?? '');
    }

    private function portionLabel(LeaveRequest $r): string
    {
        return match ($r->day_portion) {
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            default => 'Full Day',
        };
    }
}
