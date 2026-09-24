@php $t = $data['totals']; $b = $data['budget']; @endphp

<x-layout.admin title="Diesel Analytics">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Diesel', 'url' => route('admin.hr.trackers.diesel.index')],
        ['label' => 'Analytics'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Diesel Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $filter->label() }}</p>
        </div>
        <a href="{{ route('admin.hr.trackers.diesel.index', $filter->toQuery()) }}" class="btn btn-outline-secondary">Back to Register</a>
    </div>

    <x-tracker.filter-bar :filter="$filter" :show-search="false" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-4 dash-animate">
        <x-tracker.stat label="Entries" :value="number_format($t['entries'])" tone="primary" />
        <x-tracker.stat label="Quantity" :value="number_format($t['quantity'], 2).' L'" tone="info" />
        <x-tracker.stat label="Spend" :value="'₹'.number_format($t['amount'], 2)" tone="warning" />
        <x-tracker.stat label="Average Rate" :value="$t['avg_rate'] > 0 ? '₹'.number_format($t['avg_rate'], 2).' / L' : '—'" tone="success" />
        <x-tracker.stat
            label="Budget Remaining"
            :value="$b['has_budget'] ? '₹'.number_format($b['remaining'], 2) : 'Not set'"
            :tone="$b['has_budget'] ? ($b['remaining'] < 0 ? 'danger' : 'success') : 'secondary'"
            :sub="$b['has_budget'] ? $b['percent'].'% of allocation used' : null" />
    </div>

    @if($t['entries'] === 0)
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">Nothing to analyse for {{ $filter->label() }}.</div>
            <div class="text-xs mt-1">Record a few diesel entries, or widen the period above.</div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Consumption trend</h3>
                <p class="text-xs text-gray-500 mb-3">Litres and spend across the selected period.</p>
                <div id="chartTrend"></div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Cost per litre movement</h3>
                <p class="text-xs text-gray-500 mb-3">Effective rate paid, derived from amount ÷ quantity.</p>
                <div id="chartRate"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="panel p-5 xl:col-span-2">
                <h3 class="font-bold mb-1">Month-on-month comparison</h3>
                <p class="text-xs text-gray-500 mb-3">The last six months, regardless of the filter above.</p>
                <div id="chartMoM"></div>
            </div>

            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">By vehicle</h3>
                    <p class="text-xs text-gray-500">Where the fuel went.</p>
                </div>
                <div class="overflow-x-auto max-h-[380px]">
                    <table class="table-striped">
                        <thead><tr><th>Vehicle</th><th class="text-right">Entries</th><th class="text-right">Litres</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @foreach($data['by_vehicle'] as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row->vehicle_no }}</td>
                                    <td class="text-right">{{ $row->entries }}</td>
                                    <td class="text-right">{{ number_format((float) $row->quantity, 2) }}</td>
                                    <td class="text-right font-bold">₹{{ number_format((float) $row->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                const buckets = @json($data['trend']->pluck('bucket')->map(fn($b) => strlen((string) $b) === 7
                    ? \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) $b)->format('M Y')
                    : \Illuminate\Support\Carbon::parse((string) $b)->format('d M'))->values());

                new ApexCharts(document.querySelector('#chartTrend'), {
                    chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [
                        { name: 'Litres', type: 'column', data: @json($data['trend']->pluck('quantity')->map(fn($v) => round((float) $v, 2))->values()) },
                        { name: 'Amount (₹)', type: 'line', data: @json($data['trend']->pluck('amount')->map(fn($v) => round((float) $v, 2))->values()) },
                    ],
                    colors: ['#e2a03f', '#4361ee'],
                    stroke: { width: [0, 2], curve: 'smooth' },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: buckets, labels: label, axisBorder: { show: false } },
                    yaxis: [
                        { title: { text: 'Litres', style: label.style }, labels: label },
                        { opposite: true, title: { text: 'Amount', style: label.style }, labels: label },
                    ],
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    legend: { labels: { colors: label.style.colors } },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();

                new ApexCharts(document.querySelector('#chartRate'), {
                    chart: { type: 'line', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [{ name: '₹ per litre', data: @json($data['rate_movement']->pluck('rate')->values()) }],
                    colors: ['#00ab55'],
                    stroke: { curve: 'smooth', width: 3 },
                    markers: { size: 4 },
                    dataLabels: { enabled: false },
                    xaxis: { categories: buckets, labels: label, axisBorder: { show: false } },
                    yaxis: { labels: { ...label, formatter: (v) => '₹' + Number(v).toFixed(2) } },
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();

                new ApexCharts(document.querySelector('#chartMoM'), {
                    chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    series: [
                        { name: 'Litres', data: @json($data['month_on_month']->pluck('quantity')->map(fn($v) => round((float) $v, 2))->values()) },
                        { name: 'Amount (₹)', data: @json($data['month_on_month']->pluck('amount')->map(fn($v) => round((float) $v, 2))->values()) },
                    ],
                    colors: ['#805dca', '#4361ee'],
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: @json($data['month_on_month']->pluck('bucket')->map(fn($b) => \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) $b)->format('M Y'))->values()),
                        labels: label, axisBorder: { show: false },
                    },
                    yaxis: { labels: label },
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    legend: { labels: { colors: label.style.colors } },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                }).render();
            });
        </script>
        @endpush
    @endif
</x-layout.admin>
