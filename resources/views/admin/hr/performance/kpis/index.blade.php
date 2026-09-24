<x-layout.admin title="KPI Master">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'KPI Master'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">KPI Master</h1>
            <p class="text-sm text-gray-500 mt-0.5">Every measurable indicator, across all KRAs.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.hr.performance.kras.index') }}" class="btn btn-outline-primary">KRA Master</a>
            @can('performance_kpi.create')<a href="{{ route('admin.hr.performance.kpis.create') }}" class="btn btn-primary">+ New KPI</a>@endcan
        </div>
    </div>

    <div class="panel p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or code…" class="form-input w-full" />
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">KRA</label>
                <select name="kra_id" class="form-select w-[220px]">
                    <option value="">All KRAs</option>
                    @foreach($kras as $k)
                        <option value="{{ $k->id }}" @selected(request('kra_id') == $k->id)>{{ $k->code }} — {{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Apply</button>
            <a href="{{ route('admin.hr.performance.kpis.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>
    </div>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>KPI</th><th>KRA</th><th>Unit</th><th class="text-right">Target</th><th class="text-right">Weightage</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($kpis as $kpi)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $kpi->code }}</td>
                        <td class="font-semibold">{{ $kpi->name }}</td>
                        <td class="text-xs">
                            <a href="{{ route('admin.hr.performance.kras.show', $kpi->kra_id) }}" class="text-primary">{{ $kpi->kra?->name ?? '—' }}</a>
                        </td>
                        <td class="text-xs">{{ $kpi->unit_label }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ $kpi->formatValue((float) $kpi->target_value) }}</td>
                        <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $kpi->weightage, 2, '.', ''), '0'), '.') }}%</td>
                        <td><span class="px-2 py-0.5 rounded text-xs font-semibold {{ $kpi->status === 'active' ? 'bg-success/10 text-success' : 'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' }}">{{ ucfirst($kpi->status) }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            @can('performance_kpi.edit')<a href="{{ route('admin.hr.performance.kpis.edit', $kpi) }}" class="text-primary text-xs font-semibold">Edit</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-10">No KPIs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $kpis->links() }}</div>
</x-layout.admin>
