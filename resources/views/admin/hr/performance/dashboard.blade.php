@php
    $s = $stats;
    $avg = $s['average_score'];
@endphp

<x-layout.admin title="Performance Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Performance']]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Performance Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $cycle ? $cycle->name.' · '.$cycle->period_label : 'No performance cycle yet' }}
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            @if($cycles->isNotEmpty())
                <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" />
            @endif
            @can('performance.configure')
                <a href="{{ route('admin.hr.performance.cycles.create') }}" class="btn btn-primary">+ New Cycle</a>
            @endcan
        </div>
    </div>

    @if(! $cycle)
        <div class="panel p-10 text-center">
            <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary grid place-content-center mx-auto mb-4">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke-linecap="round"/></svg>
            </div>
            <h2 class="font-extrabold text-lg">Start with a performance cycle</h2>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                A cycle is the review period everything else hangs off — goals, assessments and scores all belong to one.
                Create the current quarter to get going.
            </p>
            <div class="flex flex-wrap gap-2 justify-center mt-5">
                @can('performance.configure')<a href="{{ route('admin.hr.performance.cycles.create') }}" class="btn btn-primary">Create a Cycle</a>@endcan
                @can('performance_kra.view')<a href="{{ route('admin.hr.performance.kras.index') }}" class="btn btn-outline-primary">Set up KRAs</a>@endcan
            </div>
        </div>
    @else
        {{-- The nine headline metrics. --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-4 dash-animate">
            <x-tracker.stat label="Employees" :value="number_format($s['employees'])" tone="primary" :sub="$s['in_cycle'].' in this cycle'" />
            <x-tracker.stat label="Active KRAs" :value="number_format($s['active_kras'])" tone="info" />
            <x-tracker.stat label="Active KPIs" :value="number_format($s['active_kpis'])" tone="secondary" />
            <x-tracker.stat label="Pending Reviews" :value="number_format($s['pending_approvals'])" tone="warning" sub="awaiting a stage" />
            <x-tracker.stat label="Completed" :value="number_format($s['completed_reviews'])" tone="success" sub="finalised" />
            <div class="panel p-4 flex items-center gap-3">
                <x-performance.score-ring :score="$avg" :size="64" />
                <div class="min-w-0">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Average Score</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $avg === null ? 'Nothing finalised yet' : 'across the organisation' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
            <div class="panel p-5 xl:col-span-2">
                <h3 class="font-bold mb-1">Department comparison</h3>
                <p class="text-xs text-gray-500 mb-3">Average finalised score per department for {{ $cycle->name }}.</p>
                @if($byDepartment->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500">No finalised scores yet.</div>
                @else
                    <div id="chartDepartments"></div>
                @endif
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Band spread</h3>
                <p class="text-xs text-gray-500 mb-3">How the workforce sits across the bands.</p>
                @if($bandSpread->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500">No finalised scores yet.</div>
                @else
                    <div id="chartBands"></div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
            <div class="panel p-0">
                <div class="p-5 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold">Top performers</h3>
                        <p class="text-xs text-gray-500">Highest finalised scores.</p>
                    </div>
                    @can('performance_reports.view')
                        <a href="{{ route('admin.hr.performance.reports.show', ['report' => 'top_performers', 'cycle' => $cycle->id]) }}" class="text-primary text-xs font-bold">Full list →</a>
                    @endcan
                </div>
                <div class="divide-y divide-gray-50 dark:divide-[#1b2e4b]">
                    @forelse($top as $row)
                        <div class="flex items-center gap-3 px-5 py-2.5">
                            <span class="w-6 h-6 rounded-lg bg-success/10 text-success grid place-content-center text-[11px] font-black shrink-0">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm truncate">{{ $row->employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $row->employee?->department?->name ?? '—' }}</div>
                            </div>
                            <x-performance.band-pill :band="$row->band" />
                            <span class="font-extrabold text-success text-sm tabular-nums">{{ number_format((float) $row->final_score, 1) }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">Nothing finalised yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel p-0">
                <div class="p-5 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold">Needs attention</h3>
                        <p class="text-xs text-gray-500">Lowest finalised scores.</p>
                    </div>
                    @can('performance_reports.view')
                        <a href="{{ route('admin.hr.performance.reports.show', ['report' => 'bottom_performers', 'cycle' => $cycle->id]) }}" class="text-primary text-xs font-bold">Full list →</a>
                    @endcan
                </div>
                <div class="divide-y divide-gray-50 dark:divide-[#1b2e4b]">
                    @forelse($low as $row)
                        <div class="flex items-center gap-3 px-5 py-2.5">
                            <span class="w-6 h-6 rounded-lg bg-danger/10 text-danger grid place-content-center text-[11px] font-black shrink-0">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm truncate">{{ $row->employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $row->employee?->department?->name ?? '—' }}</div>
                            </div>
                            <x-performance.band-pill :band="$row->band" />
                            <span class="font-extrabold text-danger text-sm tabular-nums">{{ number_format((float) $row->final_score, 1) }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">Nothing finalised yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Upcoming review dates</h3>
                    <p class="text-xs text-gray-500">Deadlines on open and draft cycles.</p>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-[#1b2e4b]">
                    @forelse($upcoming as $c)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('admin.hr.performance.cycles.show', $c) }}" class="font-semibold text-sm text-primary">{{ $c->name }}</a>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $c->status === 'open' ? 'bg-success/10 text-success' : 'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' }}">{{ $c->status_label }}</span>
                            </div>
                            <div class="mt-1.5 space-y-0.5 text-[11px] text-gray-500">
                                @if($c->self_review_due)<div>Self-assessment · <b>{{ $c->self_review_due->format('d M Y') }}</b></div>@endif
                                @if($c->manager_review_due)<div>Manager review · <b>{{ $c->manager_review_due->format('d M Y') }}</b></div>@endif
                                @if($c->hr_review_due)<div>HR review · <b>{{ $c->hr_review_due->format('d M Y') }}</b></div>@endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">No deadlines set.</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($trend->count() > 1)
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Performance trend</h3>
                <p class="text-xs text-gray-500 mb-3">Organisation-wide average across the last six cycles.</p>
                <div id="chartTrend"></div>
            </div>
        @endif

        @include('admin.dashboards._chartcss')
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const dark = document.body.classList.contains('dark');
                const grid = dark ? '#1b2e4b' : '#e0e6ed';
                const label = { style: { colors: dark ? '#888ea8' : '#3b3f5c' } };

                const deptEl = document.querySelector('#chartDepartments');
                if (deptEl) {
                    new ApexCharts(deptEl, {
                        chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [{ name: 'Average score', data: @json($byDepartment->pluck('average')->values()) }],
                        colors: ['#4361ee'],
                        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '62%' } },
                        dataLabels: { enabled: true, formatter: v => Number(v).toFixed(1), style: { fontSize: '11px' } },
                        xaxis: { categories: @json($byDepartment->keys()->values()), max: 100, labels: label },
                        yaxis: { labels: label },
                        grid: { borderColor: grid, strokeDashArray: 4 },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                    }).render();
                }

                const bandEl = document.querySelector('#chartBands');
                if (bandEl) {
                    new ApexCharts(bandEl, {
                        chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                        series: @json($bandSpread->values()),
                        labels: @json($bandSpread->keys()),
                        colors: ['#00ab55', '#4361ee', '#2196f3', '#e2a03f', '#f97316', '#e7515a'],
                        plotOptions: { pie: { donut: { size: '62%' } } },
                        dataLabels: { enabled: true, formatter: v => v.toFixed(0) + '%' },
                        legend: { position: 'bottom', labels: { colors: label.style.colors } },
                        stroke: { width: 0 },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                    }).render();
                }

                const trendEl = document.querySelector('#chartTrend');
                if (trendEl) {
                    new ApexCharts(trendEl, {
                        chart: { type: 'area', height: 300, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                        series: [{ name: 'Average score', data: @json($trend->map(fn($t) => round((float) $t->average, 2))->values()) }],
                        colors: ['#00ab55'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        markers: { size: 4 },
                        xaxis: { categories: @json($trend->pluck('cycle')->values()), labels: label, axisBorder: { show: false } },
                        yaxis: { min: 0, max: 100, labels: label },
                        grid: { borderColor: grid, strokeDashArray: 4 },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                    }).render();
                }
            });
        </script>
        @endpush
    @endif
</x-layout.admin>
