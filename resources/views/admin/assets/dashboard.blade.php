<x-layout.admin title="Asset Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Asset Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Register health, depreciation, maintenance & warranty alerts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.assets.assets.create') }}" class="btn btn-sm btn-primary">+ Asset</a>
            <a href="{{ route('admin.assets.depreciation.index') }}" class="btn btn-sm btn-outline-warning">Depreciation Run</a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total Assets</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['total'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Across all locations</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total Cost</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['value'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">At purchase price</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Book Value</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['book_value'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">After depreciation</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Accumulated Dep.</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['depreciation'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Lifetime</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Maint. (YTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['maint_cost_ytd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Total spend YTD</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Warranty &lt;60d</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['warranty_soon'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['lost'] }} lost</div>
        </div>
    </div>

    {{-- Status donut + Category treemap --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Lifecycle Status</h5>
            <div id="chart-status" style="min-height:320px;"></div>
        </div>
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Asset Value by Category</h5>
            <div id="chart-cat-treemap" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Depreciation forecast + Maintenance trend --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Book Value Forecast — Next 12 Months</h5>
            <div id="chart-forecast" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Maintenance Cost Trend (12 months)</h5>
            <div id="chart-maint-trend" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Top maintenance assets + Maintenance type donut --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Top 10 Assets by Maintenance Cost</h5>
            <div id="chart-top-maint" style="min-height:340px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Maintenance Type</h5>
            <div id="chart-maint-type" style="min-height:340px;"></div>
        </div>
    </div>

    {{-- Location pie + Condition + Warranty list --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Location Distribution</h5>
            <div id="chart-location" style="min-height:300px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Condition Rating</h5>
            <div id="chart-condition" style="min-height:300px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Warranty Expiring</h5>
            <ul class="space-y-2">
                @forelse($warranties as $w)
                    @php $days = $w->warranty_expiry_date->diffInDays(now(), false); @endphp
                    <li class="flex items-center justify-between p-2 rounded-lg {{ $days < 0 ? 'bg-warning/10' : 'bg-danger/10' }}">
                        <div class="min-w-0">
                            <a href="{{ route('admin.assets.assets.show', $w) }}" class="text-sm font-semibold text-primary hover:underline truncate block">{{ $w->name }}</a>
                            <div class="text-[11px] text-gray-500">{{ $w->category?->name }} · {{ $w->location?->name ?? '—' }}</div>
                        </div>
                        <div class="text-right ml-2">
                            <div class="text-xs font-semibold">{{ $w->warranty_expiry_date->format('d M Y') }}</div>
                            <div class="text-[10px] {{ $days < 0 ? 'text-warning' : 'text-danger' }}">
                                @if($days < 0) in {{ abs($days) }}d
                                @elseif($days === 0) today
                                @else {{ $days }}d ago
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 text-center py-6">All warranties healthy 🎉</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Recent assignments --}}
    @if($recentAssignments->count() > 0)
    <div class="panel mb-6 dash-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold">Recent Custody Changes</h5>
            <a href="{{ route('admin.assets.assignments.index') }}" class="text-primary text-xs hover:underline">All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-hover">
                <thead><tr><th>Code</th><th>Asset</th><th>Action</th><th>Employee</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($recentAssignments as $a)
                        <tr>
                            <td class="font-mono">{{ $a->assignment_code }}</td>
                            <td><a href="{{ route('admin.assets.assets.show', $a->asset) }}" class="text-primary hover:underline">{{ $a->asset->asset_code }} · {{ $a->asset->name }}</a></td>
                            <td class="capitalize">{{ $a->action_type }}</td>
                            <td>{{ $a->employee?->full_name ?? '—' }}</td>
                            <td>{{ $a->assigned_at?->format('d M Y') }}</td>
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

        // Status donut
        const st = @json((object) $byStatus);
        const stKeys = Object.keys(st);
        new ApexCharts(document.querySelector('#chart-status'), {
            chart: { type:'donut', height:320, animations:anim, fontFamily:'inherit' },
            series: stKeys.length ? stKeys.map(k => Number(st[k])) : [0],
            labels: stKeys.length ? stKeys.map(k => k.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())) : ['No data'],
            colors: [P.info, P.success, P.warning, P.danger, P.purple, P.pink],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%',
                labels: { show:true, total: { show:true, label:'Total',
                    formatter: w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) } } } } },
            legend: { position:'bottom' }
        }).render();

        // Category treemap (book value)
        const cats = @json($byCategory);
        new ApexCharts(document.querySelector('#chart-cat-treemap'), {
            chart: { type:'treemap', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ data: cats.map(r => ({ x: (r.name || 'Uncategorized') + ' (' + r.cnt + ')', y: Number(r.book_value) })) }],
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            plotOptions: { treemap: { distributed:true, enableShades:false } },
            dataLabels: { enabled:true, style:{ fontSize:'12px', fontWeight:700 },
                formatter: (text, op) => [text, '₹' + Number(op.value).toLocaleString('en-IN')] },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Forecast area
        const fc = @json($forecast);
        new ApexCharts(document.querySelector('#chart-forecast'), {
            chart: { type:'area', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Book Value', data: fc.map(r => r.book_value) }],
            xaxis: { categories: fc.map(r => r.label) },
            colors: [P.success],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient:{ opacityFrom:0.45, opacityTo:0.05 } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Maintenance cost trend
        const mt = @json($maintTrend);
        new ApexCharts(document.querySelector('#chart-maint-trend'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Cost', data: mt.map(r => r.value) }],
            xaxis: { categories: mt.map(r => r.label) },
            plotOptions: { bar: { borderRadius:6, columnWidth:'45%' } },
            colors: [P.warning],
            fill: { type:'gradient', gradient: { gradientToColors:[P.danger], stops:[0,100] } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Top maintenance assets bar
        const tm = @json($topMaintAssets);
        new ApexCharts(document.querySelector('#chart-top-maint'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Cost', data: tm.map(r => Number(r.cost)) }],
            xaxis: { categories: tm.map(r => r.asset_code + ' ' + r.name),
                labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.danger, P.warning, P.purple, P.pink, P.primary, P.info, P.success, P.teal, '#f59e0b', '#10b981'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false }
        }).render();

        // Maintenance type donut
        const mtypes = @json((object) $maintByType);
        const mtKeys = Object.keys(mtypes);
        new ApexCharts(document.querySelector('#chart-maint-type'), {
            chart: { type:'donut', height:340, animations:anim, fontFamily:'inherit' },
            series: mtKeys.length ? mtKeys.map(k => Number(mtypes[k])) : [0],
            labels: mtKeys.length ? mtKeys.map(k => k.charAt(0).toUpperCase() + k.slice(1)) : ['No data'],
            colors: [P.danger, P.warning, P.info, P.purple],
            stroke: { width:2, colors:['#fff'] },
            legend: { position:'bottom' },
            plotOptions: { pie: { donut: { size:'65%' } } }
        }).render();

        // Location pie
        const loc = @json($byLocation);
        new ApexCharts(document.querySelector('#chart-location'), {
            chart: { type:'pie', height:300, animations:anim, fontFamily:'inherit' },
            series: loc.map(r => Number(r.cnt)),
            labels: loc.map(r => r.name),
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal],
            stroke: { width:2, colors:['#fff'] },
            legend: { position:'bottom' }
        }).render();

        // Condition column
        const cd = @json((object) $byCondition);
        const cdOrder = ['excellent','good','fair','poor','damaged'];
        const cdKeys = cdOrder.filter(k => cd[k] !== undefined);
        new ApexCharts(document.querySelector('#chart-condition'), {
            chart: { type:'bar', height:300, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Count', data: cdKeys.map(k => Number(cd[k])) }],
            xaxis: { categories: cdKeys.map(k => k.charAt(0).toUpperCase() + k.slice(1)) },
            plotOptions: { bar: { borderRadius:6, columnWidth:'55%', distributed:true } },
            colors: [P.success, P.info, P.warning, '#e97316', P.danger],
            dataLabels: { enabled:true, style:{ colors:['#fff'] } },
            legend: { show:false }
        }).render();
    });
    </script>
</x-layout.admin>
