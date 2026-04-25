<x-layout.admin title="HR Dashboard">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Dashboard']]" />

    {{-- ───────── Header ───────── --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">HR Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Live overview of workforce, attendance, payroll and performance.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-sm btn-primary">+ Add Employee</a>
            <a href="{{ route('admin.hr.attendance.create') }}" class="btn btn-sm btn-outline-info">Mark Attendance</a>
            <a href="{{ route('admin.hr.payroll.generate-form') }}" class="btn btn-sm btn-outline-success">Generate Payroll</a>
        </div>
    </div>

    {{-- ───────── Row 1: KPI cards (animated counters + gradient) ───────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 hr-animate">
        <div class="panel relative overflow-hidden bg-gradient-to-br from-primary to-[#1937cc] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">Total</div>
            <div class="text-3xl font-extrabold mt-1" data-count="{{ $totalEmployees }}">0</div>
            <div class="text-xs mt-1 opacity-80">{{ $activeEmployees }} active · {{ $onProbation }} probation</div>
        </div>

        <div class="panel relative overflow-hidden bg-gradient-to-br from-success to-[#008853] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">Present Today</div>
            <div class="text-3xl font-extrabold mt-1" data-count="{{ $presentToday }}">0</div>
            <div class="text-xs mt-1 opacity-80">{{ $attendanceRate }}% attendance</div>
        </div>

        <div class="panel relative overflow-hidden bg-gradient-to-br from-warning to-[#b87316] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">On Leave</div>
            <div class="text-3xl font-extrabold mt-1" data-count="{{ $onLeaveToday }}">0</div>
            <div class="text-xs mt-1 opacity-80">{{ $pendingLeaves }} pending requests</div>
        </div>

        <div class="panel relative overflow-hidden bg-gradient-to-br from-danger to-[#a4323b] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">Absent Today</div>
            <div class="text-3xl font-extrabold mt-1" data-count="{{ $absentToday }}">0</div>
            <div class="text-xs mt-1 opacity-80">{{ $lateToday }} late · {{ $halfDayToday }} half day</div>
        </div>

        <div class="panel relative overflow-hidden bg-gradient-to-br from-info to-[#0b8caf] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">New This Month</div>
            <div class="text-3xl font-extrabold mt-1" data-count="{{ $newThisMonth }}">0</div>
            <div class="text-xs mt-1 opacity-80">{{ $exitsThisMonth }} exits</div>
        </div>

        <div class="panel relative overflow-hidden bg-gradient-to-br from-[#805dca] to-[#5b3fa0] text-white">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="text-xs uppercase tracking-wide opacity-80">Payroll (Month)</div>
            <div class="text-2xl font-extrabold mt-1">&#8377; <span data-count="{{ (int) $payrollThisMonth }}" data-format="money">0</span></div>
            <div class="text-xs mt-1 opacity-80">{{ $payslipsPaid }}/{{ $payslipsTotal }} payslips paid</div>
        </div>
    </div>

    {{-- ───────── Row 2: Headcount trend + Attendance radial ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 hr-animate">
        <div class="panel lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Headcount — Last 12 Months</h5>
                <span class="text-xs text-gray-400">Active employees at month end</span>
            </div>
            <div id="chart-headcount" style="min-height:320px;"></div>
        </div>

        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Today's Attendance</h5>
            <div id="chart-attendance-radial" style="min-height:320px;"></div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs mt-2">
                <div><div class="font-bold text-success">{{ $presentToday }}</div><div class="text-gray-500">Present</div></div>
                <div><div class="font-bold text-danger">{{ $absentToday }}</div><div class="text-gray-500">Absent</div></div>
                <div><div class="font-bold text-warning">{{ $onLeaveToday }}</div><div class="text-gray-500">On Leave</div></div>
            </div>
        </div>
    </div>

    {{-- ───────── Row 3: Department headcount + Gender donut + Employment polar ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 hr-animate">
        <div class="panel lg:col-span-1">
            <h5 class="text-lg font-semibold mb-3">Department Headcount</h5>
            <div id="chart-dept" style="min-height:320px;"></div>
        </div>

        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Gender Split</h5>
            <div id="chart-gender" style="min-height:320px;"></div>
        </div>

        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Employment Type</h5>
            <div id="chart-employment" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- ───────── Row 4: 30-day attendance stacked area ───────── --}}
    <div class="panel mb-6 hr-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold">Attendance — Last 30 Days</h5>
            <div class="flex gap-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-success"></span> Present</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-warning"></span> Late</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-info"></span> Half Day</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-danger"></span> Absent</span>
            </div>
        </div>
        <div id="chart-attendance-30" style="min-height:320px;"></div>
    </div>

    {{-- ───────── Row 5: Payroll trend + Leaves horizontal bar ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 hr-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Payroll Trend — Last 6 Months</h5>
            <div id="chart-payroll" style="min-height:320px;"></div>
        </div>

        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Leaves by Type ({{ now()->year }})</h5>
            <div id="chart-leaves" style="min-height:320px;"></div>
        </div>
    </div>

    {{-- ───────── Row 6: Age + Tenure + Performance radar ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 hr-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Age Distribution</h5>
            <div id="chart-age" style="min-height:300px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Tenure Distribution</h5>
            <div id="chart-tenure" style="min-height:300px;"></div>
        </div>
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Performance Ratings</h5>
            <div id="chart-rating" style="min-height:300px;"></div>
        </div>
    </div>

    {{-- ───────── Row 7: Hiring by department + Birthdays + Recent joiners ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 hr-animate">
        <div class="panel">
            <h5 class="text-lg font-semibold mb-3">Hiring by Dept ({{ now()->year }})</h5>
            <div id="chart-hiring" style="min-height:300px;"></div>
        </div>

        <div class="panel">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Upcoming Birthdays</h5>
                <span class="text-xs text-gray-400">Next 30 days</span>
            </div>
            <ul class="space-y-2">
                @forelse($upcomingBirthdays as $e)
                    <li class="flex items-center justify-between p-2 rounded-lg bg-gradient-to-r from-primary/5 to-transparent hover:from-primary/10 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($e->first_name, 0, 1).substr($e->last_name ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold">{{ $e->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $e->department?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-semibold text-primary">{{ $e->next_birthday->format('d M') }}</div>
                            <div class="text-[10px] text-gray-400">
                                @if((int) $e->days_until === 0) Today 🎉
                                @elseif((int) $e->days_until === 1) Tomorrow
                                @else in {{ (int) $e->days_until }} days
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 text-center py-6">No upcoming birthdays.</li>
                @endforelse
            </ul>
        </div>

        <div class="panel">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-lg font-semibold">Recent Joiners</h5>
                <a href="{{ route('admin.hr.employees.index') }}" class="text-primary text-xs hover:underline">All →</a>
            </div>
            <ul class="space-y-2">
                @forelse($recentJoiners as $e)
                    <li class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-[#1b2e4b]/40 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-success to-info text-white flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($e->first_name, 0, 1).substr($e->last_name ?? '', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.hr.employees.show', $e) }}" class="text-sm font-semibold text-primary hover:underline truncate block">{{ $e->full_name }}</a>
                            <div class="text-[11px] text-gray-500 truncate">{{ $e->designation?->name ?? '—' }} · {{ $e->department?->name ?? '—' }}</div>
                        </div>
                        <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $e->joining_date?->format('d M') }}</span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 text-center py-6">No recent joiners.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ───────── Row 8: Pending leaves ───────── --}}
    @if($recentPendingLeaves->count() > 0)
    <div class="panel mb-6 hr-animate">
        <div class="flex items-center justify-between mb-3">
            <h5 class="text-lg font-semibold">Pending Leave Requests</h5>
            <a href="{{ route('admin.hr.leaves.index', ['status' => 'pending']) }}" class="text-primary text-xs hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-hover">
                <thead>
                    <tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th class="text-right">Days</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($recentPendingLeaves as $lr)
                        <tr>
                            <td class="font-semibold">{{ $lr->employee?->full_name ?? '—' }}</td>
                            <td>{{ $lr->leaveType?->name ?? '—' }}</td>
                            <td>{{ $lr->from_date?->format('d M Y') }}</td>
                            <td>{{ $lr->to_date?->format('d M Y') }}</td>
                            <td class="text-right font-semibold">{{ $lr->days }}</td>
                            <td class="text-right"><a href="{{ route('admin.hr.leaves.show', $lr) }}" class="text-primary text-xs">Review →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ───────── Fade-in animation CSS ───────── --}}
    <style>
        .hr-animate > * {
            opacity: 0;
            transform: translateY(16px);
            animation: hrFadeUp .65s cubic-bezier(.22,.61,.36,1) forwards;
        }
        .hr-animate > *:nth-child(1) { animation-delay: .05s; }
        .hr-animate > *:nth-child(2) { animation-delay: .12s; }
        .hr-animate > *:nth-child(3) { animation-delay: .19s; }
        .hr-animate > *:nth-child(4) { animation-delay: .26s; }
        .hr-animate > *:nth-child(5) { animation-delay: .33s; }
        .hr-animate > *:nth-child(6) { animation-delay: .40s; }
        @keyframes hrFadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .apexcharts-tooltip {
            box-shadow: 0 10px 30px rgba(0,0,0,.12) !important;
            border-radius: 10px !important;
        }
    </style>

    {{-- ───────── ApexCharts from CDN ───────── --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // Animated numeric counters
        document.querySelectorAll('[data-count]').forEach(function (el) {
            const target = parseFloat(el.dataset.count) || 0;
            const fmt = el.dataset.format === 'money';
            const dur = 1200;
            const start = performance.now();
            function tick(now) {
                const p = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - p, 3);
                const v = Math.round(target * eased);
                el.textContent = fmt ? v.toLocaleString('en-IN') : v.toLocaleString('en-IN');
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });

        // Theme palette
        const P = {
            primary: '#4361ee', info: '#2196f3', success: '#00ab55',
            warning: '#e2a03f', danger: '#e7515a', purple: '#805dca',
            pink: '#e95f9b', teal: '#00c4b4'
        };

        const anim = {
            enabled: true,
            easing: 'easeinout',
            speed: 900,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 450 }
        };

        const commonTooltip = { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' };

        // ── 1. Headcount line (smooth area) ────────────────────────────
        const hc = @json($headcountTrend);
        new ApexCharts(document.querySelector('#chart-headcount'), {
            chart: { type: 'area', height: 320, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Headcount', data: hc.map(r => r.value) }],
            xaxis: { categories: hc.map(r => r.label), labels: { style: { colors: '#8a8a8a' } } },
            yaxis: { labels: { style: { colors: '#8a8a8a' } } },
            stroke: { curve: 'smooth', width: 3 },
            colors: [P.primary],
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(0,0,0,.06)', strokeDashArray: 3 },
            markers: { size: 4, strokeWidth: 2, strokeColors: '#fff', hover: { size: 6 } },
            tooltip: commonTooltip
        }).render();

        // ── 2. Attendance radial (multi-series donut) ──────────────────
        const total = {{ $totalEmployees ?: 1 }};
        const present = {{ $presentToday }};
        const absent = {{ $absentToday }};
        const leave = {{ $onLeaveToday }};
        const pctPresent = Math.round((present / total) * 100);
        const pctAbsent = Math.round((absent / total) * 100);
        const pctLeave = Math.round((leave / total) * 100);
        new ApexCharts(document.querySelector('#chart-attendance-radial'), {
            chart: { type: 'radialBar', height: 320, animations: anim, fontFamily: 'inherit' },
            series: [pctPresent, pctLeave, pctAbsent],
            labels: ['Present', 'Leave', 'Absent'],
            colors: [P.success, P.warning, P.danger],
            plotOptions: {
                radialBar: {
                    hollow: { size: '40%' },
                    track: { background: 'rgba(0,0,0,.04)' },
                    dataLabels: {
                        name: { fontSize: '14px' },
                        value: { fontSize: '16px', formatter: v => v + '%' },
                        total: {
                            show: true, label: 'Total',
                            formatter: () => total
                        }
                    }
                }
            },
            stroke: { lineCap: 'round' },
            legend: { show: true, position: 'bottom' }
        }).render();

        // ── 3. Department column ───────────────────────────────────────
        const dep = @json($deptHeadcount);
        new ApexCharts(document.querySelector('#chart-dept'), {
            chart: { type: 'bar', height: 320, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Employees', data: dep.map(r => r.total) }],
            xaxis: { categories: dep.map(r => r.name), labels: { style: { colors: '#8a8a8a' } } },
            plotOptions: {
                bar: { borderRadius: 6, columnWidth: '55%', distributed: true,
                    dataLabels: { position: 'top' } }
            },
            colors: [P.primary, P.info, P.success, P.warning, P.danger, P.purple, P.pink, P.teal, '#f59e0b', '#10b981'],
            dataLabels: { enabled: true, offsetY: -18, style: { colors: ['#555'], fontSize: '11px' } },
            legend: { show: false },
            grid: { borderColor: 'rgba(0,0,0,.05)' },
            tooltip: commonTooltip
        }).render();

        // ── 4. Gender donut ────────────────────────────────────────────
        const gen = @json((object) $genderSplit);
        const genLabels = Object.keys(gen).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        new ApexCharts(document.querySelector('#chart-gender'), {
            chart: { type: 'donut', height: 320, animations: anim, fontFamily: 'inherit' },
            series: Object.values(gen).map(v => Number(v)),
            labels: genLabels.length ? genLabels : ['No data'],
            colors: [P.primary, P.pink, P.teal, P.warning],
            stroke: { width: 2, colors: ['#fff'] },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: { show: true, label: 'Total', color: '#888', formatter: w => w.globals.seriesTotals.reduce((a,b)=>a+b,0) }
                        }
                    }
                }
            },
            dataLabels: { enabled: true, formatter: (v) => Math.round(v) + '%' },
            legend: { position: 'bottom' }
        }).render();

        // ── 5. Employment type polar ───────────────────────────────────
        const emp = @json((object) $employmentType);
        const empLabels = Object.keys(emp).map(k => k.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()));
        new ApexCharts(document.querySelector('#chart-employment'), {
            chart: { type: 'polarArea', height: 320, animations: anim, fontFamily: 'inherit' },
            series: Object.values(emp).map(v => Number(v)),
            labels: empLabels.length ? empLabels : ['No data'],
            colors: [P.primary, P.success, P.warning, P.info, P.purple, P.pink],
            stroke: { colors: ['#fff'] },
            fill: { opacity: 0.85 },
            legend: { position: 'bottom' },
            yaxis: { show: false }
        }).render();

        // ── 6. 30-day stacked area ─────────────────────────────────────
        const a30 = @json($attendance30);
        new ApexCharts(document.querySelector('#chart-attendance-30'), {
            chart: { type: 'area', height: 320, stacked: true, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [
                { name: 'Present', data: a30.map(r => r.present) },
                { name: 'Late', data: a30.map(r => r.late) },
                { name: 'Half Day', data: a30.map(r => r.half_day) },
                { name: 'Absent', data: a30.map(r => r.absent) },
            ],
            xaxis: { categories: a30.map(r => r.date), labels: { style: { colors: '#8a8a8a' } } },
            colors: [P.success, P.warning, P.info, P.danger],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.55, opacityTo: 0.1 } },
            dataLabels: { enabled: false },
            legend: { show: false },
            grid: { borderColor: 'rgba(0,0,0,.05)', strokeDashArray: 3 },
            tooltip: commonTooltip
        }).render();

        // ── 7. Payroll column ──────────────────────────────────────────
        const pt = @json($payrollTrend);
        new ApexCharts(document.querySelector('#chart-payroll'), {
            chart: { type: 'bar', height: 320, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Net Pay', data: pt.map(r => r.value) }],
            xaxis: { categories: pt.map(r => r.label) },
            plotOptions: { bar: { borderRadius: 8, columnWidth: '45%' } },
            colors: [P.purple],
            fill: {
                type: 'gradient',
                gradient: { shade: 'light', type: 'vertical', gradientToColors: [P.primary], stops: [0, 100] }
            },
            dataLabels: { enabled: false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            tooltip: { ...commonTooltip, y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor: 'rgba(0,0,0,.05)' }
        }).render();

        // ── 8. Leaves horizontal bar ───────────────────────────────────
        const lb = @json($leavesByType);
        new ApexCharts(document.querySelector('#chart-leaves'), {
            chart: { type: 'bar', height: 320, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Days', data: lb.map(r => Number(r.total)) }],
            xaxis: { categories: lb.map(r => r.name) },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '65%' } },
            colors: [P.info, P.primary, P.success, P.warning, P.danger, P.purple, P.pink, P.teal],
            dataLabels: { enabled: true, style: { colors: ['#fff'] } },
            legend: { show: false },
            grid: { borderColor: 'rgba(0,0,0,.05)' },
            tooltip: commonTooltip
        }).render();

        // ── 9. Age column ──────────────────────────────────────────────
        const ab = @json((object) $ageBuckets);
        new ApexCharts(document.querySelector('#chart-age'), {
            chart: { type: 'bar', height: 300, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Employees', data: Object.values(ab) }],
            xaxis: { categories: Object.keys(ab) },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '60%', distributed: true } },
            colors: [P.teal, P.info, P.primary, P.purple, P.pink, P.danger],
            dataLabels: { enabled: true, style: { colors: ['#fff'] } },
            legend: { show: false }
        }).render();

        // ── 10. Tenure donut ───────────────────────────────────────────
        const tb = @json((object) $tenureBuckets);
        new ApexCharts(document.querySelector('#chart-tenure'), {
            chart: { type: 'donut', height: 300, animations: anim, fontFamily: 'inherit' },
            series: Object.values(tb),
            labels: Object.keys(tb),
            colors: [P.info, P.primary, P.success, P.warning, P.danger],
            stroke: { width: 2, colors: ['#fff'] },
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '65%' } } }
        }).render();

        // ── 11. Rating radar ───────────────────────────────────────────
        const rt = @json((object) $ratingBuckets);
        const rtLabels = ['poor','average','good','excellent','outstanding'];
        const rtData = rtLabels.map(k => rt[k] ? Number(rt[k]) : 0);
        new ApexCharts(document.querySelector('#chart-rating'), {
            chart: { type: 'radar', height: 300, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Employees', data: rtData }],
            labels: rtLabels.map(k => k.charAt(0).toUpperCase() + k.slice(1)),
            colors: [P.success],
            stroke: { width: 2 },
            fill: { opacity: 0.35 },
            markers: { size: 4 }
        }).render();

        // ── 12. Hiring by dept column ──────────────────────────────────
        const hd = @json($hiringDepts);
        new ApexCharts(document.querySelector('#chart-hiring'), {
            chart: { type: 'bar', height: 300, toolbar: { show: false }, animations: anim, fontFamily: 'inherit' },
            series: [{ name: 'Hires', data: hd.map(r => Number(r.hires)) }],
            xaxis: { categories: hd.map(r => r.name) },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
            colors: [P.primary, P.success, P.warning, P.info, P.purple, P.pink],
            dataLabels: { enabled: true, style: { colors: ['#fff'] } },
            legend: { show: false }
        }).render();
    });
    </script>
</x-layout.admin>
