<x-layout.employee title="Apply for Leave">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Apply for Leave</h1>
        <a href="{{ route('employee.leaves.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    @php
        // Build a map of leave_type_id → availability + is_paid flag for JS.
        $balanceMap = [];
        foreach ($types as $t) {
            $bal = $balances->get($t->id);
            $avail = $bal ? $bal->allocated + $bal->carried_forward - $bal->used - $bal->pending : 0;
            $balanceMap[$t->id] = [
                'code' => $t->code,
                'name' => $t->name,
                'available' => round($avail, 1),
                'is_paid' => (bool) $t->is_paid,
                'color' => $t->color,
            ];
        }
        // Working-days eligibility map for JS: whether each type is unlocked and,
        // if not, why + when it unlocks.
        $eligMap = [];
        foreach ($types as $t) {
            $e = $leaveEligibility[$t->id] ?? null;
            $eligMap[$t->id] = [
                'eligible' => $e['eligible'] ?? true,
                'required' => $e['required'] ?? 0,
                'completed' => $e['completed'] ?? 0,
                'remaining' => $e['remaining'] ?? 0,
                'reason' => $e['reason'] ?? null,
            ];
        }
    @endphp

    <form method="POST" action="{{ route('employee.leaves.store') }}"
          x-data="leaveForm({{ \Illuminate\Support\Js::from($balanceMap) }}, {{ \Illuminate\Support\Js::from($weekOffDays) }}, {{ \Illuminate\Support\Js::from($holidayDates) }}, {{ \Illuminate\Support\Js::from($eligMap) }}, {{ \Illuminate\Support\Js::from($balanceGate) }})"
          class="grid grid-cols-12 gap-4">
        @csrf

        <div class="col-span-12 lg:col-span-8 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow space-y-4">
            {{-- Combined Leave: fund one request from two or more types, so
                 half-days left in different buckets still add up to a day off.

                 Offered only when the business allows it, and only on a full
                 day — half a day cannot be split further, so the whole block
                 disappears the moment a half-day portion is chosen. --}}
            @if($combinationEnabled)
                <div x-show="portion === 'full'" x-transition
                     class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-3">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" x-model="combined" class="form-checkbox mt-0.5 shrink-0" />
                        <span>
                            <span class="text-sm font-semibold">Combine two or more leave types</span>
                            <span class="block text-[11px] text-gray-500 mt-0.5">
                                Use this when no single type has enough left — for example 0.5 Casual + 0.5 Sick makes one full day.
                            </span>
                        </span>
                    </label>
                </div>
            @endif

            {{-- x-show is display:none, which hides these fields but leaves them
                 in the form — they still post. Every split input is therefore
                 also :disabled when combining is off, because a disabled field
                 is the only one a browser genuinely leaves out. --}}
            <div x-show="combined" x-cloak class="space-y-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Combine from *</label>

                <template x-for="(row, i) in splitRows" :key="i">
                    <div class="flex gap-2 items-start">
                        <select :name="`splits[${i}][leave_type_id]`" x-model.number="row.type"
                                :disabled="!combined" class="form-select flex-1">
                            <option value="">-- Select leave type --</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.5" min="0" :name="`splits[${i}][days]`" x-model.number="row.days"
                               :disabled="!combined" placeholder="Days" class="form-input w-28" />
                        <button type="button" @click="removeRow(i)" x-show="splitRows.length > 2"
                                class="btn btn-outline-danger px-3" title="Remove">&times;</button>
                    </div>
                </template>

                <button type="button" @click="addRow()" class="text-primary text-xs font-bold">+ Add another leave type</button>

                {{-- Live reconciliation against the days actually being applied for. --}}
                <div class="flex items-center justify-between text-sm px-3 py-2 rounded-lg"
                     :class="splitMatches ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                    <span x-text="`Combined total: ${splitTotal.toFixed(1)} of ${days.toFixed(1)} day(s)`"></span>
                    <span class="font-bold" x-text="splitMatches ? '✓ Matches' : 'Does not match yet'"></span>
                </div>
                <p class="text-[11px] text-gray-400" x-show="!splitMatches" x-cloak>
                    Pick your dates first, then split those days across the leave types. The totals must match before you can submit.
                </p>
            </div>

            <div x-show="!combined">
                <label class="text-xs font-semibold text-gray-500 uppercase">Leave Type *</label>
                <select name="leave_type_id" x-model.number="type" :required="!combined" :disabled="combined" class="form-select mt-1">
                    <option value="">-- Select --</option>
                    @foreach($types as $t)
                        @php
                            $bal = $balances->get($t->id);
                            $avail = $bal ? $bal->allocated + $bal->carried_forward - $bal->used - $bal->pending : 0;
                            $elig = $leaveEligibility[$t->id] ?? ['eligible' => true, 'remaining' => 0];
                            $lock = ($t->is_paid && ! ($elig['eligible'] ?? true)) ? '🔒 ' : '';
                            $suffix = ($t->is_paid && ! ($elig['eligible'] ?? true))
                                ? ' — locked ('.$elig['remaining'].' more working days)'
                                : ($t->is_paid ? ' — '.number_format($avail, 1).' days available' : ' — Unpaid / no balance required');
                        @endphp
                        <option value="{{ $t->id }}">{{ $lock }}{{ $t->name }} ({{ $t->code }}){{ $suffix }}</option>
                    @endforeach
                </select>

                {{-- Live eligibility notice for the chosen leave type --}}
                <template x-if="!combined && type && elig[type] && !elig[type].eligible">
                    <div class="mt-2 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 px-3 py-2 text-sm text-amber-800 dark:text-amber-200">
                        <span class="font-semibold">🔒 Not yet available:</span>
                        <span x-text="elig[type].reason"></span>
                    </div>
                </template>
                <template x-if="!combined && type && elig[type] && elig[type].eligible && elig[type].required > 0">
                    <div class="mt-2 rounded-lg border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-700 px-3 py-2 text-sm text-emerald-800 dark:text-emerald-200">
                        <span class="font-semibold">✓ Available.</span>
                        <span x-text="'You have completed ' + elig[type].completed + ' working days (' + elig[type].required + ' required).'"></span>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">From Date *</label>
                    <input type="date" name="from_date" x-model="from" value="{{ old('from_date') }}" min="{{ now()->toDateString() }}" required class="form-input mt-1" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">To Date *</label>
                    <input type="date" name="to_date" x-model="to" value="{{ old('to_date') }}" min="{{ now()->toDateString() }}" required class="form-input mt-1" />
                </div>
            </div>

            {{-- Day portion.

                 The half-day choices are always RENDERED and merely disabled on
                 a multi-day range, never hidden. Hiding them behind x-show +
                 x-cloak meant a single JS hiccup left them invisible with no
                 way to tell, which is what "half-day doesn't work" turned out
                 to be — the options were simply never on screen. --}}
            <div x-effect="if (!isSingleDay && portion !== 'full') portion = 'full'">
                <label class="text-xs font-semibold text-gray-500 uppercase">Day Portion *</label>
                <div class="flex gap-2 mt-1">
                    @foreach(['full' => 'Full Day', 'first_half' => 'First Half', 'second_half' => 'Second Half'] as $k => $v)
                        @php $halfOption = $k !== 'full'; @endphp
                        <label class="flex-1"
                               @if($halfOption)
                                   :class="isSingleDay ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'"
                               @else
                                   class="flex-1 cursor-pointer"
                               @endif>
                            <input type="radio" name="day_portion" value="{{ $k }}" x-model="portion"
                                   class="sr-only"
                                   @if($halfOption) :disabled="!isSingleDay" @endif />
                            <div class="py-2 px-3 border rounded-lg text-center text-sm"
                                 :class="portion === '{{ $k }}' ? 'border-primary bg-primary/10 text-primary font-bold' : 'border-gray-300 dark:border-gray-600'">{{ $v }}</div>
                        </label>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    <span x-show="isSingleDay">Half a day counts as 0.5 against your balance.</span>
                    <span x-show="!isSingleDay">Half-day applies to single-day leave only — set the same From and To date to use it.</span>
                </p>
            </div>

            {{-- ─── Live preview box ───────────────────────────────── --}}
            <template x-if="!combined && type && from && to && days > 0">
                <div class="space-y-2">
                    {{-- Days breakdown --}}
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-dark-light/20">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Days requested</span>
                            <strong class="text-lg" x-text="days.toFixed(1) + ' day' + (days === 1 ? '' : 's')"></strong>
                        </div>
                        <template x-if="selected && selected.is_paid">
                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="'Available in ' + selected.code"></span>
                                    <strong x-text="selected.available.toFixed(1)"></strong>
                                </div>
                                <template x-if="overBy > 0">
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-warning font-semibold">Over balance by</span>
                                        <strong class="text-warning" x-text="overBy.toFixed(1) + ' day' + (overBy === 1 ? '' : 's')"></strong>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Warning banner when exceeding — only meaningful while the
                         Leave Balance Gate is off, since with it on the request
                         cannot be submitted at all. --}}
                    <template x-if="!gate.enabled && selected && selected.is_paid && overBy > 0">
                        <div class="p-4 rounded-lg bg-warning/10 border border-warning/30 border-l-4 border-l-warning">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.3 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/>
                                </svg>
                                <div class="flex-1 text-sm">
                                    <div class="font-bold text-warning mb-1">Heads up — your balance is less than requested</div>
                                    <div class="text-gray-700 dark:text-gray-300">
                                        You're applying for <strong x-text="days.toFixed(1)"></strong> days but only have
                                        <strong x-text="selected.available.toFixed(1)"></strong> days of <strong x-text="selected.name"></strong> available.
                                    </div>
                                    <div class="mt-2 text-gray-700 dark:text-gray-300">
                                        You can still submit the request. HR may approve
                                        <strong class="text-success" x-text="Math.min(days, selected.available).toFixed(1) + ' day(s) as paid'"></strong>
                                        and the remaining
                                        <strong class="text-warning" x-text="overBy.toFixed(1) + ' day(s) as unpaid (LWP)'"></strong>.
                                        Unpaid days will be deducted from your next payslip.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Leave Balance Gate: hard block, mirroring the server. --}}
                    <template x-if="blockedReason">
                        <div class="p-4 rounded-lg bg-danger/10 border border-danger/30 border-l-4 border-l-danger">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-danger shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5m0 4h.01"/>
                                </svg>
                                <div class="flex-1 text-sm">
                                    <div class="font-bold text-danger mb-1">This request cannot be submitted</div>
                                    <div class="text-gray-700 dark:text-gray-300" x-text="blockedReason"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Info banner for LWP type --}}
                    <template x-if="selected && !selected.is_paid">
                        <div class="p-4 rounded-lg bg-info/10 border border-info/30 border-l-4 border-l-info">
                            <div class="text-sm">
                                <div class="font-bold text-info mb-1">This is an unpaid leave</div>
                                <div class="text-gray-700 dark:text-gray-300">
                                    All <strong x-text="days.toFixed(1)"></strong> day(s) will be treated as Leave Without Pay and deducted from your next payslip.
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Reason *</label>
                <textarea name="reason" rows="4" required minlength="5" class="form-input mt-1" placeholder="Briefly describe the reason for leave...">{{ old('reason') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary"
                        :disabled="cannotSubmit"
                        :class="cannotSubmit ? 'opacity-50 cursor-not-allowed' : ''">
                    Submit Request
                </button>
                <a href="{{ route('employee.leaves.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Your Balance</h3>
            @foreach($types as $t)
                @php $bal = $balances->get($t->id); $avail = $bal ? $bal->allocated + $bal->carried_forward - $bal->used - $bal->pending : 0; @endphp
                <div class="flex items-center justify-between py-1.5 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full" style="background: {{ $t->color }}"></span>
                        {{ $t->name }}
                    </div>
                    <div class="font-semibold">{{ number_format($avail, 1) }}</div>
                </div>
            @endforeach
            @if($balanceGate['enabled'])
                <div class="mt-4 text-[11px] rounded-lg px-3 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
                    <b>Balance policy:</b>
                    @if($balanceGate['lwp_exception'])
                        you cannot apply for more paid leave than you have left. If your balance is exhausted,
                        apply under a Leave Without Pay type instead.
                    @else
                        you cannot apply for more leave than you have left. Once your balance is exhausted,
                        including Leave Without Pay, please contact HR.
                    @endif
                </div>
            @endif

            <div class="mt-4 text-[11px] text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-3">
                Your request will be forwarded to HR/Manager for approval. You'll see its status on the Leaves page.
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leaveForm', (balanceMap, weekOffDays = [], holidayDates = [], elig = {}, gate = {}) => ({
                balanceMap,
                elig,                              // leave_type_id → eligibility {eligible, required, completed, remaining, reason}
                gate,                              // {enabled, lwp_exception, has_any_paid_balance}
                weekOffDays,                       // [0..6] week-off weekdays
                holidayDates: new Set(holidayDates), // 'YYYY-MM-DD' public holidays
                type: {{ old('leave_type_id') ? (int) old('leave_type_id') : 'null' }},
                combined: {{ old('splits') && $combinationEnabled ? 'true' : 'false' }},
                splitRows: {{ \Illuminate\Support\Js::from(
                    collect(old('splits', []))
                        ->map(fn ($r) => ['type' => (int) ($r['leave_type_id'] ?? 0) ?: '', 'days' => (float) ($r['days'] ?? 0) ?: ''])
                        ->when(fn ($c) => $c->count() < 2, fn ($c) => $c->pad(2, ['type' => '', 'days' => '']))
                        ->values()
                ) }},
                from: '{{ old('from_date') }}',
                to: '{{ old('to_date') }}',
                portion: '{{ old('day_portion', 'full') }}',
                combinationEnabled: {{ $combinationEnabled ? 'true' : 'false' }},

                init() {
                    // Picking a From date fills To with the same day, so a
                    // single-day request — and therefore the half-day options —
                    // is one click away instead of needing both fields set by
                    // hand. Only ever widens nothing: an existing later To is
                    // left alone.
                    this.$watch('from', (value) => {
                        if (! value) return;
                        if (! this.to || this.to < value) this.to = value;
                    });

                    // Half a day cannot be split across types.
                    this.$watch('portion', (value) => {
                        if (value !== 'full') this.combined = false;
                    });
                },

                get selected() {
                    return this.type ? this.balanceMap[this.type] : null;
                },
                // Half-day portions are only offered when both dates are the same day.
                get isSingleDay() {
                    return !!this.from && !!this.to && this.from === this.to;
                },
                // True when a date is a week-off or public holiday → not a leave day.
                isNonWorking(d) {
                    const iso = d.toISOString().slice(0, 10);
                    return this.weekOffDays.includes(d.getDay()) || this.holidayDates.has(iso);
                },
                get days() {
                    if (!this.from || !this.to) return 0;
                    const f = new Date(this.from), t = new Date(this.to);
                    if (isNaN(f) || isNaN(t) || t < f) return 0;

                    // Single date: 0 if it's a week-off/holiday, else half or full.
                    if (f.getTime() === t.getTime()) {
                        if (this.isNonWorking(f)) return 0;
                        return this.portion !== 'full' ? 0.5 : 1;
                    }

                    // Multi-day: count working days only (skip week-offs/holidays).
                    let count = 0;
                    for (let d = new Date(f); d <= t; d.setDate(d.getDate() + 1)) {
                        if (!this.isNonWorking(d)) count++;
                    }
                    return count;
                },
                get overBy() {
                    if (!this.selected || !this.selected.is_paid) return 0;
                    return Math.max(0, this.days - this.selected.available);
                },

                // Mirrors LeaveBalanceGateService::evaluate() so the form refuses
                // exactly what the server would, with the same wording.
                get blockedReason() {
                    if (this.combined) return null;   // server checks each type
                    if (!this.gate.enabled || !this.selected || this.days <= 0) return null;

                    const n = v => (Math.round(v * 10) / 10).toString().replace(/\.0$/, '');

                    if (!this.selected.is_paid) {
                        if (this.gate.lwp_exception || this.gate.has_any_paid_balance) return null;
                        return 'You have no paid leave balance remaining, and Leave Without Pay is not permitted '
                             + 'as an exception under the current policy. Please contact HR.';
                    }

                    if (this.overBy <= 0) return null;

                    const tail = this.gate.lwp_exception
                        ? ' If you still need the time off, apply under a Leave Without Pay type instead.'
                        : ' Please contact HR.';

                    return this.selected.available <= 0
                        ? `You do not have any leave balance. No ${this.selected.name} remains (0 days available), so this request cannot be submitted.${tail}`
                        : `You have ${n(this.selected.available)} day(s) of ${this.selected.name} available but applied for ${n(this.days)}. This request cannot be submitted.${tail}`;
                },

                // ── Combined Leave ──────────────────────────────────────
                addRow() {
                    this.splitRows.push({ type: '', days: '' });
                },
                removeRow(i) {
                    if (this.splitRows.length > 2) this.splitRows.splice(i, 1);
                },
                get splitTotal() {
                    return this.splitRows.reduce((sum, r) => sum + (parseFloat(r.days) || 0), 0);
                },
                // Rounded to one decimal because half-days are the whole point.
                get splitMatches() {
                    if (this.days <= 0) return false;
                    const filled = this.splitRows.filter(r => r.type && parseFloat(r.days) > 0);
                    if (filled.length < 2) return false;
                    const types = filled.map(r => r.type);
                    if (new Set(types).size !== types.length) return false;   // same type twice
                    return Math.abs(this.splitTotal - this.days) < 0.05;
                },

                // Either gate — working-days eligibility or leave balance — closes the
                // button. In combined mode the server checks each contributing type;
                // the form only insists that the split adds up.
                get cannotSubmit() {
                    if (this.combined) return !this.splitMatches;
                    const locked = this.type && this.elig[this.type] && !this.elig[this.type].eligible;
                    return !!(locked || this.blockedReason);
                },
            }));
        });
    </script>
    @endpush
</x-layout.employee>
