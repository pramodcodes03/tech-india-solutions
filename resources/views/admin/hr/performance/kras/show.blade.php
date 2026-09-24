@php
    use App\Models\Kpi;
    $balanced = abs($kpiWeightTotal - 100) < 0.01;
@endphp

<x-layout.admin :title="$kra->name">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'KRA Master', 'url' => route('admin.hr.performance.kras.index')],
        ['label' => $kra->code],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-bold px-2 py-1 rounded bg-gray-100 dark:bg-[#1b2e4b] text-gray-500">{{ $kra->code }}</span>
                <h1 class="text-2xl font-extrabold">{{ $kra->name }}</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1">{{ $kra->scope_label }} · {{ \App\Models\PerformanceCycle::FREQUENCIES[$kra->review_frequency] ?? $kra->review_frequency }} review</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('performance_kpi.create')
                <a href="{{ route('admin.hr.performance.kpis.create', ['kra_id' => $kra->id]) }}" class="btn btn-primary">+ Add KPI</a>
            @endcan
            @can('performance_kra.edit')
                <a href="{{ route('admin.hr.performance.kras.edit', $kra) }}" class="btn btn-outline-secondary">Edit KRA</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="panel p-5 lg:col-span-2">
            <h3 class="font-bold mb-3">Details</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Department</div><div class="font-semibold mt-0.5">{{ $kra->department?->name ?? 'All' }}</div></div>
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Designation</div><div class="font-semibold mt-0.5">{{ $kra->designation?->name ?? 'All' }}</div></div>
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Default Reviewer</div><div class="font-semibold mt-0.5">{{ $kra->manager?->full_name ?? "Employee's manager" }}</div></div>
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Default Weightage</div><div class="font-semibold mt-0.5">{{ rtrim(rtrim(number_format((float) $kra->weightage, 2, '.', ''), '0'), '.') }}%</div></div>
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Active From</div><div class="font-semibold mt-0.5">{{ $kra->start_date?->format('d M Y') ?? '—' }}</div></div>
                <div><div class="text-[11px] font-bold uppercase text-gray-500">Until</div><div class="font-semibold mt-0.5">{{ $kra->end_date?->format('d M Y') ?? '—' }}</div></div>
            </div>
            @if($kra->description)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-[#1b2e4b]">
                    <div class="text-[11px] font-bold uppercase text-gray-500 mb-1">Description</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $kra->description }}</p>
                </div>
            @endif
        </div>

        <div class="panel p-5">
            <h3 class="font-bold mb-1">KPI weightage</h3>
            <p class="text-xs text-gray-500 mb-4">KPIs under one KRA should total 100% between them.</p>
            <x-performance.weightage-meter :total="$kpiWeightTotal" />
            @unless($balanced)
                <p class="text-[11px] text-gray-500 mt-3">
                    A KRA whose KPIs do not total 100% is still scored — each KPI keeps its relative share — but the
                    numbers read more clearly when they add up.
                </p>
            @endunless
        </div>
    </div>

    <div class="panel p-0">
        <div class="p-5 pb-3">
            <h3 class="font-bold">Key Performance Indicators</h3>
            <p class="text-xs text-gray-500">How this KRA is measured. Each KPI is scored from target vs achieved.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="table-striped">
                <thead><tr>
                    <th>Code</th><th>KPI</th><th>Unit</th><th class="text-right">Target</th>
                    <th class="text-right">Weightage</th><th>Scoring</th><th>Status</th><th class="text-right">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse($kra->kpis as $kpi)
                        <tr>
                            <td class="font-mono text-xs font-semibold">{{ $kpi->code }}</td>
                            <td>
                                <div class="font-semibold">{{ $kpi->name }}</div>
                                @if($kpi->description)<div class="text-[11px] text-gray-400 max-w-[260px] truncate">{{ $kpi->description }}</div>@endif
                            </td>
                            <td class="text-xs">{{ $kpi->unit_label }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ $kpi->formatValue((float) $kpi->target_value) }}</td>
                            <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $kpi->weightage, 2, '.', ''), '0'), '.') }}%</td>
                            <td class="text-[11px] text-gray-500 max-w-[200px]">{{ Kpi::FORMULAS[$kpi->score_formula] ?? $kpi->score_formula }}</td>
                            <td><span class="px-2 py-0.5 rounded text-xs font-semibold {{ $kpi->status === 'active' ? 'bg-success/10 text-success' : 'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' }}">{{ ucfirst($kpi->status) }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                @can('performance_kpi.edit')<a href="{{ route('admin.hr.performance.kpis.edit', $kpi) }}" class="text-primary text-xs font-semibold">Edit</a>@endcan
                                @can('performance_kpi.delete')
                                    <form method="POST" action="{{ route('admin.hr.performance.kpis.destroy', $kpi) }}" class="inline ltr:ml-2 rtl:mr-2"
                                          onsubmit="return confirm('Delete {{ $kpi->code }}? Reviews already under way keep their copy.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger text-xs font-semibold">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-gray-500 py-10">
                            <div class="font-semibold">No KPIs under this KRA.</div>
                            <div class="text-xs mt-1 max-w-md mx-auto">Without KPIs it is scored from the manager's 1–5 rating alone — which is fine for a qualitative area like Team Collaboration.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
