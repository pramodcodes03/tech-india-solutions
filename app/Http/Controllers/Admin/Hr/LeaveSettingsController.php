<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Services\LeaveAccrualService;
use App\Support\HrSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveSettingsController extends Controller
{
    private array $keys = [
        'probation_period_days',
        'leave_application_window_hours',
        'leave_accrual_frequency',
        'el_working_days_required',
        'el_carry_forward_cap',
        'leave_policy_document',
    ];

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.view'), 403);

        $settings = [];
        foreach ($this->keys as $k) {
            $settings[$k] = HrSettings::get($k);
        }

        return view('admin.hr.leave-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.edit'), 403);

        $data = $request->validate([
            'probation_period_days' => ['required', 'integer', 'min:0', 'max:730'],
            'leave_application_window_hours' => ['required', 'integer', 'min:0', 'max:2160'],
            'leave_accrual_frequency' => ['required', 'in:monthly,half_yearly,annual'],
            'el_working_days_required' => ['required', 'integer', 'min:0', 'max:1000'],
            'el_carry_forward_cap' => ['required', 'numeric', 'min:0'],
            'leave_policy_document' => ['nullable', 'string'],
        ]);

        foreach ($data as $key => $value) {
            HrSettings::set($key, $value, 'leave');
        }

        return back()->with('success', 'Leave settings updated.');
    }

    /**
     * Run accrual / year-end on demand from the settings screen.
     */
    public function run(Request $request, LeaveAccrualService $service)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.edit'), 403);
        $request->validate(['job' => ['required', 'in:accrue,year_end']]);

        if ($request->job === 'accrue') {
            $r = $service->accrueAll();

            return back()->with('success', "Accrual run complete: {$r['credited']} credited, {$r['skipped']} skipped.");
        }

        $r = $service->processYearEnd();

        return back()->with('success', "Year-end run complete: {$r['lapsed']} lapsed, {$r['carried']} carried forward.");
    }
}
