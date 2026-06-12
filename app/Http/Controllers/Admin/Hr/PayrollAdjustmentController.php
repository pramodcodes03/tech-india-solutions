<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll_adjustments.view'), 403);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $adjustments = PayrollAdjustment::with('employee')
            ->where('month', $month)->where('year', $year)
            ->latest()->get();

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->orderBy('first_name')->get();

        return view('admin.hr.payroll-adjustments.index', compact('adjustments', 'employees', 'month', 'year'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll_adjustments.manage'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'component' => ['required', 'in:incentive,arrears,bonus,extra_deduction'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $data['business_id'] = app(CurrentBusiness::class)->id();
        $data['created_by'] = Auth::guard('admin')->id();
        PayrollAdjustment::create($data);

        return back()->with('success', 'Adjustment added. It will apply on the next payroll run for that month.');
    }

    /**
     * Auto-compute arrears for a backdated salary revision and book it as an
     * arrears adjustment for the selected month.
     */
    public function generateArrears(Request $request, \App\Services\ArrearsService $service)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll_adjustments.manage'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $result = $service->generate($employee, (int) $data['month'], (int) $data['year']);

        if (! $result['applicable']) {
            return back()->with('warning', "No arrears booked: {$result['reason']}");
        }

        return back()->with('success', "Arrears of {$result['arrears']} booked for {$employee->full_name} ({$result['reason']}).");
    }

    public function destroy(PayrollAdjustment $adjustment)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll_adjustments.manage'), 403);
        if ($adjustment->applied) {
            return back()->withErrors(['adjustment' => 'This adjustment has already been applied to a payslip.']);
        }
        $adjustment->delete();

        return back()->with('success', 'Adjustment removed.');
    }
}
