<x-layout.admin title="Purchase Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Purchase Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Purchase & Vendor Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Procurement spend, vendor performance, receipts & aging.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-sm btn-outline-info">+ Vendor</a>
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-sm btn-primary">+ Purchase Order</a>
        </div>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total POs</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['po_total'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">All time</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Pending</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['po_pending'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Awaiting receipt</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Received</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['po_received'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Fully delivered</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">YTD Spend</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['po_value_ytd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">This fiscal year</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Active Vendors</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['vendors_active'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Supplier base</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Cancelled POs</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['po_cancelled'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Lost orders</div>
        </div>
    </div>

    {{-- Status flow + Concentration --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">PO Status — Count & Value</h5>
            <div id="chart-status" style="min-height:340px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Vendor Concentration</h5>
            <div id="chart-concentration" style="min-height:340px;"></div>
            <div class="text-xs text-gray-500 text-center mt-1">Top 5 vs rest of vendor base</div>
        </div>
    </div>

    {{-- Spend trend --}}
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Monthly Spend Trend (12 months)</h5>
        <div id="chart-spend" style="min-height:320px;"></div>
    </div>

    {{-- Top vendors + Aging --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Top 10 Vendors by Spend</h5>
            <div id="chart-topvendors" style="min-height:360px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Pending PO Aging</h5>
            <div id="chart-aging" style="min-height:360px;"></div>
        </div>
    </div>

    {{-- Vendor performance (delivery) --}}
    @if($vendorPerf->count() > 0)
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Vendor Delivery Performance — Avg Delay vs Expected (days)</h5>
        <div id="chart-perf" style="min-height:340px;"></div>
        <div class="text-xs text-gray-500 text-center mt-1">Negative = early · Positive = late. Target: 0 or below.</div>
    </div>
    @endif

    {{-- Recent POs table --}}
    @if($recentPos->count() > 0)
    <div class="panel mb-6 dash-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold">Recent Purchase Orders</h5>
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-primary text-xs hover:underline">All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-hover">
                <thead><tr><th>PO #</th><th>Vendor</th><th>Date</th><th>Expected</th><th>Status</th><th class="text-right">Value</th></tr></thead>
                <tbody>
                    @foreach($recentPos as $po)
                        <tr>
                            <td class="font-mono font-semibold text-primary"><a href="{{ route('admin.purchase-orders.show', $po) }}">{{ $po->po_number }}</a></td>
                            <td>{{ $po->vendor?->name ?? '—' }}</td>
                            <td>{{ $po->po_date?->format('d M Y') }}</td>
                            <td>{{ $po->expected_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-semibold',
                                    'bg-success/10 text-success' => $po->status === 'received',
                                    'bg-info/10 text-info' => in_array($po->status, ['confirmed']),
                                    'bg-warning/10 text-warning' => in_array($po->status, ['draft', 'pending']),
                                    'bg-danger/10 text-danger' => $po->status === 'cancelled',
                                ])>{{ ucfirst($po->status) }}</span>
                            </td>
                            <td class="text-right font-semibold">&#8377;{{ number_format($po->grand_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @include('admin.dashboards._chartcss')

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-count]').forEach(function (el) {
            const target = parseFloat(el.dataset.count) || 0;
            const dur = 1200, start = performance.now();
            function tick(now) {
                const p = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased).toLocaleString('en-IN');
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });

        const P = { primary:'#4361ee', info:'#2196f3', success:'#00ab55', warning:'#e2a03f',
                    danger:'#e7515a', purple:'#805dca', pink:'#e95f9b', teal:'#00c4b4' };
        const anim = { enabled:true, easing:'easeinout', speed:900,
            animateGradually:{ enabled:true, delay:150 }, dynamicAnimation:{ enabled:true, speed:450 } };

        // Status dual-axis column
        const flow = @json($flow);
        new ApexCharts(document.querySelector('#chart-status'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Count', type:'column', data: flow.map(r => Number(r.cnt)) },
                { name:'Value (₹)', type:'line', data: flow.map(r => Number(r.total)) },
            ],
            stroke: { width:[0, 3], curve:'smooth' },
            xaxis: { categories: flow.map(r => (r.status || '').charAt(0).toUpperCase() + (r.status || '').slice(1)) },
            yaxis: [
                { title: { text:'Count' } },
                { opposite:true, title: { text:'Value' },
                  labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
            ],
            plotOptions: { bar: { borderRadius:6, columnWidth:'45%', distributed:true } },
            colors: [P.primary, P.purple],
            dataLabels: { enabled:false },
            legend: { position:'top' },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Concentration donut
        const conc = @json($concentration);
        new ApexCharts(document.querySelector('#chart-concentration'), {
            chart: { type:'donut', height:340, animations:anim, fontFamily:'inherit' },
            series: [Number(conc.top5 || 0), Number(conc.rest || 0)],
            labels: ['Top 5 Vendors', 'Rest'],
            colors: [P.danger, P.info],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%',
                labels: { show:true, total: { show:true, label:'Total',
                    formatter: w => '₹' + Number(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toLocaleString('en-IN') } } } } },
            dataLabels: { enabled:true, formatter: v => Math.round(v) + '%' },
            legend: { position:'bottom' },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Spend trend area
        const sp = @json($spendTrend);
        new ApexCharts(document.querySelector('#chart-spend'), {
            chart: { type:'area', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'PO Spend', data: sp.map(r => r.value) }],
            xaxis: { categories: sp.map(r => r.label) },
            colors: [P.purple],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.45, opacityTo:0.05, stops:[0,90,100] } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            markers: { size:4, strokeWidth:2, strokeColors:'#fff', hover: { size:6 } }
        }).render();

        // Top vendors horizontal bar
        const tv = @json($topVendors);
        new ApexCharts(document.querySelector('#chart-topvendors'), {
            chart: { type:'bar', height:360, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Spend', data: tv.map(v => Number(v.po_value || 0)) }],
            xaxis: { categories: tv.map(v => v.name), labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] }, formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Aging polar area
        const ag = @json((object) $poAging);
        new ApexCharts(document.querySelector('#chart-aging'), {
            chart: { type:'polarArea', height:360, animations:anim, fontFamily:'inherit' },
            series: Object.values(ag),
            labels: Object.keys(ag).map(k => k + ' days'),
            colors: [P.success, P.warning, '#e97316', P.danger],
            stroke: { colors:['#fff'] },
            fill: { opacity:0.85 },
            legend: { position:'bottom' },
            yaxis: { show:false }
        }).render();

        // Vendor delivery perf column
        @if($vendorPerf->count() > 0)
        const vp = @json($vendorPerf);
        new ApexCharts(document.querySelector('#chart-perf'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Avg delay (days)', data: vp.map(r => Math.round(r.avg_delay || 0)) }],
            xaxis: { categories: vp.map(r => r.name) },
            plotOptions: { bar: { borderRadius:6, columnWidth:'45%', colors: {
                ranges: [
                    { from:-1000, to:0, color: P.success },
                    { from:0.01, to:3, color: P.warning },
                    { from:3.01, to:1000, color: P.danger },
                ]
            } } },
            dataLabels: { enabled:true, style:{ colors:['#fff'] }, formatter: v => (v > 0 ? '+' : '') + v + 'd' },
            grid: { borderColor:'rgba(0,0,0,.05)' },
            tooltip: { y: { formatter: v => (v > 0 ? '+' : '') + v + ' days' } }
        }).render();
        @endif
    });
    </script>
</x-layout.admin>
