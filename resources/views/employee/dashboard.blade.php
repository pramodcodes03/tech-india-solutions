<x-layout.employee title="Dashboard">

    {{-- Greeting hero --}}
    <div class="mb-6 p-6 rounded-2xl bg-gradient-to-br from-primary via-info to-primary/70 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,.3), transparent 50%), radial-gradient(circle at 80% 80%, rgba(255,255,255,.2), transparent 50%);"></div>
        <div class="relative">
            <div class="text-xs uppercase tracking-wider opacity-80">{{ now()->format('l, d F Y') }}</div>
            <h1 class="text-3xl font-extrabold mt-2">Hello, {{ $employee->first_name }} 👋</h1>
            <div class="mt-1 opacity-90">{{ $employee->designation?->name ?? 'Team Member' }} · {{ $employee->department?->name ?? '' }}</div>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-green-300 animate-pulse"></span>
                    {{ ucfirst(str_replace('_', ' ', $employee->status)) }}
                </span>
                @if($employee->shift)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold">
                    🕒 {{ $employee->shift->name }}
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Today card + stats --}}
    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="col-span-12 lg:col-span-4 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold">Today's Attendance</div>
            @if($todayRecord)
                <div class="mt-3 space-y-2">
                    <div class="flex justify-between"><span class="text-gray-500">Check-in</span><span class="font-semibold">{{ $todayRecord->check_in ? \Carbon\Carbon::parse($todayRecord->check_in)->format('g:i A') : '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Check-out</span><span class="font-semibold">{{ $todayRecord->check_out ? \Carbon\Carbon::parse($todayRecord->check_out)->format('g:i A') : '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Hours</span><span class="font-semibold">{{ number_format($todayRecord->hours_worked, 2) }} hrs</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Status</span>
                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                            @class([
                                'bg-success/10 text-success' => in_array($todayRecord->status, ['present']),
                                'bg-warning/10 text-warning' => in_array($todayRecord->status, ['late', 'half_day']),
                                'bg-danger/10 text-danger' => $todayRecord->status === 'absent',
                                'bg-info/10 text-info' => $todayRecord->status === 'on_leave',
                            ])">{{ ucfirst(str_replace('_', ' ', $todayRecord->status)) }}</span>
                    </div>
                </div>
                @if(!$todayRecord->check_out)
                    <form method="POST" action="{{ route('employee.attendance.punch') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">Check Out</button>
                    </form>
                @endif
            @else
                <div class="mt-3 text-center py-4">
                    <div class="text-4xl mb-2">⏱</div>
                    <div class="text-gray-500 mb-3">You haven't checked in yet</div>
                    <form method="POST" action="{{ route('employee.attendance.punch') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">Check In Now</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="col-span-12 lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-3">
            @php
                $tiles = [
                    ['label' => 'Present', 'value' => $summary['present'] + $summary['late'], 'bg' => 'from-success/15 to-success/5', 'text' => 'text-success', 'icon' => '✓'],
                    ['label' => 'Absent', 'value' => $summary['absent'], 'bg' => 'from-danger/15 to-danger/5', 'text' => 'text-danger', 'icon' => '✕'],
                    ['label' => 'On Leave', 'value' => $summary['on_leave'], 'bg' => 'from-info/15 to-info/5', 'text' => 'text-info', 'icon' => '🌴'],
                    ['label' => 'Half / Late', 'value' => $summary['half_day'].' / '.$summary['late'], 'bg' => 'from-warning/15 to-warning/5', 'text' => 'text-warning', 'icon' => '◐'],
                ];
            @endphp
            @foreach($tiles as $t)
                <div class="p-4 rounded-xl bg-gradient-to-br {{ $t['bg'] }} dark:bg-[#1b2e4b] shadow">
                    <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">{{ $t['label'] }}</div>
                    <div class="flex items-end justify-between mt-2">
                        <div class="text-2xl font-extrabold {{ $t['text'] }}">{{ $t['value'] }}</div>
                        <div class="text-xl opacity-50">{{ $t['icon'] }}</div>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-1">{{ now()->format('F Y') }}</div>
                </div>
            @endforeach

            <div class="col-span-2 md:col-span-4 p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs uppercase tracking-wider text-gray-500 font-bold">Paid Days Progress — {{ now()->format('F') }}</div>
                    <div class="text-sm font-semibold">{{ $summary['paid_days'] }} / {{ $summary['working_days'] }}</div>
                </div>
                @php $pct = $summary['working_days'] > 0 ? min(100, round(($summary['paid_days'] / $summary['working_days']) * 100)) : 0; @endphp
                <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary to-info" style="width: {{ $pct }}%"></div>
                </div>
                <div class="mt-1 text-[11px] text-gray-400">{{ $pct }}% complete · {{ $summary['lop_days'] }} LOP day(s)</div>
            </div>
        </div>
    </div>

    {{-- Leave balances --}}
    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="col-span-12 lg:col-span-8 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-lg">Leave Balances ({{ now()->year }})</h2>
                <a href="{{ route('employee.leaves.create') }}" class="btn btn-sm btn-primary">Apply Leave</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @forelse($employee->leaveBalances->where('year', now()->year) as $b)
                    @php $avail = $b->allocated + $b->carried_forward - $b->used - $b->pending; @endphp
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700" style="border-left: 3px solid {{ $b->leaveType->color ?? '#3b82f6' }}">
                        <div class="text-xs text-gray-500 font-semibold">{{ $b->leaveType->name }}</div>
                        <div class="text-2xl font-extrabold mt-1">{{ number_format($avail, 1) }}</div>
                        <div class="text-[11px] text-gray-400">
                            Used {{ number_format($b->used, 1) }} / Pending {{ number_format($b->pending, 1) }}
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-sm text-gray-500 py-4 text-center">No leave balance allocated. Contact HR.</div>
                @endforelse
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h2 class="font-bold text-lg mb-3">Upcoming Holidays</h2>
            @forelse($upcomingHolidays as $h)
                <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-dark-light">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded bg-primary/10 text-primary">
                        <div class="text-xs font-bold">{{ $h->date->format('M') }}</div>
                        <div class="text-lg font-extrabold leading-none">{{ $h->date->format('d') }}</div>
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{{ $h->name }}</div>
                        <div class="text-xs text-gray-500">{{ $h->date->format('l') }}</div>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 py-4 text-center">No upcoming holidays in your calendar.</div>
            @endforelse
        </div>
    </div>

    {{-- Recent payslips + birthdays + quick actions --}}
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-6 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-lg">Recent Payslips</h2>
                <a href="{{ route('employee.payslips.index') }}" class="text-xs text-primary">View all →</a>
            </div>
            @forelse($recentPayslips as $p)
                <a href="{{ route('employee.payslips.show', $p) }}" class="flex items-center justify-between p-3 rounded hover:bg-gray-50 dark:hover:bg-dark-light">
                    <div>
                        <div class="font-semibold">{{ $p->period_label }}</div>
                        <div class="text-xs text-gray-500">{{ $p->payslip_code }} · {{ ucfirst($p->status) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-extrabold text-success">₹{{ number_format($p->net_pay, 2) }}</div>
                        <div class="text-[11px] text-gray-400">Net Pay</div>
                    </div>
                </a>
            @empty
                <div class="text-sm text-gray-500 py-4 text-center">No payslips generated yet.</div>
            @endforelse
        </div>

        <div class="col-span-12 lg:col-span-6 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h2 class="font-bold text-lg mb-3">🎂 Birthdays this month</h2>
            @forelse($birthdaysThisMonth as $b)
                <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-dark-light">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr($b->first_name, 0, 1).substr($b->last_name ?? '', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate">{{ $b->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $b->department?->name }}</div>
                    </div>
                    <div class="text-xs text-primary font-semibold">{{ $b->date_of_birth->format('d M') }}</div>
                </div>
            @empty
                <div class="text-sm text-gray-500 py-4 text-center">No birthdays this month.</div>
            @endforelse
        </div>
    </div>

</x-layout.employee>
