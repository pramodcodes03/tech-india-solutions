<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Warning;
use App\Services\WarningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarningController extends Controller
{
    public function __construct(protected WarningService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('warnings.view'), 403);

        $warnings = Warning::with('employee.department', 'issuer')
            ->when($request->level, fn ($q, $l) => $q->where('level', $l))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.hr.warnings.index', compact('warnings'));
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('warnings.create'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->orderBy('first_name')->get();
        $preselect = $request->input('employee_id');

        return view('admin.hr.warnings.create', compact('employees', 'preselect'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('warnings.create'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'level' => ['required', 'integer', 'between:1,3'],
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['required', 'string'],
            'action_required' => ['nullable', 'string'],
            'issued_on' => ['required', 'date'],
        ]);
        $this->service->create($data);

        return redirect()->route('admin.hr.warnings.index')->with('success', 'Warning issued.');
    }

    public function show(Warning $warning)
    {
        abort_unless(Auth::guard('admin')->user()->can('warnings.view'), 403);
        $warning->load('employee.department', 'issuer');

        return view('admin.hr.warnings.show', compact('warning'));
    }

    public function withdraw(Warning $warning)
    {
        abort_unless(Auth::guard('admin')->user()->can('warnings.edit'), 403);
        $warning->update(['status' => 'withdrawn']);

        return back()->with('success', 'Warning withdrawn.');
    }
}
