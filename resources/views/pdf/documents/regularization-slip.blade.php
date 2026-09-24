@extends('pdf.layout')
@section('title', 'Regularization Approval Slip')
@section('doc-title', 'Attendance Regularization Approval')
@section('doc-meta')
    <div class="row"><span class="label">Date of record</span> <span class="val">{{ optional($request?->date)->format('d M Y') ?? '—' }}</span></div>
    <div class="row"><span class="label">Actioned</span> <span class="val">{{ optional($request?->actioned_at)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Employee</td><td class="v">{{ $request?->employee?->full_name ?? '—' }}</td>
            <td class="k">Employee ID</td><td class="v">{{ $request?->employee?->employee_code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Department</td><td class="v">{{ $request?->employee?->department?->name ?? '—' }}</td>
            <td class="k">Attendance date</td><td class="v">{{ optional($request?->date)->format('d F Y') ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Field</th><th>As recorded</th><th>As requested</th></tr></thead>
        <tbody>
            <tr>
                <td>Check in</td>
                <td>{{ $request?->original_check_in ? substr((string) $request->original_check_in, 0, 5) : '— not marked —' }}</td>
                <td style="font-weight:bold;">{{ $request?->requested_check_in ? substr((string) $request->requested_check_in, 0, 5) : '—' }}</td>
            </tr>
            <tr class="alt">
                <td>Check out</td>
                <td>{{ $request?->original_check_out ? substr((string) $request->original_check_out, 0, 5) : '— not marked —' }}</td>
                <td style="font-weight:bold;">{{ $request?->requested_check_out ? substr((string) $request->requested_check_out, 0, 5) : '—' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="panel mt">
        <div class="panel-title">Reason stated by the employee</div>
        <div style="font-size:9.5px;">{{ $request?->reason ?: '—' }}</div>
    </div>

    @if($request?->review_remarks ?? $request?->admin_remarks)
        <div class="panel mt">
            <div class="panel-title">Approver's remarks</div>
            <div style="font-size:9.5px;">{{ $request->review_remarks ?? $request->admin_remarks }}</div>
        </div>
    @endif

    <div class="mt-lg" style="font-size:10px;">
        This correction has been <strong>{{ strtoupper((string) ($request?->status ?? '—')) }}</strong>
        @if($request?->reviewer) by {{ $request->reviewer->display_name }} @endif
        @if($request?->actioned_at) on {{ $request->actioned_at->format('d F Y') }} @endif,
        and the attendance record for the date shown has been updated accordingly.
    </div>
@endsection
