@extends('pdf.layout')
@section('title', 'Comp-off Approval Slip')
@section('doc-title', 'Compensatory Off Approval')
@section('doc-meta')
    <div class="row"><span class="label">Worked on</span> <span class="val">{{ optional($request?->worked_on)->format('d M Y') ?? '—' }}</span></div>
    <div class="row"><span class="label">Comp-off on</span> <span class="val">{{ optional($request?->comp_date)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Employee</td><td class="v">{{ $request?->employee?->full_name ?? '—' }}</td>
            <td class="k">Employee ID</td><td class="v">{{ $request?->employee?->employee_code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Department</td><td class="v">{{ $request?->employee?->department?->name ?? '—' }}</td>
            <td class="k">Status</td><td class="v">{{ ucfirst((string) ($request?->status ?? '—')) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Particulars</th><th>Date</th><th>Day</th></tr></thead>
        <tbody>
            <tr>
                <td>Worked on (non-working day)</td>
                <td style="font-weight:bold;">{{ optional($request?->worked_on)->format('d F Y') ?? '—' }}</td>
                <td>{{ optional($request?->worked_on)->format('l') ?? '—' }}</td>
            </tr>
            <tr class="alt">
                <td>Compensatory off availed on</td>
                <td style="font-weight:bold;">{{ optional($request?->comp_date)->format('d F Y') ?? '—' }}</td>
                <td>{{ optional($request?->comp_date)->format('l') ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="panel mt">
        <div class="panel-title">Reason stated by the employee</div>
        <div style="font-size:9.5px;">{{ $request?->reason ?: '—' }}</div>
    </div>

    @if($request?->admin_remarks)
        <div class="panel mt">
            <div class="panel-title">Approver's remarks</div>
            <div style="font-size:9.5px;">{{ $request->admin_remarks }}</div>
        </div>
    @endif

    <div class="mt-lg" style="font-size:10px;">
        The compensatory off claimed against the additional day worked is hereby
        <strong>{{ strtoupper((string) ($request?->status ?? '—')) }}</strong>
        @if($request?->approver_name) by {{ $request->approver_name }} @endif
        @if($request?->actioned_at) on {{ $request->actioned_at->format('d F Y') }} @endif.
        The comp-off date will be treated as a paid day off and will not be deducted from any leave balance.
    </div>
@endsection
