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
                    ->orWhere('employee_code', 'like', "%{$s}%");
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

        $employees = Employee::with('department')
            ->whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            }))
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

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.create'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.hr.attendance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.create'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:present,absent,half_day,late,on_leave,holiday,weekend'],
            'remarks' => ['nullable', 'string'],
        ]);
        $data['source'] = 'manual';
        $this->service->upsert($data);

        return redirect()->route('admin.hr.attendance.index', ['date' => $data['date']])
            ->with('success', 'Attendance recorded.');
    }

    public function importForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.import'), 403);

        return view('admin.hr.attendance.import');
    }

    public function import(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.import'), 403);
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt']]);

        $result = $this->service->importBiometricCsv($request->file('csv'));

        $msg = "Imported {$result['imported']} records, skipped {$result['skipped']}.";
        if (! empty($result['errors'])) {
            return back()->with('error', $msg.' First errors: '.implode(' | ', array_slice($result['errors'], 0, 3)));
        }

        return redirect()->route('admin.hr.attendance.index')->with('success', $msg);
    }
}
