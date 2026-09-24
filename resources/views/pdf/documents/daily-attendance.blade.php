@extends('pdf.layout')
@section('title', 'Daily Attendance — '.$date->format('d M Y'))
@section('doc-title', 'Daily Attendance Report')
@section('doc-sub', $date->format('l, d F Y'))
@section('doc-meta')
    <div class="row"><span class="label">Records</span> <span class="val">{{ $records->count() }}</span></div>
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    @php
        $tally = $records->groupBy('status')->map->count();
        $tiles = [
            'Present' => (int) ($tally['present'] ?? 0),
            'Half Day' => (int) ($tally['half_day'] ?? 0),
            'Absent' => (int) ($tally['absent'] ?? 0),
            'On Leave' => (int) ($tally['on_leave'] ?? 0),
            'Week Off' => (int) ($tally['week_off'] ?? 0),
            'Holiday' => (int) ($tally['holiday'] ?? 0),
        ];
    @endphp
    <table class="data" style="margin-bottom:10px;">
        <tr>
            @foreach($tiles as $label => $count)
                <td style="background:#f0f3f8; border:1px solid #e6e9ee; padding:7px 9px;">
                    <div style="font-size:7.5px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280;">{{ $label }}</div>
                    <div style="font-size:13px; font-weight:bold; color:#0f2b5b;">{{ $count }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Emp ID</th><th>Employee</th><th>Department</th>
                <th class="tc">Check In</th><th class="tc">Check Out</th>
                <th class="tr">Worked Hours</th><th class="tr">Break (min)</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $i => $r)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $r->employee?->employee_code }}</td>
                    <td>{{ $r->employee?->full_name }}</td>
                    <td>{{ $r->employee?->department?->name ?? '—' }}</td>
                    <td class="tc">{{ $r->check_in ? substr((string) $r->check_in, 0, 5) : '—' }}</td>
                    <td class="tc">{{ $r->check_out ? substr((string) $r->check_out, 0, 5) : '—' }}</td>
                    <td class="tr">{{ $r->worked_hours !== null ? number_format((float) $r->worked_hours, 2) : '—' }}</td>
                    <td class="tr">{{ $r->break_minutes ?: '—' }}</td>
                    <td>
                        @php
                            $badge = match ($r->status) {
                                'present' => 'badge-green', 'half_day' => 'badge-amber',
                                'absent' => 'badge-red', default => 'badge-grey',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', (string) $r->status)) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No attendance recorded on {{ $date->format('d M Y') }}.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('signature')@endsection
