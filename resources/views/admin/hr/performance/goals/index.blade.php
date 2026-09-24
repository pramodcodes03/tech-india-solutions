<x-layout.admin title="Goal Assignment">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Goals'],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Assigned Goals</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle?->name ?? 'No cycle selected' }} — who is measured on what.</p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            @if($cycles->isNotEmpty())
                <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" />
            @endif
            @can('performance_goals.view')
                <a href="{{ route('admin.hr.performance.goals.weightages', ['cycle' => $cycle?->id]) }}" class="btn btn-outline-primary">Weightages</a>
            @endcan
            @can('performance_goals.assign')
                <a href="{{ route('admin.hr.performance.goals.create', ['cycle' => $cycle?->id]) }}" class="btn btn-primary">Assign Goals</a>
            @endcan
        </div>
    </div>

    @if(! $cycle)
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">No performance cycle yet.</div>
            <div class="text-xs mt-1">Create one before assigning goals.</div>
            @can('performance.configure')
                <a href="{{ route('admin.hr.performance.cycles.create') }}" class="btn btn-primary mt-4">Create a Cycle</a>
            @endcan
        </div>
    @else
        <div class="panel p-4 mb-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="cycle" value="{{ $cycle->id }}" />
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Search employee</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or employee ID…" class="form-input w-full" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                    <select name="department_id" class="form-select w-[190px]">
                        <option value="">All departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.hr.performance.goals.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>

        @if($rows->isEmpty())
            <div class="panel p-10 text-center text-gray-500">
                <div class="font-semibold">No goals assigned in {{ $cycle->name }}.</div>
                <div class="text-xs mt-1">Assign KRAs to get the cycle moving.</div>
                @can('performance_goals.assign')
                    <a href="{{ route('admin.hr.performance.goals.create', ['cycle' => $cycle->id]) }}" class="btn btn-primary mt-4">Assign Goals</a>
                @endcan
            </div>
        @else
            <div class="space-y-3">
                @foreach($rows as $employeeId => $goals)
                    @php
                        $employee = $goals->first()->employee;
                        $total = (float) ($totals[$employeeId] ?? 0);
                        $balanced = abs($total - 100) < 0.01;
                        $status = $goals->first()->status;
                    @endphp
                    <div class="panel p-0 overflow-hidden">
                        <div class="p-4 flex flex-wrap items-center gap-4 border-b border-gray-100 dark:border-[#1b2e4b] {{ $balanced ? '' : 'bg-warning/5' }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">{{ $employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-mono">{{ $employee?->employee_code }}</span>
                                    · {{ $employee?->department?->name ?? 'No department' }}
                                    · {{ $employee?->designation?->name ?? 'No designation' }}
                                </div>
                            </div>
                            <x-performance.stage-stepper :current="$status" />
                            <x-performance.weightage-meter :total="$total" />
                            @can('performance_reviews.view')
                                <a href="{{ route('admin.hr.performance.reviews.show', ['cycle' => $cycle->id, 'employee' => $employeeId]) }}" class="btn btn-outline-primary btn-sm">Open Review</a>
                            @endcan
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-striped text-sm">
                                <thead><tr><th>KRA</th><th>Reviewer</th><th class="text-right">Weightage</th><th class="text-right">KPIs</th><th class="text-right">Progress</th><th>Status</th><th class="text-right"></th></tr></thead>
                                <tbody>
                                    @foreach($goals as $goal)
                                        <tr>
                                            <td>
                                                <span class="font-mono text-[11px] text-gray-400">{{ $goal->kra?->code }}</span>
                                                <div class="font-semibold">{{ $goal->kra?->name ?? '—' }}</div>
                                            </td>
                                            <td class="text-xs">{{ $goal->manager?->full_name ?? '—' }}</td>
                                            <td class="text-right font-bold tabular-nums">{{ rtrim(rtrim(number_format((float) $goal->weightage, 2, '.', ''), '0'), '.') }}%</td>
                                            <td class="text-right">{{ $goal->kpis->count() }}</td>
                                            <td class="text-right">
                                                @if($goal->kpis->isEmpty())
                                                    <span class="text-gray-400 text-xs">Rating only</span>
                                                @else
                                                    <span class="font-semibold tabular-nums">{{ number_format($goal->progress_percent, 1) }}%</span>
                                                @endif
                                            </td>
                                            <td><span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 dark:bg-[#1b2e4b] text-gray-500">{{ $goal->status_label }}</span></td>
                                            <td class="text-right">
                                                @can('performance_goals.delete')
                                                    @if($goal->status === 'assigned')
                                                        <form method="POST" action="{{ route('admin.hr.performance.goals.destroy', $goal) }}" class="inline"
                                                              onsubmit="return confirm('Remove {{ $goal->kra?->code }} from {{ $employee?->full_name }}?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-danger text-xs font-semibold">Remove</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-layout.admin>
