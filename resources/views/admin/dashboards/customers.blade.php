<x-layout.admin title="Customer Analytics">
    <x-admin.breadcrumb :items="[['label' => 'Customer Analytics']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Customer Analytics Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Segmentation, lifetime value, acquisition, receivables & churn risk.</p>
        </div>
        <a href="{{ route('admin.customers.create') }}" class="btn btn-sm btn-primary">+ Customer</a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['total'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['active'] }} active</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">With Orders</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['with_orders'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Converted</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">New (MTD)</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['new_mtd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">This month</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Churn Risk</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['churn_risk'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">No order in 6 months</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total Revenue</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['total_revenue'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">All invoices</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Inactive</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['inactive'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Need win-back</div>
        </div>
    </div>

    {{-- Acquisition trend + Concentration --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Customer Acquisition (12 months)</h5>
            <div id="chart-acquisition" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Revenue Concentration</h5>
            <div id="chart-concentration" style="min-height:320px;"></div>
            <div class="text-xs text-gray-500 text-center mt-1">Top 10 customers vs rest</div>
        </div>
    </div>

    {{-- Segmentation bubble + State distribution --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-1">Customer Purchase Frequency</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">How often customers buy, and what each segment is worth. Bar labels show customer count and revenue.</p>
            <div id="chart-segmentation" style="min-height:360px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">By State (Top 10)</h5>
            <div id="chart-states" style="min-height:360px;"></div>
        </div>
    </div>

    {{-- LTV scatter + Top customers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-1">Customer Loyalty Tiers</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Revenue contribution grouped by how long customers have been with you. Bar labels show customer count.</p>
            <div id="chart-loyalty-tiers" style="min-height:340px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Top 15 Customers by Revenue</h5>
            <div id="chart-top" style="min-height:340px;"></div>
        </div>
    </div>

    {{-- Aging heatmap --}}
    @if(count($custAging) > 0)
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Receivables Aging by Customer (Top 10 — ₹ balance)</h5>
        <div id="chart-aging-heatmap" style="min-height:{{ max(320, 36 * count($custAging) + 80) }}px;"></div>
    </div>
    @endif

    {{-- Recent customers --}}
    @if($recent->count() > 0)
    <div class="panel mb-6 dash-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold">Recent Customers</h5>
            <a href="{{ route('admin.customers.index') }}" class="text-primary text-xs hover:underline">All →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($recent as $c)
                <a href="{{ route('admin.customers.show', $c) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-primary/40 hover:shadow transition">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($c->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $c->name }}</div>
                        <div class="text-[11px] text-gray-500 truncate">{{ $c->company ?? $c->email ?? '—' }}</div>
                        <div class="text-[10px] text-gray-400">{{ $c->city }}{{ $c->state ? ', ' . $c->state : '' }}</div>
                    </div>
                </a>
            @endforeach
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

        // Acquisition column
        const acq = @json($acquisition);
        new ApexCharts(document.querySelector('#chart-acquisition'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'New Customers', data: acq.map(r => r.value) }],
            xaxis: { categories: acq.map(r => r.label) },
            plotOptions: { bar: { borderRadius:6, columnWidth:'55%' } },
            colors: [P.primary],
            fill: { type:'gradient', gradient:{ shade:'light', gradientToColors:[P.info], stops:[0,100] } },
            dataLabels: { enabled:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Concentration donut
        const conc = @json($concentration);
        new ApexCharts(document.querySelector('#chart-concentration'), {
            chart: { type:'donut', height:320, animations:anim, fontFamily:'inherit' },
            series: [Number(conc.top10 || 0), Number(conc.rest || 0)],
            labels: ['Top 10', 'Rest'],
            colors: [P.danger, P.info],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%',
                labels: { show:true, total: { show:true, label:'Total',
                    formatter: w => '₹' + Number(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toLocaleString('en-IN') } } } } },
            dataLabels: { enabled:true, formatter: v => Math.round(v) + '%' },
            legend: { position:'bottom' },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Customer purchase-frequency segments horizontal bar
        const seg = @json($segBubble);
        const segTotalRev = seg.reduce((s, r) => s + Number(r.revenue || 0), 0);
        new ApexCharts(document.querySelector('#chart-segmentation'), {
            chart: { type:'bar', height:360, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Revenue', data: seg.map(r => Number(r.revenue || 0)) }],
            xaxis: {
                categories: seg.map(r => r.label),
                labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') }
            },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'60%' } },
            colors: [P.danger, P.warning, P.info, P.success],
            dataLabels: {
                enabled: true, style:{ colors:['#fff'], fontWeight:600 },
                formatter: (v, { dataPointIndex }) => {
                    const r = seg[dataPointIndex];
                    return `${r.count} cust · ₹${Number(v).toLocaleString('en-IN')}`;
                }
            },
            legend: { show:false },
            tooltip: { custom: ({ dataPointIndex }) => {
                const r = seg[dataPointIndex];
                const pct = segTotalRev > 0 ? ((r.revenue / segTotalRev) * 100).toFixed(1) : '0.0';
                return `<div class="px-2 py-1"><b>${r.label}</b><br/>${r.count} customers<br/>₹${Number(r.revenue).toLocaleString('en-IN')} (${pct}% of revenue)</div>`;
            } }
        }).render();

        // State horizontal bar
        const st = @json($byState);
        new ApexCharts(document.querySelector('#chart-states'), {
            chart: { type:'bar', height:360, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Customers', data: st.map(r => Number(r.cnt)) }],
            xaxis: { categories: st.map(r => r.state) },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] } },
            legend: { show:false }
        }).render();

        // Customer loyalty tiers horizontal bar
        const tiers = @json($loyaltyTiers);
        const tierTotalRev = tiers.reduce((s, t) => s + Number(t.revenue || 0), 0);
        new ApexCharts(document.querySelector('#chart-loyalty-tiers'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Revenue', data: tiers.map(t => Number(t.revenue || 0)) }],
            xaxis: {
                categories: tiers.map(t => t.label),
                labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') }
            },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'60%' } },
            colors: [P.info, P.success, P.warning, P.primary],
            dataLabels: {
                enabled: true, style:{ colors:['#fff'], fontWeight:600 },
                formatter: (v, { dataPointIndex }) => {
                    const t = tiers[dataPointIndex];
                    return `${t.count} cust · ₹${Number(v).toLocaleString('en-IN')}`;
                }
            },
            legend: { show:false },
            tooltip: { custom: ({ dataPointIndex }) => {
                const t = tiers[dataPointIndex];
                const pct = tierTotalRev > 0 ? ((t.revenue / tierTotalRev) * 100).toFixed(1) : '0.0';
                return `<div class="px-2 py-1"><b>${t.label}</b><br/>${t.count} customers<br/>₹${Number(t.revenue).toLocaleString('en-IN')} (${pct}% of revenue)</div>`;
            } }
        }).render();

        // Top customers horizontal bar
        const tc = @json($topByRevenue);
        new ApexCharts(document.querySelector('#chart-top'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Revenue', data: tc.map(c => Number(c.invoices_sum_grand_total || 0)) }],
            xaxis: { categories: tc.map(c => c.name),
                labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981', '#06b6d4', '#e11d48', '#9333ea', '#0891b2', '#059669'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false }
        }).render();

        // Aging heatmap
        @if(count($custAging) > 0)
        const custAging = @json($custAging);
        const buckets = ['0-30','31-60','61-90','90+'];
        new ApexCharts(document.querySelector('#chart-aging-heatmap'), {
            chart: { type:'heatmap', height: {{ max(320, 36 * count($custAging) + 80) }},
                toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: custAging.map(r => ({
                name: r.name,
                data: buckets.map(b => ({ x: b, y: Math.round(r.buckets[b] || 0) }))
            })),
            colors: [P.danger],
            plotOptions: { heatmap: { radius:4, enableShades:true, shadeIntensity:0.55,
                colorScale: { ranges: [
                    { from:0, to:0,       color:'#f1f5f9', name:'None' },
                    { from:1, to:10000,   color:'#fecaca', name:'Low' },
                    { from:10001, to:50000, color:'#f87171', name:'Med' },
                    { from:50001, to:200000, color:'#dc2626', name:'High' },
                    { from:200001, to:99999999, color:'#7f1d1d', name:'Very High' },
                ] } } },
            dataLabels: { enabled:true, style:{ colors:['#fff'], fontSize:'11px' },
                formatter: v => v ? '₹' + Number(v).toLocaleString('en-IN') : '' }
        }).render();
        @endif
    });
    </script>
</x-layout.admin>
