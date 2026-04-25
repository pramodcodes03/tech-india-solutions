<x-layout.admin title="Executive Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Executive Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Executive & Finance Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Top-level KPIs · cash flow · margin · working capital.</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-primary">Reports</a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Revenue (MTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['rev_mtd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">YTD: ₹{{ number_format($kpi['rev_ytd']) }}</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Collected (MTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['pay_mtd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Cash received</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">PO Spend (MTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['po_mtd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Procurement outflow</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Receivables</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['receivables'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Pending collection</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Overdue</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['overdue'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Past due date</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Net Cash (MTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['net_cash_flow'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Paid – PO spend</div>
        </div>
    </div>

    {{-- Revenue vs target gauge + Cash flow line --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Revenue vs Target (MTD)</h5>
            <div id="chart-target" style="min-height:340px;"></div>
            <div class="text-xs text-gray-500 text-center mt-1">Target: ₹{{ number_format($target) }} (110% of last month)</div>
        </div>
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Cash Flow — Invoiced · Collected · PO Spend (12 months)</h5>
            <div id="chart-cashflow" style="min-height:340px;"></div>
        </div>
    </div>

    {{-- Margin trend + Receivables aging --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Gross Margin Trend (6 months)</h5>
            <div id="chart-margin" style="min-height:340px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Receivables Aging</h5>
            <div id="chart-aging" style="min-height:340px;"></div>
        </div>
    </div>

    {{-- Working capital + Payment modes --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Working Capital — Receivables vs Payables (6 months)</h5>
            <div id="chart-workingcap" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Payment Modes (6 months)</h5>
            <div id="chart-modes" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Top margin products --}}
    @if($topMargin->count() > 0)
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Top 10 Products by Margin</h5>
        <div id="chart-margin-products" style="min-height:360px;"></div>
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

        // Target gauge
        const targetPct = {{ $targetPct }};
        new ApexCharts(document.querySelector('#chart-target'), {
            chart: { type:'radialBar', height:340, animations:anim, fontFamily:'inherit' },
            series: [targetPct],
            plotOptions: { radialBar: {
                startAngle:-135, endAngle:135, hollow:{size:'65%'},
                track:{ background:'rgba(0,0,0,.05)' },
                dataLabels: {
                    name: { fontSize:'14px', offsetY:-5, color:'#888' },
                    value: { fontSize:'32px', fontWeight:800, formatter: v => v + '%' }
                }
            } },
            fill: { type:'gradient', gradient:{ shade:'dark', shadeIntensity:0.5,
                gradientToColors:[targetPct >= 90 ? P.success : targetPct >= 60 ? P.warning : P.danger], stops:[0,100] } },
            colors: [targetPct >= 90 ? P.success : targetPct >= 60 ? P.warning : P.danger],
            labels: ['of Target'],
            stroke: { lineCap:'round' }
        }).render();

        // Cash flow line (3 series)
        const cf = @json($cashFlow);
        new ApexCharts(document.querySelector('#chart-cashflow'), {
            chart: { type:'line', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Invoiced', data: cf.map(r => r.invoiced) },
                { name:'Collected', data: cf.map(r => r.paid) },
                { name:'PO Spend', data: cf.map(r => r.po) },
            ],
            xaxis: { categories: cf.map(r => r.label) },
            stroke: { curve:'smooth', width:[3, 3, 3] },
            colors: [P.primary, P.success, P.purple],
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            markers: { size:4, strokeWidth:2, strokeColors:'#fff', hover: { size:7 } },
            legend: { position:'top' },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Margin trend (mixed: column revenue/cost + line margin %)
        const mg = @json($marginTrend);
        new ApexCharts(document.querySelector('#chart-margin'), {
            chart: { type:'line', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Revenue', type:'column', data: mg.map(r => r.revenue) },
                { name:'Cost',    type:'column', data: mg.map(r => r.cost) },
                { name:'Margin %',type:'line',   data: mg.map(r => r.pct) },
            ],
            stroke: { width:[0, 0, 3], curve:'smooth' },
            xaxis: { categories: mg.map(r => r.label) },
            yaxis: [
                { title: { text:'₹ Value' }, labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
                { show:false },
                { opposite:true, title: { text:'Margin %' }, labels: { formatter: v => v + '%' } }
            ],
            plotOptions: { bar: { borderRadius:4, columnWidth:'55%' } },
            colors: [P.primary, P.danger, P.success],
            dataLabels: { enabled:false },
            legend: { position:'top' },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Aging stacked bar
        const ag = @json((object) $aging);
        new ApexCharts(document.querySelector('#chart-aging'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Balance', data: Object.values(ag) }],
            xaxis: { categories: Object.keys(ag).map(k => k + ' days') },
            plotOptions: { bar: { borderRadius:6, columnWidth:'45%', distributed:true } },
            colors: [P.success, P.warning, '#e97316', P.danger],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Working capital dual line
        const wc = @json($workingCap);
        new ApexCharts(document.querySelector('#chart-workingcap'), {
            chart: { type:'area', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Receivables', data: wc.map(r => r.receivables) },
                { name:'Payables',    data: wc.map(r => r.payables) },
            ],
            xaxis: { categories: wc.map(r => r.label) },
            colors: [P.success, P.danger],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient: { opacityFrom:0.4, opacityTo:0.05 } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            legend: { position:'top' },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 }
        }).render();

        // Payment modes donut
        const pm = @json((object) $paymentModes);
        const pmKeys = Object.keys(pm);
        new ApexCharts(document.querySelector('#chart-modes'), {
            chart: { type:'donut', height:320, animations:anim, fontFamily:'inherit' },
            series: pmKeys.length ? Object.values(pm).map(v => Number(v)) : [0],
            labels: pmKeys.length ? pmKeys.map(k => k.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())) : ['No data'],
            colors: [P.primary, P.info, P.success, P.warning, P.purple, P.pink, P.teal],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%',
                labels: { show:true, total: { show:true, label:'Total',
                    formatter: w => '₹' + Number(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toLocaleString('en-IN') } } } } },
            dataLabels: { enabled:true, formatter: v => Math.round(v) + '%' },
            legend: { position:'bottom' },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Top margin products
        @if($topMargin->count() > 0)
        const tm = @json($topMargin);
        new ApexCharts(document.querySelector('#chart-margin-products'), {
            chart: { type:'bar', height:360, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Margin', data: tm.map(r => Number(r.margin || 0)) }],
            xaxis: { categories: tm.map(r => r.name),
                labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.success, P.teal, P.info, P.primary, P.purple, P.pink, P.warning, '#f59e0b', '#10b981', '#06b6d4'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false }
        }).render();
        @endif
    });
    </script>
</x-layout.admin>
