<x-layout.admin title="Leave Settings">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Leave Settings']]" />
    <h1 class="text-2xl font-extrabold mb-1">Leave Policy &amp; Automation Settings</h1>
    <p class="text-sm text-gray-500 mb-5">All thresholds are admin-configurable — accrual runs nightly using these values and each leave type's own accrual config.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <form method="POST" action="{{ route('admin.hr.leave-settings.update') }}" class="panel p-6 space-y-5 mb-5">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Probation Period (days)</label>
                <input type="number" name="probation_period_days" value="{{ $settings['probation_period_days'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Accrual starts only after probation (per leave type).</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Backdated Leave Window (hours)</label>
                <input type="number" name="leave_application_window_hours" value="{{ $settings['leave_application_window_hours'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Applications older than this are auto-rejected.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Attendance Correction TAT (hours)</label>
                <input type="number" name="attendance_correction_tat_hours" value="{{ $settings['attendance_correction_tat_hours'] }}" min="1" max="2160" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Resolution target for attendance correction requests (currently {{ $settings['attendance_correction_tat_hours'] }}h). Requests past this show as overdue.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Default Accrual Frequency</label>
                <select name="leave_accrual_frequency" class="form-select mt-1">
                    @foreach(['monthly'=>'Monthly','half_yearly'=>'Half-yearly','annual'=>'Annual'] as $v=>$l)
                        <option value="{{ $v }}" @selected($settings['leave_accrual_frequency']===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Accrual Credit Day (of month)</label>
                <input type="number" name="leave_accrual_day" min="1" max="28" value="{{ $settings['leave_accrual_day'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Leave is credited on this day each month (e.g. 11 = 11th). Set per business — applies to the currently selected business only.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">CL &amp; SL Working-days</label>
                <input type="number" min="0" max="1000" name="cl_sl_working_days_required" value="{{ $settings['cl_sl_working_days_required'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Days since joining before Casual &amp; Sick Leave unlock. Business default — a department or employee can override. Set per business.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">EL Working-days Required</label>
                <input type="number" min="0" max="1000" name="el_working_days_required" value="{{ $settings['el_working_days_required'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Days since joining before Earned Leave unlocks. Business default — a department or employee can override.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">EL Carry-forward Cap</label>
                <input type="number" step="0.5" name="el_carry_forward_cap" value="{{ $settings['el_carry_forward_cap'] }}" class="form-input mt-1" required>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Full Day Hours</label>
                <input type="number" step="0.5" min="1" max="24" name="full_day_hours" value="{{ $settings['full_day_hours'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">Worked hours needed to be marked <b>Present</b>. Set per business.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Half Day Hours</label>
                <input type="number" step="0.5" min="0.5" name="half_day_hours" value="{{ $settings['half_day_hours'] }}" class="form-input mt-1" required>
                <p class="text-[11px] text-gray-400 mt-1">At least this many hours (but under Full Day) = <b>Half Day</b>. Below it = <b>Absent</b>.</p>
            </div>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Company Leave Policy (shown to employees)</label>
            <textarea name="leave_policy_document" rows="8" class="form-textarea mt-1" placeholder="Describe the company leave policy…">{{ $settings['leave_policy_document'] }}</textarea>
        </div>
        <button class="btn btn-primary">Save Settings</button>
    </form>

    <div class="panel p-6">
        <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Run Jobs Now</div>
        <div class="flex gap-3 flex-wrap">
            <form method="POST" action="{{ route('admin.hr.leave-settings.run') }}" onsubmit="return confirm('Run accrual now?')">
                @csrf <input type="hidden" name="job" value="accrue"><button class="btn btn-outline-primary">Run Accrual</button>
            </form>
            <form method="POST" action="{{ route('admin.hr.leave-settings.run') }}" onsubmit="return confirm('Run year-end lapse / carry-forward now?')">
                @csrf <input type="hidden" name="job" value="year_end"><button class="btn btn-outline-warning">Run Year-end Lapse</button>
            </form>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">These also run automatically (accrual nightly, year-end on 31 Dec).</p>
    </div>
</x-layout.admin>
