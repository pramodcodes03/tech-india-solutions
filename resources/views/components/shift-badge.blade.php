{{--
    Shift badge — one source of truth for "which shift is this employee on".

    Attendance status is derived from the shift window (start/end + grace), so
    HR needs the shift visible wherever they judge attendance: the daily list,
    the correction queue and the review screen. An employee with NO shift falls
    back to the total-hours rule instead — a materially different calculation —
    so that case is flagged in warning colour rather than left blank.

    Props:
      shift  — App\Models\Shift|null
      times  — show the start–end window next to the name (default true)
      stack  — render name and window on two lines (for narrow table cells)
--}}
@props(['shift' => null, 'times' => true, 'stack' => false])

@if($shift)
    @php
        $window = \Carbon\Carbon::parse($shift->start_time)->format('g:i A')
            .' – '.\Carbon\Carbon::parse($shift->end_time)->format('g:i A');
    @endphp
    <span {{ $attributes->merge(['class' => $stack ? 'block' : 'inline-flex items-center gap-1.5']) }}>
        <span class="badge bg-primary/10 text-primary whitespace-nowrap">{{ $shift->name }}</span>
        @if($times)
            <span class="text-xs text-gray-500 whitespace-nowrap {{ $stack ? 'block mt-0.5' : '' }}">{{ $window }}</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => 'badge bg-warning/10 text-warning whitespace-nowrap']) }}
          title="No shift assigned — attendance is judged on total hours worked, not on a shift window">No shift</span>
@endif
