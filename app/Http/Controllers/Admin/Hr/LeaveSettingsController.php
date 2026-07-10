<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Services\LeaveAccrualService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveSettingsController extends Controller
{
    private array $keys = [
        'probation_period_days',
        'leave_application_window_hours',
        'attendance_correction_tat_hours',
        'leave_accrual_frequency',
        'leave_accrual_day',
        'el_working_days_required',
        'el_carry_forward_cap',
        'leave_policy_document',
    ];

    /** Keys stored per-business rather than globally. */
    private array $perBusinessKeys = [
        'leave_accrual_day',
    ];

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.view'), 403);

        $businessId = app(CurrentBusiness::class)->id();

        $settings = [];
        foreach ($this->keys as $k) {
            $settings[$k] = in_array($k, $this->perBusinessKeys, true)
                ? HrSettings::getForBusiness($k, $businessId)
                : HrSettings::get($k);
        }

        return view('admin.hr.leave-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.edit'), 403);

        $data = $request->validate([
            'probation_period_days' => ['required', 'integer', 'min:0', 'max:730'],
            'leave_application_window_hours' => ['required', 'integer', 'min:0', 'max:2160'],
            'attendance_correction_tat_hours' => ['required', 'integer', 'min:1', 'max:2160'],
            'leave_accrual_frequency' => ['required', 'in:monthly,half_yearly,annual'],
            'leave_accrual_day' => ['required', 'integer', 'min:1', 'max:28'],
            'el_working_days_required' => ['required', 'integer', 'min:0', 'max:1000'],
            'el_carry_forward_cap' => ['required', 'numeric', 'min:0'],
            'leave_policy_document' => ['nullable', 'string'],
        ]);

        $businessId = app(CurrentBusiness::class)->id();

        foreach ($data as $key => $value) {
            if (in_array($key, $this->perBusinessKeys, true)) {
                HrSettings::setForBusiness($key, $businessId, $value, 'leave');
            } else {
                HrSettings::set($key, $value, 'leave');
            }
        }

        // Make the EL-specific policy actually take effect: the accrual / year-end
        // engine reads per-leave-type fields, so push the configured thresholds
        // onto every Earned Leave type (code starting with "EL"). This keeps the
        // global Leave Settings page authoritative over EL behaviour.
        \App\Models\LeaveType::withoutGlobalScopes()
            ->whereRaw('UPPER(code) LIKE ?', ['EL%'])
            ->update([
                'min_working_days' => (int) $data['el_working_days_required'],
                'max_carry_forward' => (float) $data['el_carry_forward_cap'],
                'carry_forward' => true,
            ]);

        // Seed the default accrual frequency onto any type that hasn't set one.
        \App\Models\LeaveType::withoutGlobalScopes()
            ->where(fn ($q) => $q->whereNull('accrual_frequency')->orWhere('accrual_frequency', ''))
            ->update(['accrual_frequency' => $data['leave_accrual_frequency']]);

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
