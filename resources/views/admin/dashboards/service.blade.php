<x-layout.admin title="Service Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Service Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Service & Support Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tickets, SLA compliance, workload & response times.</p>
        </div>
        <a href="{{ route('admin.service-tickets.create') }}" class="btn btn-sm btn-primary">+ New Ticket</a>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Total Tickets</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['total'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['mtd_opened'] }} this month</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Open</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['open'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Needs attention</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">In Progress</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['in_progress'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Being worked on</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Resolved</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['resolved'] + $kpi['closed'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['mtd_closed'] }} this month</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#a4323b] to-[#7a1f26] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Critical Open</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $kpi['critical'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Escalation needed</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">SLA Compliance</div>
            <div class="text-3xl font-extrabold mt-1"><span data-count="{{ $slaRate }}">0</span>%</div>
            <div class="text-xs opacity-80 mt-1">{{ $slaCompliant }}/{{ $slaTotal }} within target</div>
        </div>
    </div>

    {{-- SLA gauge + Status flow --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">SLA Compliance — Last 90 Days</h5>
            <div id="chart-sla" style="min-height:320px;"></div>
            <div class="text-xs text-gray-500 text-center mt-2">Targets: Critical 4h · High 24h · Medium 72h · Low 168h</div>
        </div>
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Ticket Flow — Open → In Progress → Resolved → Closed</h5>
            <div id="chart-flow" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Volume trend + Priority --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Ticket Volume — Opened vs Closed (12 months)</h5>
            <div id="chart-volume" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">By Priority</h5>
            <div id="chart-priority" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Resolution time by priority + Category --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Avg Resolution Time by Priority (hours)</h5>
            <div id="chart-restime" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Tickets by Category</h5>
            <div id="chart-category" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Technician workload heatmap --}}
    @if(count($heatmap) > 0)
    <div class="panel mb-6 dash-animate">
        <h5 class="text-lg font-semibold mb-3">Technician Workload — Day of Week (last 60 days)</h5>
        <div id="chart-heatmap" style="min-height:{{ max(320, 40 * count($heatmap) + 80) }}px;"></div>
    </div>
    @endif

    {{-- Critical open tickets --}}
    @if($criticalTickets->count() > 0)
    <div class="panel mb-6 dash-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold text-danger">Critical Tickets — Open & In Progress</h5>
            <a href="{{ route('admin.service-tickets.index', ['priority' => 'critical']) }}" class="text-primary text-xs hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-hover">
                <thead><tr><th>Ticket #</th><th>Customer</th><th>Opened</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($criticalTickets as $t)
                        <tr>
                            <td class="font-mono font-semibold text-danger"><a href="{{ route('admin.service-tickets.show', $t) }}">{{ $t->ticket_number }}</a></td>
                            <td>{{ $t->customer?->name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($t->opened_at)->diffForHumans() }}</td>
                            <td><span class="px-2 py-0.5 rounded text-xs bg-danger/10 text-danger">{{ ucfirst(str_replace('_',' ', $t->status)) }}</span></td>
                            <td class="text-right"><a href="{{ route('admin.service-tickets.show', $t) }}" class="text-primary text-xs">Open →</a></td>
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
                el.textContent = (target % 1 === 0 ? Math.round(target * eased) : (target * eased).toFixed(1)).toLocaleString ? Math.round(target * eased).toLocaleString('en-IN') : (target * eased).toFixed(1);
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });

        const P = { primary:'#4361ee', info:'#2196f3', success:'#00ab55', warning:'#e2a03f',
                    danger:'#e7515a', purple:'#805dca', pink:'#e95f9b', teal:'#00c4b4' };
        const anim = { enabled:true, easing:'easeinout', speed:900,
            animateGradually:{ enabled:true, delay:150 }, dynamicAnimation:{ enabled:true, speed:450 } };

        // SLA gauge
        const slaRate = {{ $slaRate }};
        new ApexCharts(document.querySelector('#chart-sla'), {
            chart: { type:'radialBar', height:320, animations:anim, fontFamily:'inherit' },
            series: [slaRate],
            plotOptions: { radialBar: {
                startAngle:-135, endAngle:135, hollow:{size:'65%'},
                track:{ background:'rgba(0,0,0,.05)' },
                dataLabels: {
                    name: { fontSize:'14px', offsetY:-5, color:'#888' },
                    value: { fontSize:'32px', fontWeight:800, formatter: v => v + '%' }
                }
            } },
            fill: { type:'gradient', gradient: { shade:'dark', shadeIntensity:0.5,
                gradientToColors:[slaRate >= 85 ? P.success : slaRate >= 65 ? P.warning : P.danger], stops:[0,100] } },
            colors: [slaRate >= 85 ? P.success : slaRate >= 65 ? P.warning : P.danger],
            labels: ['Within SLA'],
            stroke: { lineCap:'round' }
        }).render();

        // Flow stacked bar (Open, In Progress, Resolved, Closed)
        const flow = @json($flow);
        new ApexCharts(document.querySelector('#chart-flow'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Tickets', data: [
                { x:'Open',        y: flow.open,        fillColor: P.danger },
                { x:'In Progress', y: flow.in_progress, fillColor: P.warning },
                { x:'Resolved',    y: flow.resolved,    fillColor: P.info },
                { x:'Closed',      y: flow.closed,      fillColor: P.success },
            ] }],
            plotOptions: { bar: { borderRadius:8, columnWidth:'45%', distributed:true,
                dataLabels: { position:'top' } } },
            dataLabels: { enabled:true, offsetY:-18, style:{ colors:['#555'], fontSize:'14px', fontWeight:700 } },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Volume trend line
        const vol = @json($volumeTrend);
        new ApexCharts(document.querySelector('#chart-volume'), {
            chart: { type:'area', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Opened', data: vol.map(r => r.opened) },
                { name:'Closed', data: vol.map(r => r.closed) },
            ],
            xaxis: { categories: vol.map(r => r.label) },
            colors: [P.danger, P.success],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient:{ opacityFrom:0.45, opacityTo:0.05 } },
            dataLabels: { enabled:false },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            legend: { position:'top' }
        }).render();

        // Priority donut
        const pr = @json((object) $byPriority);
        const prOrder = ['critical','high','medium','low'];
        const prLabels = prOrder.filter(k => pr[k] !== undefined);
        new ApexCharts(document.querySelector('#chart-priority'), {
            chart: { type:'donut', height:320, animations:anim, fontFamily:'inherit' },
            series: prLabels.map(k => Number(pr[k])),
            labels: prLabels.map(k => k.charAt(0).toUpperCase() + k.slice(1)),
            colors: [P.danger, '#e97316', P.warning, P.info],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%',
                labels: { show:true, total:{ show:true, label:'Total',
                    formatter: w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) } } } } },
            legend: { position:'bottom' }
        }).render();

        // Resolution time by priority
        const rt = @json($resByPriority);
        new ApexCharts(document.querySelector('#chart-restime'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Avg hours', data: rt.map(r => Math.round(r.avg_hrs || 0)) }],
            xaxis: { categories: rt.map(r => (r.priority || '').charAt(0).toUpperCase() + (r.priority || '').slice(1)) },
            plotOptions: { bar: { borderRadius:6, columnWidth:'50%', distributed:true } },
            colors: [P.danger, '#e97316', P.warning, P.info],
            dataLabels: { enabled:true, formatter: v => v + ' hrs', style:{ colors:['#fff'] } },
            legend: { show:false }
        }).render();

        // Category horizontal bar
        const cat = @json($byCategory);
        new ApexCharts(document.querySelector('#chart-category'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Tickets', data: cat.map(r => Number(r.cnt)) }],
            xaxis: { categories: cat.map(r => r.name) },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal],
            dataLabels: { enabled:true, style:{ colors:['#fff'] } },
            legend: { show:false }
        }).render();

        // Technician heatmap
        @if(count($heatmap) > 0)
        const hm = @json($heatmap);
        const days = @json($days);
        new ApexCharts(document.querySelector('#chart-heatmap'), {
            chart: { type:'heatmap', height: {{ max(320, 40 * count($heatmap) + 80) }},
                toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: hm.map(r => ({ name: r.name, data: r.data.map((v, i) => ({ x: days[i], y: v })) })),
            colors: [P.primary],
            plotOptions: { heatmap: { radius:4, enableShades:true, shadeIntensity:0.55,
                colorScale: { ranges: [
                    { from:0, to:0, color:'#eef2ff', name:'None' },
                    { from:1, to:3, color:'#c7d2fe', name:'Low' },
                    { from:4, to:8, color:'#818cf8', name:'Med' },
                    { from:9, to:15, color:'#4361ee', name:'High' },
                    { from:16, to:9999, color:'#1e2a6b', name:'Very High' },
                ] } } },
            dataLabels: { enabled:true, style:{ colors:['#fff'], fontSize:'11px' } },
            xaxis: { type:'category' }
        }).render();
        @endif
    });
    </script>
</x-layout.admin>
