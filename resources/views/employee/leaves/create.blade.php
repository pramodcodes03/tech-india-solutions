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
    @endphp

    <form method="POST" action="{{ route('employee.leaves.store') }}"
          x-data="leaveForm({{ \Illuminate\Support\Js::from($balanceMap) }})"
          class="grid grid-cols-12 gap-4">
        @csrf

        <div class="col-span-12 lg:col-span-8 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow space-y-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Leave Type *</label>
                <select name="leave_type_id" x-model.number="type" required class="form-select mt-1">
                    <option value="">-- Select --</option>
                    @foreach($types as $t)
                        @php $bal = $balances->get($t->id); $avail = $bal ? $bal->allocated + $bal->carried_forward - $bal->used - $bal->pending : 0; @endphp
                        @if($t->is_paid)
                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }}) — {{ number_format($avail, 1) }} days available</option>
                        @else
                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }}) — Unpaid / no balance required</option>
                        @endif
                    @endforeach
                </select>
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

            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Day Portion *</label>
                <div class="flex gap-2 mt-1">
                    @foreach(['full' => 'Full Day', 'first_half' => 'First Half', 'second_half' => 'Second Half'] as $k => $v)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="day_portion" value="{{ $k }}" x-model="portion" class="sr-only" />
                            <div class="py-2 px-3 border rounded-lg text-center text-sm" :class="portion === '{{ $k }}' ? 'border-primary bg-primary/10 text-primary font-bold' : 'border-gray-300 dark:border-gray-600'">{{ $v }}</div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ─── Live preview box ───────────────────────────────── --}}
            <template x-if="type && from && to && days > 0">
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

                    {{-- Warning banner when exceeding --}}
                    <template x-if="selected && selected.is_paid && overBy > 0">
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
                <button type="submit" class="btn btn-primary">Submit Request</button>
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
            <div class="mt-4 text-[11px] text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-3">
                Your request will be forwarded to HR/Manager for approval. You'll see its status on the Leaves page.
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leaveForm', (balanceMap) => ({
                balanceMap,
                type: {{ old('leave_type_id') ? (int) old('leave_type_id') : 'null' }},
                from: '{{ old('from_date') }}',
                to: '{{ old('to_date') }}',
                portion: '{{ old('day_portion', 'full') }}',

                get selected() {
                    return this.type ? this.balanceMap[this.type] : null;
                },
                get days() {
                    if (!this.from || !this.to) return 0;
                    const f = new Date(this.from), t = new Date(this.to);
                    if (isNaN(f) || isNaN(t) || t < f) return 0;
                    const diffDays = Math.round((t - f) / 86400000) + 1;
                    if (diffDays === 1 && this.portion !== 'full') return 0.5;
                    return diffDays;
                },
                get overBy() {
                    if (!this.selected || !this.selected.is_paid) return 0;
                    return Math.max(0, this.days - this.selected.available);
                },
            }));
        });
    </script>
    @endpush
</x-layout.employee>
