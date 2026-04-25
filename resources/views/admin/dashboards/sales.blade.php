<x-layout.admin title="Sales Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'Sales Dashboard']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Sales Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Leads, quotations, orders, invoices & receivables.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.leads.create') }}" class="btn btn-sm btn-outline-primary">+ Lead</a>
            <a href="{{ route('admin.quotations.create') }}" class="btn btn-sm btn-outline-info">+ Quotation</a>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-sm btn-primary">+ Invoice</a>
        </div>
    </div>

    {{-- KPI Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 dash-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Pipeline Value</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['pipeline_value'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['leads_open'] }} open leads</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Revenue (MTD)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['revenue_mtd'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">₹{{ number_format($kpi['paid_mtd']) }} collected</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Receivables</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;<span data-count="{{ (int) $kpi['receivables'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">DSO: {{ $dso }} days</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Quote Win Rate</div>
            <div class="text-2xl font-extrabold mt-1"><span data-count="{{ $kpi['quote_acceptance_rate'] }}">0</span>%</div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['quotes_accepted'] }}/{{ $kpi['quotes_total'] }} accepted</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Lead Win %</div>
            <div class="text-2xl font-extrabold mt-1"><span data-count="{{ $kpi['win_rate'] }}">0</span>%</div>
            <div class="text-xs opacity-80 mt-1">{{ $kpi['leads_won'] }} won / {{ $kpi['leads_total'] }}</div>
        </div>
        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#e95f9b] to-[#b8467a] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase opacity-80">Active Customers</div>
            <div class="text-2xl font-extrabold mt-1"><span data-count="{{ $kpi['customers_total'] }}">0</span></div>
            <div class="text-xs opacity-80 mt-1">Top 10 in chart below</div>
        </div>
    </div>

    {{-- Funnel + DSO gauge --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Sales Funnel — Lead to Paid</h5>
            <div id="chart-funnel" style="min-height:340px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Days Sales Outstanding</h5>
            <div id="chart-dso" style="min-height:340px;"></div>
            <div class="text-center text-xs text-gray-500 mt-2">Lower is better. Target: &lt; 30 days.</div>
        </div>
    </div>

    {{-- Revenue trend + Lead source --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <h5 class="text-lg font-semibold mb-3">Revenue — Invoiced vs Collected (12 months)</h5>
            <div id="chart-revenue" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Lead Source</h5>
            <div id="chart-leadsource" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Quote status + Invoice treemap --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Quotation Status (6 months)</h5>
            <div id="chart-quotestatus" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Invoice Status — Value Breakdown</h5>
            <div id="chart-invoicetreemap" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- Receivables aging + Lead source bubble --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 dash-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Receivables Aging</h5>
            <div id="chart-aging" style="min-height:320px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Lead Source × Value</h5>
            <div id="chart-sourcebubble" style="min-height:320px;"></div>
            <div class="text-xs text-gray-400 text-center mt-1">Bubble size = total expected value per source.</div>
        </div>
    </div>

    {{-- Top customers bar + Recent lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 dash-animate">
        <div class="panel lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Top 10 Customers by Revenue</h5>
                <a href="{{ route('admin.customers.index') }}" class="text-primary text-xs hover:underline">All →</a>
            </div>
            <div id="chart-topcustomers" style="min-height:360px;"></div>
        </div>

        <div class="panel">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Latest Leads</h5>
                <a href="{{ route('admin.leads.index') }}" class="text-primary text-xs hover:underline">All →</a>
            </div>
            <ul class="space-y-2">
                @forelse($recentLeads as $l)
                    <li class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-[#1b2e4b]/40">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.leads.show', $l) }}" class="text-sm font-semibold text-primary hover:underline truncate block">{{ $l->name }}</a>
                            <div class="text-[11px] text-gray-500 truncate">{{ $l->company ?? $l->email ?? '—' }} · {{ ucfirst($l->source) }}</div>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded
                            @switch($l->status)
                                @case('won') bg-success/10 text-success @break
                                @case('lost') bg-danger/10 text-danger @break
                                @case('proposal') bg-info/10 text-info @break
                                @default bg-warning/10 text-warning
                            @endswitch
                        ">{{ ucfirst($l->status) }}</span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 text-center py-6">No recent leads.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Overdue invoices alert table --}}
    @if($overdueInvoices->count() > 0)
    <div class="panel mb-6 dash-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold text-danger">Overdue Invoices</h5>
            <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="text-primary text-xs hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-hover">
                <thead><tr><th>Invoice #</th><th>Customer</th><th>Date</th><th>Due</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                    @foreach($overdueInvoices as $inv)
                        <tr>
                            <td class="font-mono font-semibold text-primary"><a href="{{ route('admin.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                            <td>{{ $inv->customer?->name ?? '—' }}</td>
                            <td>{{ $inv->invoice_date?->format('d M Y') }}</td>
                            <td class="text-danger">{{ $inv->due_date?->format('d M Y') }}</td>
                            <td class="text-right font-bold text-danger">&#8377;{{ number_format($inv->balance_due ?? $inv->grand_total, 2) }}</td>
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
        // Counter animation
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

        // Funnel (horizontal bar with distributed colors)
        const funnel = @json($funnel);
        new ApexCharts(document.querySelector('#chart-funnel'), {
            chart: { type:'bar', height:340, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Count', data: funnel.map(r => r.value) }],
            xaxis: { categories: funnel.map(r => r.stage) },
            plotOptions: { bar: { horizontal:true, distributed:true, borderRadius:6, barHeight:'70%',
                dataLabels: { position:'center' } } },
            colors: [P.primary, P.info, P.teal, P.success, P.warning, P.purple],
            dataLabels: { enabled:true, style:{ colors:['#fff'], fontWeight:700 },
                formatter: (v, opt) => {
                    const data = funnel.map(r => r.value);
                    const first = data[0] || 1;
                    const pct = Math.round((v / first) * 100);
                    return v.toLocaleString('en-IN') + ' (' + pct + '%)';
                } },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // DSO radial gauge (0-60 scale)
        const dso = {{ $dso }};
        new ApexCharts(document.querySelector('#chart-dso'), {
            chart: { type:'radialBar', height:320, animations:anim, fontFamily:'inherit' },
            series: [ Math.min(100, Math.round((dso / 60) * 100)) ],
            plotOptions: { radialBar: {
                startAngle:-135, endAngle:135, hollow:{size:'65%'},
                track: { background:'rgba(0,0,0,.05)', strokeWidth:'100%' },
                dataLabels: {
                    name: { fontSize:'14px', offsetY:-5 },
                    value: { fontSize:'32px', fontWeight:800, formatter: () => dso + ' d' }
                }
            } },
            fill: { type:'gradient', gradient: { shade:'dark', shadeIntensity:0.5,
                gradientToColors:[dso <= 30 ? P.success : dso <= 45 ? P.warning : P.danger],
                stops:[0, 100] } },
            colors: [dso <= 30 ? P.success : dso <= 45 ? P.warning : P.danger],
            labels: ['DSO'],
            stroke: { lineCap:'round' }
        }).render();

        // Revenue line (dual series)
        const rev = @json($revenueTrend);
        new ApexCharts(document.querySelector('#chart-revenue'), {
            chart: { type:'area', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [
                { name:'Invoiced', data: rev.map(r => r.invoiced) },
                { name:'Collected', data: rev.map(r => r.paid) },
            ],
            xaxis: { categories: rev.map(r => r.label) },
            colors: [P.primary, P.success],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient:{ opacityFrom:0.4, opacityTo:0.05 } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            legend: { position:'top' }
        }).render();

        // Lead source donut
        const ls = @json((object) $leadBySource);
        const lsKeys = Object.keys(ls);
        new ApexCharts(document.querySelector('#chart-leadsource'), {
            chart: { type:'donut', height:320, animations:anim, fontFamily:'inherit' },
            series: Object.values(ls).map(v => Number(v)),
            labels: lsKeys.length ? lsKeys.map(k => k.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())) : ['No data'],
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal],
            stroke: { width:2, colors:['#fff'] },
            plotOptions: { pie: { donut: { size:'65%', labels: { show:true,
                total: { show:true, label:'Total', formatter: w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) } } } } },
            legend: { position:'bottom' },
            dataLabels: { enabled:true, formatter: v => Math.round(v) + '%' }
        }).render();

        // Quotation status stacked column
        const qsm = @json($quoteStatusMonthly);
        new ApexCharts(document.querySelector('#chart-quotestatus'), {
            chart: { type:'bar', height:320, stacked:true, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: ['draft','sent','accepted','rejected','expired'].map(s => ({
                name: s.charAt(0).toUpperCase() + s.slice(1),
                data: qsm.map(r => r[s] || 0)
            })),
            xaxis: { categories: qsm.map(r => r.label) },
            plotOptions: { bar: { borderRadius:4, columnWidth:'55%' } },
            colors: [P.info, P.primary, P.success, P.danger, P.warning],
            dataLabels: { enabled:false },
            legend: { position:'top' },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Invoice status treemap
        const invst = @json($invoiceStatus);
        new ApexCharts(document.querySelector('#chart-invoicetreemap'), {
            chart: { type:'treemap', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ data: invst.map(r => ({ x: r.status.charAt(0).toUpperCase() + r.status.slice(1) + ' (' + r.cnt + ')', y: Number(r.total) })) }],
            colors: [P.success, P.warning, P.info, P.danger, P.purple],
            plotOptions: { treemap: { distributed:true, enableShades:false } },
            dataLabels: { enabled:true, style:{ fontSize:'13px', fontWeight:700 },
                formatter: (text, op) => [text, '₹' + Number(op.value).toLocaleString('en-IN')] },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();

        // Aging horizontal bar
        const ag = @json((object) $aging);
        new ApexCharts(document.querySelector('#chart-aging'), {
            chart: { type:'bar', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Balance Due', data: Object.values(ag) }],
            xaxis: { categories: Object.keys(ag), labels:{ formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, distributed:true, borderRadius:6, barHeight:'70%' } },
            colors: [P.success, P.warning, '#d97706', P.danger],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();

        // Lead source bubble
        const lsb = @json($leadSourceBubble);
        new ApexCharts(document.querySelector('#chart-sourcebubble'), {
            chart: { type:'bubble', height:320, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Sources', data: lsb.map(r => ({
                x: Number(r.cnt), y: Math.round(r.avg_val || 0), z: Math.round(r.total_val || 0), name: r.source
            })) }],
            xaxis: { title:{ text:'Lead count' }, tickAmount:5 },
            yaxis: { title:{ text:'Avg expected value (₹)' }, labels:{ formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            colors: [P.purple],
            fill: { opacity: 0.75 },
            dataLabels: { enabled:true, formatter: (v, op) => lsb[op.dataPointIndex]?.source },
            tooltip: { custom: ({ dataPointIndex }) => {
                const r = lsb[dataPointIndex];
                return `<div class="px-2 py-1"><b>${r.source}</b><br/>Leads: ${r.cnt}<br/>Avg: ₹${Number(r.avg_val||0).toLocaleString('en-IN')}<br/>Total: ₹${Number(r.total_val||0).toLocaleString('en-IN')}</div>`;
            } }
        }).render();

        // Top customers horizontal bar
        const tc = @json($topCustomers);
        new ApexCharts(document.querySelector('#chart-topcustomers'), {
            chart: { type:'bar', height:360, toolbar:{show:false}, animations:anim, fontFamily:'inherit' },
            series: [{ name:'Revenue', data: tc.map(c => Number(c.invoices_sum_grand_total || 0)) }],
            xaxis: { categories: tc.map(c => c.name), labels:{ formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, barHeight:'65%' } },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            dataLabels: { enabled:true, style:{ colors:['#fff'] },
                formatter: v => '₹' + Number(v).toLocaleString('en-IN') },
            legend: { show:false },
            grid: { borderColor:'rgba(0,0,0,.05)' }
        }).render();
    });
    </script>
</x-layout.admin>
