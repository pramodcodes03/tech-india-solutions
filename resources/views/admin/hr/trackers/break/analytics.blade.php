@php
    use App\Models\BreakSheet;
    $t = $data['totals'];
@endphp

<x-layout.admin title="Break Sheet Analytics">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Break Sheet', 'url' => route('admin.hr.trackers.break.index')],
        ['label' => 'Analytics'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Break Sheet Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $filter->label() }}</p>
        </div>
        <a href="{{ route('admin.hr.trackers.break.index', $filter->toQuery()) }}" class="btn btn-outline-secondary">Back to Register</a>
    </div>

    <x-tracker.filter-bar :filter="$filter" :show-search="false" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-4 dash-animate">
        <x-tracker.stat label="Total Breaks" :value="number_format($t['entries'])" tone="primary" />
        <x-tracker.stat label="Employees" :value="number_format($t['employees'])" tone="info" />
        <x-tracker.stat label="Total Time" :value="BreakSheet::formatMinutes($t['minutes'])" tone="warning" />
        <x-tracker.stat label="Average Break" :value="BreakSheet::formatMinutes($t['avg_minutes'])" tone="success"
                        :sub="$t['entries'] ? 'across '.number_format($t['entries']).' breaks' : null" />
        <x-tracker.stat label="Per Day" :value="BreakSheet::formatMinutes($t['avg_per_day'])" tone="secondary"
                        sub="total break time per day" />
    </div>

    @if($t['entries'] === 0)
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">Nothing to analyse for {{ $filter->label() }}.</div>
            <div class="text-xs mt-1">Record a few break entries, or widen the period above.</div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
            <div class="panel p-5 xl:col-span-2">
                <h3 class="font-bold mb-1">Daily break totals</h3>
                <p class="text-xs text-gray-500 mb-3">Total minutes of break taken each day, and how many breaks that was.</p>
                <div id="chartDaily"></div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Break type mix</h3>
                <p class="text-xs text-gray-500 mb-3">Share of breaks by type.</p>
                <div id="chartTypes"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
            <div class="panel p-0 xl:col-span-2">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Total break time — per employee, per day</h3>
                    <p class="text-xs text-gray-500">Every break an employee took on a date, added into one figure — the combined total HR reads off the sheet.</p>
                </div>
                <div class="overflow-x-auto max-h-[420px]">
                    <table class="table-striped">
                        <thead><tr><th>Date</th><th>Employee</th><th>Department</th><th class="text-right">Breaks</th><th class="text-right">First out</th><th class="text-right">Last in</th><th class="text-right">Total Break Timing</th></tr></thead>
                        <tbody>
                            @forelse($data['per_employee_day'] as $row)
                                <tr>
                                    <td class="whitespace-nowrap text-xs">{{ \Carbon\Carbon::parse($row->break_date)->format('d M Y') }}</td>
                                    <td>
                                        <div class="font-semibold">{{ $row->employee_name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">{{ $row->employee_code }}</div>
                                    </td>
                                    <td class="text-xs">{{ $row->department ?? '—' }}</td>
                                    <td class="text-right">{{ $row->entries }}</td>
                                    <td class="text-right font-mono text-xs">{{ substr((string) $row->first_out, 0, 5) }}</td>
                                    <td class="text-right font-mono text-xs">{{ substr((string) $row->last_in, 0, 5) }}</td>
                                    <td class="text-right font-extrabold text-primary">{{ BreakSheet::formatMinutes($row->total_minutes) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-gray-500 py-8">No breaks in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Average break duration — per employee</h3>
                    <p class="text-xs text-gray-500">Sorted by total time spent on break.</p>
                </div>
                <div class="overflow-x-auto max-h-[420px]">
                    <table class="table-striped">
                        <thead><tr><th>Employee</th><th>Department</th><th class="text-right">Breaks</th><th class="text-right">Average</th><th class="text-right">Longest</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach($data['per_employee'] as $row)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $row->employee_name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">{{ $row->employee_code }}</div>
                                    </td>
                                    <td class="text-xs">{{ $row->department ?? '—' }}</td>
                                    <td class="text-right">{{ $row->entries }}</td>
                                    <td class="text-right font-semibold">{{ BreakSheet::formatMinutes($row->avg_minutes) }}</td>
                                    <td class="text-right">{{ BreakSheet::formatMinutes($row->longest_minutes) }}</td>
                                    <td class="text-right font-bold">{{ BreakSheet::formatMinutes($row->total_minutes) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Per department</h3>
                    <p class="text-xs text-gray-500">Where break time is concentrated.</p>
                </div>
                <div class="overflow-x-auto max-h-[420px]">
                    <table class="table-striped">
                        <thead><tr><th>Department</th><th class="text-right">Employees</th><th class="text-right">Breaks</th><th class="text-right">Average</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach($data['per_department'] as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row->department }}</td>
                                    <td class="text-right">{{ $row->employees }}</td>
                                    <td class="text-right">{{ $row->entries }}</td>
                                    <td class="text-right font-semibold">{{ BreakSheet::formatMinutes($row->avg_minutes) }}</td>
                                    <td class="text-right font-bold">{{ BreakSheet::formatMinutes($row->total_minutes) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel p-0">
            <div class="p-5 pb-3">
                <h3 class="font-bold">Ten longest breaks</h3>
                <p class="text-xs text-gray-500">The outliers worth a conversation.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table-striped">
                    <thead><tr><th>Date</th><th>Employee</th><th>Type</th><th>Out</th><th>In</th><th class="text-right">Duration</th></tr></thead>
                    <tbody>
                        @foreach($data['longest'] as $row)
                            <tr>
                                <td class="whitespace-nowrap">{{ $row->break_date->format('d M Y') }}</td>
                                <td class="font-semibold">{{ $row->employee?->full_name ?? '—' }}</td>
                                <td class="text-xs">{{ $row->breakType?->name ?? '—' }}</td>
                                <td class="font-mono">{{ substr((string) $row->out_time, 0, 5) }}</td>
                                <td class="font-mono">{{ substr((string) $row->in_time, 0, 5) }}</td>
                                <td class="text-right font-bold text-danger">{{ $row->duration_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @include('admin.dashboards._chartcss')
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const dark = document.body.classList.contains('dark');
                const grid = dark ? '#1b2e4b' : '#e0e6ed';
                const label = { style: { colors: dark ? '#888ea8' : '#3b3f5c' } };

                new ApexCharts(document.querySelector('#chartDaily'), {
                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                    series: [
                        { name: 'Break minutes', type: 'area', data: @json($data['daily']->pluck('total_minutes')->map(fn($v) => (int) $v)->values()) },
                        { name: 'Breaks', type: 'line', data: @json($data['daily']->pluck('entries')->map(fn($v) => (int) $v)->values()) },
                    ],
                    colors: ['#4361ee', '#e2a03f'],
                    stroke: { curve: 'smooth', width: [2, 2] },
                    fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: @json($data['daily']->pluck('break_date')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))->values()), labels: label, axisBorder: { show: false } },
                    yaxis: [
                        { title: { text: 'Minutes', style: label.style }, labels: label },
                        { opposite: true, title: { text: 'Breaks', style: label.style }, labels: label },
                    ],
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    legend: { labels: { colors: label.style.colors } },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();

                new ApexCharts(document.querySelector('#chartTypes'), {
                    chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                    series: @json($data['by_type']->pluck('entries')->map(fn($v) => (int) $v)->values()),
                    labels: @json($data['by_type']->pluck('break_type')->values()),
                    colors: ['#4361ee', '#00ab55', '#e2a03f', '#e7515a', '#2196f3', '#805dca', '#00c0ef'],
                    plotOptions: { pie: { donut: { size: '62%' } } },
                    dataLabels: { enabled: true, formatter: (v) => v.toFixed(0) + '%' },
                    legend: { position: 'bottom', labels: { colors: label.style.colors } },
                    stroke: { width: 0 },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();
            });
        </script>
        @endpush
    @endif
</x-layout.admin>
