<x-layout.admin title="Performance Cycles">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Cycles'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Performance Cycles</h1>
            <p class="text-sm text-gray-500 mt-0.5">The review periods goals, assessments and scores belong to.</p>
        </div>
        @can('performance.configure')
            <a href="{{ route('admin.hr.performance.cycles.create') }}" class="btn btn-primary">+ New Cycle</a>
        @endcan
    </div>

    <form method="GET" class="panel p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status</label>
            <select name="status" onchange="this.form.submit()" class="form-select w-[180px]">
                <option value="">All statuses</option>
                @foreach(\App\Models\PerformanceCycle::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('admin.hr.performance.cycles.index') }}" class="btn btn-outline-secondary">Reset</a>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr>
                <th>Cycle</th><th>Frequency</th><th>Period</th><th>Review Deadlines</th>
                <th class="text-right">Employees</th><th class="text-right">Finalised</th><th>Status</th><th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($cycles as $cycle)
                    <tr>
                        <td>
                            <a href="{{ route('admin.hr.performance.cycles.show', $cycle) }}" class="font-semibold text-primary">{{ $cycle->name }}</a>
                            @if($cycle->notes)<div class="text-[11px] text-gray-400 max-w-[220px] truncate">{{ $cycle->notes }}</div>@endif
                        </td>
                        <td class="text-xs">{{ $cycle->frequency_label }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $cycle->period_label }}</td>
                        <td class="text-[11px] text-gray-500 whitespace-nowrap">
                            @if($cycle->self_review_due || $cycle->manager_review_due || $cycle->hr_review_due)
                                <div>Self · {{ $cycle->self_review_due?->format('d M') ?? '—' }}</div>
                                <div>Manager · {{ $cycle->manager_review_due?->format('d M') ?? '—' }}</div>
                                <div>HR · {{ $cycle->hr_review_due?->format('d M') ?? '—' }}</div>
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold">{{ $cycle->employee_kras_count }}</td>
                        <td class="text-right font-semibold">{{ $cycle->scores_count }}</td>
                        <td>
                            <span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' => $cycle->status === 'draft',
                                'bg-success/10 text-success' => $cycle->status === 'open',
                                'bg-warning/10 text-warning' => $cycle->status === 'locked',
                                'bg-info/10 text-info' => $cycle->status === 'closed',
                            ])>{{ $cycle->status_label }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.hr.performance.cycles.show', $cycle) }}" class="text-primary text-xs font-semibold">Open</a>
                            @can('performance.configure')
                                <a href="{{ route('admin.hr.performance.cycles.edit', $cycle) }}" class="text-info text-xs font-semibold ltr:ml-2 rtl:mr-2">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-10">
                        <div class="font-semibold">No cycles yet.</div>
                        <div class="text-xs mt-1">Create one to start assigning goals.</div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $cycles->links() }}</div>
</x-layout.admin>
