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
        {{-- ── Leave Balance Gate ──────────────────────────────────────
             Two linked switches, so they live together rather than being lost
             among the numeric thresholds above. The second only has an effect
             while the first is on, which is why it dims when the gate is off. --}}
        <div class="rounded-xl border border-gray-200 dark:border-[#253b5c] p-5"
             x-data="{ gate: {{ filter_var($settings['leave_balance_gate_enabled'], FILTER_VALIDATE_BOOL) ? 'true' : 'false' }} }">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Leave Balance Gate</div>
            <p class="text-[12px] text-gray-400 mb-4 max-w-3xl">
                Controls what an employee is allowed to <b>submit</b>. It does not change HR's approval screen —
                HR can still split an approved request into paid and unpaid days.
            </p>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="leave_balance_gate_enabled" value="1" x-model="gate"
                       class="form-checkbox mt-0.5 shrink-0" />
                <span>
                    <span class="font-semibold text-sm">Block leave requests that exceed the available balance</span>
                    <span class="block text-[12px] text-gray-400 mt-0.5">
                        On — an employee applying for more days than they hold is stopped at submission, and told what
                        they have left. Off — the earlier behaviour: over-balance requests go through and HR decides
                        the paid / unpaid split at approval.
                    </span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer mt-4 pt-4 border-t border-gray-100 dark:border-[#1b2e4b]"
                   :class="gate ? '' : 'opacity-50'">
                <input type="checkbox" name="leave_lwp_exception_enabled" value="1"
                       @checked(filter_var($settings['leave_lwp_exception_enabled'], FILTER_VALIDATE_BOOL))
                       class="form-checkbox mt-0.5 shrink-0" />
                <span>
                    <span class="font-semibold text-sm">Allow Leave Without Pay as an exception</span>
                    <span class="block text-[12px] text-gray-400 mt-0.5">
                        On (recommended) — the gate blocks paid leave types only. An employee whose balance is
                        exhausted can still apply under an unpaid / LWP type, so a genuine emergency is not locked out.
                        Off — the block is absolute: with no paid balance left the employee cannot apply for anything,
                        LWP included, and must go through HR.
                    </span>
                    <span class="block text-[12px] text-gray-400 mt-1.5" x-show="!gate" x-cloak>
                        <b>Note:</b> this setting has no effect while the gate above is off.
                    </span>
                </span>
            </label>
        </div>

        {{-- Combination Leave. Separate from the balance gate above: this one
             decides whether an employee may fund a single day from more than
             one bucket at all, regardless of what their balances look like. --}}
        <div class="rounded-lg border border-gray-200 dark:border-[#253b5c] p-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="leave_combination_enabled" value="1"
                       @checked(filter_var($settings['leave_combination_enabled'] ?? true, FILTER_VALIDATE_BOOL))
                       class="form-checkbox mt-0.5 shrink-0" />
                <span>
                    <span class="font-semibold text-sm">Allow Combination Leave</span>
                    <span class="block text-[12px] text-gray-400 mt-0.5">
                        On (default) — an employee may fund one full day from two or more leave types, for example
                        0.5 Casual + 0.5 Sick. Off — every request must come from a single leave type, and the
                        combine option disappears from the employee's apply form.
                    </span>
                    <span class="block text-[12px] text-gray-400 mt-1.5">
                        Combination only ever applies to a <b>full day</b>. It is never offered on a half-day
                        request, since half a day cannot be split further.
                    </span>
                </span>
            </label>
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
