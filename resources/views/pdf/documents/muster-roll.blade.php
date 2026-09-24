@php
    // Codes come from App\Models\Attendance so the register, the calendar and
    // the monthly summary all speak the same vocabulary — WO, HD/WO, HD/L and
    // the rest — rather than each view inventing its own letters.
    $colors = [
        'P' => '#047857', 'HD' => '#b45309', 'HD/L' => '#0d9488', 'HD/WO' => '#4f46e5',
        'A' => '#b91c1c', 'L' => '#2563eb', 'WO' => '#94a3b8',
        'H' => '#94a3b8', 'CO' => '#7c3aed',
    ];
@endphp
@extends('pdf.layout')
@section('title', 'Muster Roll — '.$period)
@section('doc-title', 'Monthly Attendance Register')
@section('doc-sub', 'Muster roll for '.$period)
@section('doc-meta')
    <div class="row"><span class="label">Employees</span> <span class="val">{{ $employees->count() }}</span></div>
    <div class="row"><span class="label">Days</span> <span class="val">{{ $daysInMonth }}</span></div>
@endsection

@push('styles')
<style>
    table.muster { width: 100%; border-collapse: collapse; }
    table.muster th, table.muster td { border: 1px solid #dfe4ea; padding: 2px 0; font-size: 5.6px; text-align: center; word-break: break-all; }
    table.muster th { background: #0f2b5b; color: #fff; }
    table.muster td.name { text-align: left; font-size: 7px; padding-left: 4px; white-space: nowrap; }
    table.muster th.name { text-align: left; padding-left: 4px; }
    table.muster td.sun { background: #f4f6f9; }
    .legend span { display: inline-block; margin-right: 9px; font-size: 8px; }
    .legend b { display: inline-block; min-width: 12px; text-align: center; }
</style>
@endpush

@section('content')
    <table class="muster">
        <thead>
            <tr>
                <th class="name" style="width:120px;">Employee</th>
                <th style="width:52px;">Emp ID</th>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php $day = $start->copy()->day($d); @endphp
                    <th>{{ $d }}<br><span style="font-weight:normal; opacity:.7;">{{ $day->format('D')[0] }}</span></th>
                @endfor
                <th style="width:22px;">P</th><th style="width:22px;">A</th>
                <th style="width:22px;">L</th><th style="width:24px;">WO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                @php
                    $dayStatuses = $statuses->get($employee->id, collect());
                    $tally = ['P' => 0, 'A' => 0, 'L' => 0, 'WO' => 0];
                @endphp
                <tr>
                    <td class="name">{{ $employee->full_name }}</td>
                    <td>{{ $employee->employee_code }}</td>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $status = $dayStatuses->get($d);
                            $code = $status && $status !== 'future'
                                ? \App\Models\Attendance::statusCode($status)
                                : '·';

                            // A half-day of any kind is half a day present.
                            $tally['P'] += match ($status) {
                                'present' => 1,
                                'half_day', 'half_day_leave', 'half_day_week_off' => 0.5,
                                default => 0,
                            };
                            $tally['A'] += $status === 'absent' ? 1 : 0;
                            $tally['L'] += match ($status) {
                                'on_leave' => 1,
                                'half_day_leave' => 0.5,
                                default => 0,
                            };
                            $tally['WO'] += \App\Models\Attendance::WEEK_OFF_WEIGHT[$status] ?? 0;

                            $isSunday = $start->copy()->day($d)->isSunday();
                        @endphp
                        <td @class(['sun' => $isSunday]) style="color: {{ $colors[$code] ?? '#c2c9d2' }}; font-weight: bold;">{{ $code }}</td>
                    @endfor
                    @php $num = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.'); @endphp
                    <td style="font-weight:bold;">{{ $num($tally['P']) }}</td>
                    <td style="font-weight:bold;">{{ $num($tally['A']) }}</td>
                    <td style="font-weight:bold;">{{ $num($tally['L']) }}</td>
                    <td style="font-weight:bold;">{{ $num($tally['WO']) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $daysInMonth + 6 }}" class="empty">No employees to show.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend mt">
        <span><b style="color:#047857;">P</b> Present</span>
        <span><b style="color:#b45309;">HD</b> Half Day</span>
        <span><b style="color:#0d9488;">HD/L</b> Half Day / Leave</span>
        <span><b style="color:#4f46e5;">HD/WO</b> Half Day / Week Off</span>
        <span><b style="color:#2563eb;">L</b> Leave</span>
        <span><b style="color:#94a3b8;">WO</b> Week Off</span>
        <span><b style="color:#94a3b8;">H</b> Holiday</span>
        <span><b style="color:#7c3aed;">CO</b> Comp Off</span>
        <span><b style="color:#b91c1c;">A</b> Absent</span>
        <span><b style="color:#c2c9d2;">·</b> No record</span>
    </div>
@endsection

@section('signature')@endsection
