@php
    use App\Models\VisitorLog;
    $t = $data['totals'];
@endphp

<x-layout.admin title="Visitor Analytics">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Daily Visitor', 'url' => route('admin.hr.trackers.visitors.index')],
        ['label' => 'Analytics'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Visitor Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $filter->label() }}</p>
        </div>
        <a href="{{ route('admin.hr.trackers.visitors.index', $filter->toQuery()) }}" class="btn btn-outline-secondary">Back to Register</a>
    </div>

    <x-tracker.filter-bar :filter="$filter" :show-search="false" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-4 dash-animate">
        <x-tracker.stat label="Visitors" :value="number_format($t['visits'])" tone="primary" />
        <x-tracker.stat label="Attended" :value="number_format($t['attended'])" tone="success" :sub="$t['attendance_rate'].'% turned up'" />
        <x-tracker.stat label="Selected" :value="number_format($t['selected'])" tone="info" />
        <x-tracker.stat label="Joined" :value="number_format($t['joined'])" tone="secondary" />
        <x-tracker.stat label="Conversion" :value="$t['conversion'].'%'" tone="warning" sub="selected ÷ attended" />
    </div>

    @if($t['visits'] === 0)
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">Nothing to analyse for {{ $filter->label() }}.</div>
            <div class="text-xs mt-1">Log a few visitors, or widen the period above.</div>
        </div>
    @else
        {{-- Funnel: logged → turned up → selected → joined. --}}
        <div class="panel p-5 mb-4">
            <h3 class="font-bold mb-1">Interview conversion</h3>
            <p class="text-xs text-gray-500 mb-4">How many of the people logged made it through each stage.</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @php
                    $stages = [
                        ['Logged', $t['visits'], 'bg-primary', 100],
                        ['Turned up', $t['attended'], 'bg-success', $t['visits'] ? round($t['attended'] / $t['visits'] * 100) : 0],
                        ['Selected', $t['selected'], 'bg-info', $t['visits'] ? round($t['selected'] / $t['visits'] * 100) : 0],
                        ['Joined', $t['joined'], 'bg-secondary', $t['visits'] ? round($t['joined'] / $t['visits'] * 100) : 0],
                    ];
                @endphp
                @foreach($stages as [$stageLabel, $count, $colour, $percent])
                    <div class="rounded-xl border border-gray-100 dark:border-[#1b2e4b] p-4">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $stageLabel }}</div>
                        <div class="text-2xl font-extrabold mt-0.5">{{ number_format($count) }}</div>
                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden mt-2">
                            <div class="h-full rounded-full {{ $colour }}" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="text-[11px] text-gray-500 mt-1">{{ $percent }}% of everyone logged</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
            <div class="panel p-5 xl:col-span-2">
                <h3 class="font-bold mb-1">Visitors by day</h3>
                <p class="text-xs text-gray-500 mb-3">Daily footfall across the selected period.</p>
                <div id="chartByDay"></div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">By purpose</h3>
                <p class="text-xs text-gray-500 mb-3">Why people came in.</p>
                <div id="chartPurpose"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">By source</h3>
                    <p class="text-xs text-gray-500">Which channel produces candidates that convert.</p>
                </div>
                <div class="overflow-x-auto max-h-[400px]">
                    <table class="table-striped">
                        <thead><tr><th>Source</th><th class="text-right">Visitors</th><th class="text-right">Converted</th><th class="text-right">Rate</th></tr></thead>
                        <tbody>
                            @foreach($data['by_source'] as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row->label }}</td>
                                    <td class="text-right">{{ $row->total }}</td>
                                    <td class="text-right">{{ $row->converted }}</td>
                                    <td class="text-right font-bold {{ $row->converted > 0 ? 'text-success' : 'text-gray-400' }}">
                                        {{ $row->total > 0 ? round($row->converted / $row->total * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Availability &amp; outcome</h3>
                <p class="text-xs text-gray-500 mb-4">Where the log currently stands.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Availability</div>
                        @foreach($data['by_status'] as $row)
                            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 dark:border-[#1b2e4b] last:border-0">
                                <span class="text-sm">{{ VisitorLog::STATUSES[$row->label] ?? ucfirst((string) $row->label) }}</span>
                                <span class="font-bold">{{ $row->total }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Outcome</div>
                        @foreach($data['by_outcome'] as $row)
                            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 dark:border-[#1b2e4b] last:border-0">
                                <span class="text-sm">{{ VisitorLog::OUTCOMES[$row->label] ?? ucfirst((string) $row->label) }}</span>
                                <span class="font-bold">{{ $row->total }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
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

                new ApexCharts(document.querySelector('#chartByDay'), {
                    chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [{ name: 'Visitors', data: @json($data['by_day']->pluck('total')->map(fn($v) => (int) $v)->values()) }],
                    colors: ['#4361ee'],
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: @json($data['by_day']->pluck('visit_date')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))->values()), labels: label, axisBorder: { show: false } },
                    yaxis: { labels: label },
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();

                new ApexCharts(document.querySelector('#chartPurpose'), {
                    chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                    series: @json($data['by_purpose']->pluck('total')->map(fn($v) => (int) $v)->values()),
                    labels: @json($data['by_purpose']->pluck('label')->values()),
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
