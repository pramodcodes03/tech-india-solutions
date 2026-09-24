<x-layout.employee title="My Attendance">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Attendance</h1>
        <form method="GET" class="flex gap-2">
            <select name="month" class="form-select">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>
                @endforeach
            </select>
            <select name="year" class="form-select">
                @foreach(\App\Support\HrYears::forAttendance() as $y)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Go</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 mb-6">
        @foreach([
            ['Present', $summary['present'], 'success'],
            ['Absent', $summary['absent'], 'danger'],
            ['Half-day', $summary['half_day'], 'warning'],
            // Total leave days taken (paid + unpaid) — includes half-day leaves,
            // so a 0.5 half-day leave is reflected here instead of showing 0.
            ['On Leave', $summary['paid_leave_days'] + $summary['unpaid_leave_days'], 'info'],
            ['Holidays', $summary['holidays'], 'primary'],
            // A full week-off counts 1, a half-day week-off 0.5 — the same
            // figure the admin Monthly Summary's "Week Off" column shows, so
            // the employee and HR never read different numbers for the month.
            ['Week Off', $summary['week_offs'], 'secondary'],
            ['Paid Days', $summary['paid_days'], 'success'],
            ['LOP Days', $summary['lop_days'], 'danger'],
        ] as [$label, $val, $color])
            <div class="p-3 rounded-lg bg-white dark:bg-[#1b2e4b] shadow text-center">
                <div class="text-xs text-gray-500 font-semibold">{{ $label }}</div>
                <div class="text-xl font-extrabold text-{{ $color }} mt-1">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    @php
        $start = \Carbon\Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $offset = $start->dayOfWeek; // 0 Sun .. 6 Sat
    @endphp
    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <h3 class="font-bold mb-3">{{ $start->format('F Y') }}</h3>
        <div class="grid grid-cols-7 gap-1 text-center">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div class="text-[11px] font-bold uppercase text-gray-500 py-1">{{ $d }}</div>
            @endforeach
            @for($i = 0; $i < $offset; $i++)<div></div>@endfor
            @for($d = $start->copy(); $d->lte($end); $d->addDay())
                @php
                    $key = $d->toDateString();
                    $rec = $records->get($key);
                    // Trust the service's resolved status — it already maps
                    // raw DB variants ('weekend' → 'week_off', etc.) to the
                    // canonical strings the colour palette below matches on.
                    // The rec fallback is only there in case dayStatuses is
                    // missing a key, which shouldn't normally happen.
                    $status = $dayStatuses[$key] ?? $rec?->status;
                    // Calendar cell tints — bumped from /15-/20 to /30-/40 so
                    // the status colours are visible at a glance instead of
                    // looking like the whole month is one washed-out beige.
                    // Matches the legend swatches below (also /30).
                    $bg = match($status) {
                        'present'  => 'bg-success/30 text-success',
                        'half_day' => 'bg-warning/30 text-warning',
                        // Half worked + half sanctioned leave. Teal keeps it
                        // apart from a plain half-day (amber) and from full
                        // leave (blue) at a glance.
                        'half_day_leave' => 'bg-teal-200 dark:bg-teal-900/50 text-teal-800 dark:text-teal-200',
                        // Half worked + half week-off gets its own colour, as
                        // the client asked, so it is never mistaken for either.
                        'half_day_week_off' => 'bg-indigo-200 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200',
                        // Half sanctioned, half unaccounted for. Striped
                        // leave-blue into absent-red so the cell reads as both
                        // at a glance rather than picking a side.
                        'half_day_leave_absent' => 'bg-gradient-to-br from-info/40 to-danger/40 text-gray-800 dark:text-gray-100',
                        'leave_week_off' => 'bg-cyan-200 dark:bg-cyan-900/50 text-cyan-800 dark:text-cyan-200',
                        'absent'   => 'bg-danger/30 text-danger',
                        'on_leave' => 'bg-info/30 text-info',
                        'holiday'  => 'bg-primary/30 text-primary',
                        'comp_off' => 'bg-purple-200 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200',
                        'week_off' => 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                        default    => 'bg-gray-100 dark:bg-gray-800 text-gray-400',
                    };

                    // Which half was worked, so the cell can say so rather than
                    // leaving the duty window ambiguous.
                    $halfLabel = match($rec?->half_day_portion) {
                        'first_half'  => 'First half',
                        'second_half' => 'Second half',
                        default       => null,
                    };
                    $statusLabel = \App\Models\Attendance::statusLabel($status);
                @endphp
                <div class="aspect-square rounded-lg {{ $bg }} flex flex-col items-center justify-center text-xs p-1 leading-tight text-center"
                     title="{{ $statusLabel }}{{ $halfLabel ? ' · '.$halfLabel.' worked' : '' }}">
                    <div class="font-bold">{{ $d->day }}</div>

                    @if($rec && $rec->check_in)
                        <div class="text-[9px] opacity-70 mt-0.5">{{ \Carbon\Carbon::parse($rec->check_in)->format('g:i a') }}</div>
                        @if($rec->check_out)
                            <div class="text-[9px] opacity-70">{{ \Carbon\Carbon::parse($rec->check_out)->format('g:i a') }}</div>
                        @else
                            <div class="text-[9px] font-bold text-danger" title="Missed punch-out">MISS</div>
                        @endif
                    @endif

                    {{-- Split days say what they are on the cell itself; a plain
                         present/absent day is already obvious from its colour. --}}
                    @if($status === 'half_day_leave_absent')
                        {{-- Spelled out rather than abbreviated: this is the
                             cell that used to render as one solid blue block
                             saying "Leave", hiding the half nobody accounted
                             for. Both halves are named so neither is missed. --}}
                        <div class="text-[8px] font-bold leading-none mt-0.5">0.5 Leave</div>
                        <div class="text-[8px] font-bold leading-none">0.5 Absent</div>
                    @elseif(in_array($status, ['half_day', 'half_day_leave', 'half_day_week_off', 'leave_week_off'], true))
                        <div class="text-[8px] font-bold uppercase mt-0.5 leading-none">
                            {{ \App\Models\Attendance::statusCode($status) }}
                        </div>
                        @if($halfLabel)
                            <div class="text-[8px] opacity-70 leading-none">{{ $halfLabel }}</div>
                        @endif
                    @endif
                </div>
            @endfor
        </div>
        <div class="flex flex-wrap gap-3 mt-4 text-xs">
            <span><span class="inline-block w-3 h-3 rounded bg-success/30 mr-1"></span>Present</span>
            <span><span class="inline-block w-3 h-3 rounded bg-warning/30 mr-1"></span>Half-day</span>
            <span><span class="inline-block w-3 h-3 rounded bg-teal-200 mr-1"></span>Half Day / Leave</span>
            <span><span class="inline-block w-3 h-3 rounded bg-indigo-200 mr-1"></span>Half Day / Week Off</span>
            <span><span class="inline-block w-3 h-3 rounded bg-gradient-to-br from-info/40 to-danger/40 mr-1"></span>0.5 Leave / 0.5 Absent</span>
            <span><span class="inline-block w-3 h-3 rounded bg-cyan-200 mr-1"></span>Leave / Week Off</span>
            <span><span class="inline-block w-3 h-3 rounded bg-danger/30 mr-1"></span>Absent</span>
            <span><span class="inline-block w-3 h-3 rounded bg-info/30 mr-1"></span>On Leave</span>
            <span><span class="inline-block w-3 h-3 rounded bg-primary/30 mr-1"></span>Holiday</span>
            <span><span class="inline-block w-3 h-3 rounded bg-purple-300 mr-1"></span>Comp-off</span>
            <span><span class="inline-block w-3 h-3 rounded bg-gray-300 mr-1"></span>Week off</span>
        </div>
    </div>
</x-layout.employee>
