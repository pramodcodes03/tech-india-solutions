<x-layout.admin :title="$title">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.hr.performance.reports.index')],
        ['label' => $title],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $title }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle?->name ?? 'All cycles' }}</p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Report</label>
                <select onchange="window.location = this.value" class="form-select min-w-[220px]">
                    @foreach($reports as $key => $label)
                        <option value="{{ route('admin.hr.performance.reports.show', ['report' => $key, 'cycle' => $cycle?->id, 'department_id' => request('department_id')]) }}"
                                @selected($key === $report)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <form method="GET" class="panel p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Cycle</label>
            <select name="cycle" class="form-select min-w-[200px]">
                @foreach($cycles as $c)
                    <option value="{{ $c->id }}" @selected($cycle && $cycle->id === $c->id)>{{ $c->name }} · {{ $c->status_label }}</option>
                @endforeach
            </select>
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
        <a href="{{ route('admin.hr.performance.reports.show', ['report' => $report]) }}" class="btn btn-outline-secondary">Reset</a>

        @can('performance_reports.export')
            <div class="flex items-center gap-2 ltr:ml-auto rtl:mr-auto">
                <a href="{{ route('admin.hr.performance.reports.show', array_merge(['report' => $report, 'cycle' => $cycle?->id, 'department_id' => request('department_id')], ['format' => 'excel'])) }}"
                   class="btn btn-outline-success gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Excel
                </a>
                <a href="{{ route('admin.hr.performance.reports.show', array_merge(['report' => $report, 'cycle' => $cycle?->id, 'department_id' => request('department_id')], ['format' => 'pdf'])) }}"
                   class="btn btn-outline-danger gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" stroke-linejoin="round"/><path d="M14 3v5h5" stroke-linejoin="round"/></svg>
                    PDF
                </a>
            </div>
        @endcan
    </form>

    @if(! empty($built['summary']))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            @foreach($built['summary'] as $label => $value)
                <div class="panel p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $label }}</div>
                    <div class="text-2xl font-extrabold mt-0.5 tabular-nums">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr>@foreach($built['headings'] as $heading)<th class="whitespace-nowrap">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($built['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td class="{{ is_numeric(str_replace([',', '%'], '', (string) $cell)) ? 'text-right tabular-nums' : '' }}">{{ $cell === null || $cell === '' ? '—' : $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(1, count($built['headings'])) }}" class="text-center text-gray-500 py-10">
                        <div class="font-semibold">Nothing to report for this selection.</div>
                        <div class="text-xs mt-1">Try a different cycle, or widen the department filter.</div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
