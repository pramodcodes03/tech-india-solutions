<x-layout.admin title="Inventory Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Inventory Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Inventory Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Stock health, movements, valuation & reorder alerts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-primary">Inventory</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary">+ Product</a>
        </div>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">SKUs</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['total_skus'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['categories'] }} categories</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total Units</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['total_units'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['warehouses'] }} warehouse(s)</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Stock Value</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['total_value'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">At purchase price</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Below Reorder</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['below_reorder'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['reorder_pct'] }}% of SKUs</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Out of Stock</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['out_of_stock'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Action required</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Health Score</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ max(0, 100 - $kpi['reorder_pct']) }}">0</span>%</div>
            <div class="text-xs opacity-80 mt-1">Stock availability</div>
        </div>
    </div>

    {{-- Reorder gauge + Category treemap --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Reorder Health</h5>
            <div id="chart-reorder" style="min-height:320px;"></div>
            <div class="text-center text-xs text-gray-500 mt-2">% of SKUs at safe stock levels</div>
        </div>
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Stock Value by Category</h5>
            <div id="chart-cat-treemap" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Movement trend --}}
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Stock Movement — Last 30 Days (In vs Out)</h5>
        <div id="chart-movement" style="min-height:320px;"></div>
    </div>

    {{-- Movement heatmap (top products × months) --}}
    @if(count($movementMatrix) > 0)
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Movement Heatmap — Top 10 Products × Last 6 Months</h5>
        <div id="chart-heatmap" style="min-height:{{ max(320, 36 * count($movementMatrix) + 80) }}px;"></div>
    </div>
    @endif

    {{-- Current stock vs reorder + Warehouse --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Current Stock vs Reorder Level (top 15 low)</h5>
            <div id="chart-reorder-compare" style="min-height:400px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Warehouse Distribution</h5>
            <div id="chart-warehouse" style="min-height:400px;"></div>
        </div>
    </div>

    {{-- Top moving + Low stock list --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Top Moving Products (30 days)</h5>
            <div id="chart-topmoving" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Critical Low Stock</h5>
                <a href="{{ route('admin.inventory.low-stock') }}" class="text-primary text-xs hover:underline">All alerts →</a>
            </div>
            <ul class="space-y-2">
                @forelse($lowStockList as $p)
                    <li class="flex items-center justify-between p-2 rounded-lg bg-danger/5 hover:bg-danger/10">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold truncate">{{ $p->name }}</div>
                            <div class="text-[11px] text-gray-500 font-mono">{{ $p->code }} · {{ $p->cat_name ?? 'Uncategorized' }}</div>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-sm font-bold text-danger">{{ (int) $p->current_stock }}</div>
                            <div class="text-[10px] text-gray-400">reorder {{ (int) $p->reorder_level }}</div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 text-center py-6">All stock levels healthy 🎉</li>
                @endforelse
            </ul>
        </div>
    </div>

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

        // Reorder health gauge
        const healthy = {{ max(0, 100 - $kpi['reorder_pct']) }};
        new ApexCharts(document.querySelector('#chart-reorder'), {
            chart: { type:'radialBar', height:320, animations:anim, fontFamily:'inherit' },
            series: [healthy],
            plotOptions: { radialBar: {
                startAngle:-135, endAngle:135, hollow:{size:'65%'},
                track:{ background:'rgba(0,0,0,.05)' },
                dataLabels: {
                    name: { fontSize:'14px', offsetY:-5, color:'#888' },
                    value: { fontSize:'32px', fontWeight:800, formatter: v => v + '%' }
                }
            } },
            fill: { type:'gradient', gradient:{ shade:'dark', shadeIntensity:0.5,
                gradientToColors:[healthy >= 80 ? P.success : healthy >= 60 ? P.warning : P.danger], stops:[0,100] } },
            colors: [healthy >= 80 ? P.success : healthy >= 60 ? P.warning : P.danger],
            labels: ['Stock Health'],
            stroke: { lineCap:'round' }
        }).render();

        // Category treemap
        const cats = @json($byCategory);
        new ApexCharts(document.querySelector('#chart-cat-treemap'), {
            chart: { type:'treemap', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ data: cats.map(r => ({ x: r.name + ' (' + r.skus + ')', y: Number(r.value) })) }],
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            plotOptions: { treemap: { distributed:true, enableShades:false } },
            dataLabels: { enabled:true, style:{ fontSize:'12px', fontWeight:700 },
                formatter: (text, op) => [text, '₹' + Number(op.value).toLocaleString('en-IN')] },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Movement trend stacked area (in vs out negative)
        const mt = @json($movementTrend);
        new ApexCharts(document.querySelector('#chart-movement'), {
            chart: { type:'bar', height:320, stacked:false, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'In',  data: mt.map(r => r.in) },
                { name:'Out', data: mt.map(r => -r.out) },
            ],
            xaxis: { categories: mt.map(r => r.label), labels: { rotate:-45, style:{ fontSize:'10px' } } },
            yaxis: { labels: { formatter: v => Math.abs(v) } },
            plotOptions: { bar: { borderRadius:3, columnWidth:'75%' } },
            colors: [P.success, P.danger],
            dataLabels: { enabled:false },
            grid: { borderColor:'rgba(0,0,0,.05)' },
            legend: { position:'top' }
        }).render();

        // Movement heatmap
        @if(count($movementMatrix) > 0)
        const mm = @json($movementMatrix);
        new ApexCharts(document.querySelector('#chart-heatmap'), {
            chart: { type:'heatmap', height: {{ max(320, 36 * count($movementMatrix) + 80) }},
                toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: mm,
            colors: [P.primary],
            plotOptions: { heatmap: { radius:4, enableShades:true, shadeIntensity:0.55,
                colorScale: { ranges: [
                    { from:0, to:0, color:'#f3f4f6', name:'None' },
                    { from:1, to:20, color:'#bae6fd', name:'Low' },
                    { from:21, to:100, color:'#38bdf8', name:'Med' },
                    { from:101, to:500, color:'#0284c7', name:'High' },
                    { from:501, to:999999, color:'#0c4a6e', name:'Very High' },
                ] } } },
            dataLabels: { enabled:true, style:{ colors:['#111'], fontSize:'11px' } }
        }).render();
        @endif

        // Reorder compare grouped horizontal
        const rc = @json($reorderCompare);
        new ApexCharts(document.querySelector('#chart-reorder-compare'), {
            chart: { type:'bar', height:400, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Current', data: rc.map(r => r.current) },
                { name:'Reorder Level', data: rc.map(r => r.reorder) },
            ],
            xaxis: { categories: rc.map(r => r.name) },
            plotOptions: { bar: { horizontal:true, borderRadius:4, barHeight:'70%' } },
            colors: [P.danger, P.success],
            dataLabels: { enabled:false },
            legend: { position:'top' },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Warehouse pie
        const wh = @json($byWarehouse);
        new ApexCharts(document.querySelector('#chart-warehouse'), {
            chart: { type:'pie', height:400, animations:anim, fontFamily:'inherit' },
            series: wh.map(r => Math.max(0, Number(r.qty))),
            labels: wh.map(r => r.name),
            colors: [P.primary, P.info, P.success, P.warning, P.purple, P.pink, P.teal],
            stroke: { width:2, colors:['#fff'] },
            legend: { position:'bottom' },
            dataLabels: { enabled:true, formatter: v => Math.round(v) + '%' }
        }).render();

        // Top moving bar
        const tm = @json($topMoving);
        new ApexCharts(document.querySelector('#chart-topmoving'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Units Moved', data: tm.map(r => Number(r.qty)) }],
            xaxis: { categories: tm.map(r => r.name) },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal],
            dataLabels: { enabled:true, style:{ colors:['#fff'] } },
            legend: { show:false }
        }).render();
    });
    </script>
</x-layout.admin>
