<x-layout.admin title="Bulk Weightage Configuration">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Goals', 'url' => route('admin.hr.performance.goals.index')],
        ['label' => 'Weightages'],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Weightage Configuration</h1>
            <p class="text-sm text-gray-500 mt-0.5">Set many employees' weightages at once — {{ $cycle->name }}.</p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" />
            @can('performance_goals.view')
                <a href="{{ route('admin.hr.performance.goals.weightages.export', ['cycle' => $cycle->id]) }}" class="btn btn-outline-success">Export Excel</a>
            @endcan
        </div>
    </div>

    <div class="rounded-xl border-l-4 border-info bg-info/5 px-4 py-3 mb-4 text-sm text-gray-600 dark:text-gray-300">
        <b>The 100% rule.</b> Each employee's KRA weightages must total 100 — that is what makes the weighted score mean
        anything. Rows that do not add up are highlighted, and the totals update as you type.
    </div>

    @can('performance_goals.import')
        <div class="panel p-5 mb-4">
            <h3 class="font-bold mb-1">Import from Excel</h3>
            <p class="text-xs text-gray-500 mb-3">
                Export the sheet above, edit the <b>Weightage</b> column, then upload it back. Rows are matched on
                Employee Code + KRA Code, so anything that no longer exists is reported rather than guessed at.
            </p>
            <form method="POST" action="{{ route('admin.hr.performance.goals.weightages.import') }}" enctype="multipart/form-data"
                  class="flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="performance_cycle_id" value="{{ $cycle->id }}" />
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">File</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input p-2" />
                </div>
                <button class="btn btn-outline-info">Import</button>
            </form>
        </div>
    @endcan

    @if($rows->isEmpty())
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">No goals assigned in {{ $cycle->name }}.</div>
            @can('performance_goals.assign')
                <a href="{{ route('admin.hr.performance.goals.create', ['cycle' => $cycle->id]) }}" class="btn btn-primary mt-4">Assign Goals</a>
            @endcan
        </div>
    @else
        <form method="POST" action="{{ route('admin.hr.performance.goals.weightages.save') }}"
              x-data="weightageGrid({{ \Illuminate\Support\Js::from(
                  $rows->map(fn($goals) => $goals->mapWithKeys(fn($g) => [$g->id => (float) $g->weightage]))
              ) }})">
            @csrf
            <input type="hidden" name="performance_cycle_id" value="{{ $cycle->id }}" />

            <div class="panel p-4 mb-4 flex flex-wrap items-center justify-between gap-3 sticky top-4 z-10">
                <div class="text-sm">
                    <span class="font-bold" x-text="balancedCount"></span> of <span class="font-bold">{{ $rows->count() }}</span> employees total 100%
                    <span x-show="unbalancedCount > 0" class="text-warning font-semibold ltr:ml-2 rtl:mr-2">
                        · <span x-text="unbalancedCount"></span> still out of balance
                    </span>
                </div>
                @can('performance_goals.bulk_assign')
                    <button type="submit" class="btn btn-primary" @if(! $cycle->isEditable()) disabled @endif>Save All Weightages</button>
                @endcan
            </div>

            @unless($cycle->isEditable())
                <div class="rounded-xl bg-danger/10 text-danger px-4 py-3 mb-4 text-sm font-semibold">
                    {{ $cycle->name }} is {{ $cycle->status_label }} — weightages cannot be changed.
                </div>
            @endunless

            <div class="space-y-3">
                @foreach($rows as $employeeId => $goals)
                    @php $employee = $goals->first()->employee; @endphp
                    <div class="panel p-0 overflow-hidden" x-data="{ id: {{ $employeeId }} }"
                         :class="isBalanced(id) ? '' : 'ring-1 ring-warning/40'">
                        <div class="p-4 flex flex-wrap items-center gap-4 border-b border-gray-100 dark:border-[#1b2e4b]">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">{{ $employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-mono">{{ $employee?->employee_code }}</span> · {{ $employee?->department?->name ?? 'No department' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Total</div>
                                <div class="text-xl font-extrabold tabular-nums"
                                     :class="isBalanced(id) ? 'text-success' : (totalFor(id) > 100 ? 'text-danger' : 'text-warning')"
                                     x-text="totalFor(id).toFixed(2) + '%'"></div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="distributeEvenly(id)"
                                    @if(! $cycle->isEditable()) disabled @endif>Split evenly</button>
                        </div>

                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($goals as $goal)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-[#1b2e4b]">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-sm truncate">{{ $goal->kra?->name ?? '—' }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">{{ $goal->kra?->code }}</div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <input type="number" step="0.01" min="0" max="100"
                                               name="weightages[{{ $goal->id }}]"
                                               x-model.number="values[{{ $employeeId }}][{{ $goal->id }}]"
                                               class="form-input w-20 text-right tabular-nums py-1"
                                               @if(! $cycle->isEditable()) disabled @endif />
                                        <span class="text-xs font-bold text-gray-400">%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </form>

        @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('weightageGrid', (initial) => ({
                    values: initial,

                    totalFor(employeeId) {
                        const row = this.values[employeeId] || {};
                        return Object.values(row).reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                    },
                    isBalanced(employeeId) {
                        return Math.abs(this.totalFor(employeeId) - 100) < 0.01;
                    },
                    get balancedCount() {
                        return Object.keys(this.values).filter(id => this.isBalanced(id)).length;
                    },
                    get unbalancedCount() {
                        return Object.keys(this.values).length - this.balancedCount;
                    },

                    // Split 100 across this employee's KRAs, giving the remainder
                    // to the first one so the total lands exactly on 100.
                    distributeEvenly(employeeId) {
                        const row = this.values[employeeId] || {};
                        const ids = Object.keys(row);
                        if (!ids.length) return;

                        const share = Math.floor((100 / ids.length) * 100) / 100;
                        ids.forEach(id => { row[id] = share; });
                        const drift = Math.round((100 - share * ids.length) * 100) / 100;
                        row[ids[0]] = Math.round((share + drift) * 100) / 100;
                    },
                }));
            });
        </script>
        @endpush
    @endif
</x-layout.admin>
