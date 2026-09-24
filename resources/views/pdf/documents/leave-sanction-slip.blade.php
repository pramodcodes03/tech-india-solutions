@extends('pdf.layout')
@section('title', 'Leave Sanction Slip — '.($request?->request_code ?? ''))
@section('doc-title', 'Leave Sanction Slip')
@section('doc-meta')
    <div class="row"><span class="label">Request</span> <span class="val">{{ $request?->request_code ?? '—' }}</span></div>
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
            <td class="k">Leave type</td><td class="v">{{ $request?->leaveType?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">From</td><td class="v">{{ optional($request?->from_date)->format('d F Y') ?? '—' }}</td>
            <td class="k">To</td><td class="v">{{ optional($request?->to_date)->format('d F Y') ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Particulars</th><th class="tr">Days</th></tr></thead>
        <tbody>
            <tr><td>Total leave applied for</td><td class="tr">{{ number_format((float) ($request?->days ?? 0), 1) }}</td></tr>
            <tr class="alt"><td>Sanctioned as paid leave</td><td class="tr">{{ number_format((float) ($request?->paid_days ?? 0), 1) }}</td></tr>
            <tr><td>Sanctioned as unpaid (loss of pay)</td><td class="tr">{{ number_format((float) ($request?->unpaid_days ?? 0), 1) }}</td></tr>
        </tbody>
    </table>

    @if($request?->is_combined && $request->splits->isNotEmpty())
        {{-- Combined Leave: the slip has to show which balance each part came
             from, or the employee cannot reconcile it against their leave card. --}}
        <div class="mt">
            <div class="panel-title" style="margin-bottom:5px;">Combined leave break-up</div>
            <table class="data">
                <thead><tr><th>Leave type</th><th class="tr">Days</th><th class="tr">Paid</th><th class="tr">Unpaid</th></tr></thead>
                <tbody>
                    @foreach($request->splits as $i => $split)
                        <tr @class(['alt' => $i % 2 === 1])>
                            <td>{{ $split->leaveType?->name ?? '—' }}</td>
                            <td class="tr">{{ number_format((float) $split->days, 1) }}</td>
                            <td class="tr">{{ number_format((float) $split->paid_days, 1) }}</td>
                            <td class="tr">{{ number_format((float) $split->unpaid_days, 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="panel mt">
        <div class="panel-title">Reason stated by the employee</div>
        <div style="font-size:9.5px;">{{ $request?->reason ?: '—' }}</div>
    </div>

    @if($request?->approver_remarks)
        <div class="panel mt">
            <div class="panel-title">Approver's remarks</div>
            <div style="font-size:9.5px;">{{ $request->approver_remarks }}</div>
        </div>
    @endif

    <div class="mt-lg" style="font-size:10px;">
        The leave applied for is hereby <strong>{{ strtoupper((string) ($request?->status ?? '—')) }}</strong>
        @if($request?->approver_name) by {{ $request->approver_name }} @endif
        @if($request?->actioned_at) on {{ $request->actioned_at->format('d F Y') }} @endif.
        @if(($request?->unpaid_days ?? 0) > 0)
            The unpaid portion will be deducted as loss of pay in the applicable payroll month.
        @endif
    </div>
@endsection
