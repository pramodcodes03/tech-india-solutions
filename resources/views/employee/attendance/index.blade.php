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
            ['Late', $summary['late'], 'warning'],
            ['On Leave', $summary['on_leave'], 'info'],
            ['Holidays', $summary['holidays'], 'primary'],
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
                        'late'     => 'bg-warning/40 text-warning',
                        'half_day' => 'bg-warning/30 text-warning',
                        'absent'   => 'bg-danger/30 text-danger',
                        'on_leave' => 'bg-info/30 text-info',
                        'holiday'  => 'bg-primary/30 text-primary',
                        'comp_off' => 'bg-purple-200 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200',
                        'week_off' => 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
                        default    => 'bg-gray-100 dark:bg-gray-800 text-gray-400',
                    };
                @endphp
                <div class="aspect-square rounded-lg {{ $bg }} flex flex-col items-center justify-center text-xs p-1 leading-tight">
                    <div class="font-bold">{{ $d->day }}</div>
                    @if($rec && $rec->check_in)
                        <div class="text-[9px] opacity-70 mt-0.5">{{ \Carbon\Carbon::parse($rec->check_in)->format('g:i a') }}</div>
                        @if($rec->check_out)
                            <div class="text-[9px] opacity-70">{{ \Carbon\Carbon::parse($rec->check_out)->format('g:i a') }}</div>
                        @else
                            <div class="text-[9px] font-bold text-danger" title="Missed punch-out">MISS</div>
                        @endif
                    @endif
                </div>
            @endfor
        </div>
        <div class="flex flex-wrap gap-3 mt-4 text-xs">
            <span><span class="inline-block w-3 h-3 rounded bg-success/30 mr-1"></span>Present</span>
            <span><span class="inline-block w-3 h-3 rounded bg-warning/30 mr-1"></span>Half/Late</span>
            <span><span class="inline-block w-3 h-3 rounded bg-danger/30 mr-1"></span>Absent</span>
            <span><span class="inline-block w-3 h-3 rounded bg-info/30 mr-1"></span>On Leave</span>
            <span><span class="inline-block w-3 h-3 rounded bg-primary/30 mr-1"></span>Holiday</span>
            <span><span class="inline-block w-3 h-3 rounded bg-purple-300 mr-1"></span>Comp-off</span>
            <span><span class="inline-block w-3 h-3 rounded bg-gray-300 mr-1"></span>Week off</span>
        </div>
    </div>
</x-layout.employee>
