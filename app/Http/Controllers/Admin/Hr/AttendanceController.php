<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.view'), 403);

        $date = $request->input('date', now()->toDateString());

        $records = Attendance::with('employee.department', 'employee.designation')
            ->whereDate('date', $date)
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('card_no', 'like', "%{$s}%");
            })))
            ->orderBy('employee_id')
            ->paginate(30)
            ->withQueryString();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.attendance.index', compact('records', 'date', 'departments'));
    }

    public function monthly(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.view'), 403);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $employees = $this->monthlyEmployeeQuery($request)
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $summaries = [];
        foreach ($employees as $emp) {
            $summaries[$emp->id] = $this->service->monthlySummary($emp->id, $month, $year);
        }

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.attendance.monthly', compact('employees', 'summaries', 'month', 'year', 'departments'));
    }

    /**
     * Download the Monthly Summary as .xlsx or .csv. Respects the same
     * month/year/department/search filters as the on-screen page so the
     * export always matches what the user is looking at, just unpaginated.
     */
    public function exportMonthly(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.view'), 403);

        $data = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year'  => ['nullable', 'integer', 'between:2020,2100'],
            'department_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $month = (int) ($data['month'] ?? now()->month);
        $year  = (int) ($data['year']  ?? now()->year);
        $format = strtolower($data['format'] ?? 'xlsx');

        // Same filter pipeline as monthly() — but pull every matching row,
        // not a paginated slice, because the user is downloading the lot.
        $employees = $this->monthlyEmployeeQuery($request)
            ->orderBy('first_name')
            ->get();

        $rows = $employees->map(fn ($emp) => [
            'employee' => $emp,
            'summary'  => $this->service->monthlySummary($emp->id, $month, $year),
        ]);

        $stamp = \Carbon\Carbon::createFromDate($year, $month, 1)->format('Y-m');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = "attendance-monthly-{$stamp}.{$extension}";

        $writerType = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceMonthlySummaryExport($rows, $month, $year),
            $filename,
            $writerType,
        );
    }

    /**
     * Shared employee query used by both the on-screen monthly view and the
     * export endpoint. Keeping it in one place stops the two from drifting
     * out of sync when filters change.
     */
    protected function monthlyEmployeeQuery(Request $request)
    {
        return Employee::with('department')
            ->whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('card_no', 'like', "%{$s}%");
            }));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.create'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.hr.attendance.create', compact('employees'));
    }

    /**
     * Edit a single attendance row's punch times. Used by HR / Business Admin
     * to correct biometric or manual entries — the status auto-recomputes
     * from the new check_in / check_out via AttendanceService::deriveStatus().
     */
    public function edit(Attendance $attendance)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.edit'), 403);
        $attendance->load('employee');

        return view('admin.hr.attendance.edit', compact('attendance'));
    }

    /**
     * Persist the corrected punch times. Status is intentionally NOT supplied
     * so AttendanceService::upsert() reruns deriveStatus(), which means
     * editing check_out from 2 PM → 6 PM flips half_day → present, and back
     * to 2 PM flips it back — exactly as requested.
     *
     * Per-field locks: a field is locked once the admin changes its value,
     * so the daily biometric sync stops overwriting their correction. The
     * untouched field stays unlocked and continues to sync.
     */
    public function update(Request $request, Attendance $attendance)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.edit'), 403);

        $data = $request->validate([
            'check_in'  => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'remarks'   => ['nullable', 'string', 'max:500'],
        ]);

        // Normalise to H:i:s so calcHours() / DB TIME columns are happy.
        $normalise = fn ($t) => $t ? $t.':00' : null;

        $newCheckIn  = $normalise($data['check_in']  ?? null);
        $newCheckOut = $normalise($data['check_out'] ?? null);

        $oldCheckIn  = $attendance->check_in  ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s')  : null;
        $oldCheckOut = $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') : null;

        // Lock a field once it's been edited; preserve any pre-existing lock.
        // Comparing against the formatted old value handles the case where
        // admin opens the form, leaves a field as-is, and saves — that field
        // stays unlocked because the value didn't actually change.
        $checkInLocked  = $attendance->check_in_locked  || ($newCheckIn  !== $oldCheckIn);
        $checkOutLocked = $attendance->check_out_locked || ($newCheckOut !== $oldCheckOut);

        // Append "+edit" once; avoid runaway "+edit+edit+edit" on re-saves.
        $source = $attendance->source ?: 'manual';
        if (! str_ends_with($source, '+edit')) {
            $source .= '+edit';
        }

        $this->service->upsert([
            'employee_id'      => $attendance->employee_id,
            'business_id'      => $attendance->business_id,
            'date'             => $attendance->date->toDateString(),
            'check_in'         => $newCheckIn,
            'check_out'        => $newCheckOut,
            'check_in_locked'  => $checkInLocked,
            'check_out_locked' => $checkOutLocked,
            'remarks'          => $data['remarks'] ?? $attendance->remarks,
            'source'           => $source,
            // status intentionally omitted — let deriveStatus() recompute.
        ]);

        return redirect()
            ->route('admin.hr.attendance.index', ['date' => $attendance->date->toDateString()])
            ->with('success', 'Attendance updated. Status recalculated; edited fields are now locked from biometric sync.');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.create'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,absent,half_day,on_leave,holiday,weekend'],
            'remarks' => ['nullable', 'string'],
        ]);
        $data['source'] = 'manual';
        $result = $this->service->upsert($data);

        if ($result === null) {
            return redirect()->route('admin.hr.attendance.index', ['date' => $data['date']])
                ->with('error', 'Cannot record attendance — the employee has an approved comp-off on '.$data['date'].'.');
        }

        return redirect()->route('admin.hr.attendance.index', ['date' => $data['date']])
            ->with('success', 'Attendance recorded.');
    }

    public function importForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.import'), 403);

        $lastSyncLog = \Illuminate\Support\Facades\DB::table('biometric_sync_logs')
            ->orderByDesc('id')
            ->first();

        return view('admin.hr.attendance.import', compact('lastSyncLog'));
    }

    public function import(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.import'), 403);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx'],
            'report_date' => ['nullable', 'date'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $result = match ($extension) {
            'xls', 'xlsx' => $this->service->importDailyPerformance($file, $request->input('report_date')),
            default => $this->service->importBiometricCsv($file),
        };

        $msg = "Imported {$result['imported']} records, skipped {$result['skipped']}.";
        $dates = $result['dates'] ?? [];
        if (! empty($dates)) {
            $msg .= count($dates) === 1
                ? " Date: {$dates[0]}."
                : ' Dates: '.implode(', ', $dates).'.';
        } elseif (! empty($result['date'] ?? null)) {
            $msg .= " Date: {$result['date']}.";
        }
        if (! empty($result['errors'])) {
            return back()->with('error', $msg.' First errors: '.implode(' | ', array_slice($result['errors'], 0, 3)));
        }

        $redirectParams = ! empty($result['date'] ?? null) ? ['date' => $result['date']] : [];

        return redirect()->route('admin.hr.attendance.index', $redirectParams)->with('success', $msg);
    }
}
