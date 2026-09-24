@php use App\Support\AmountInWords; $payable = (float) ($claim?->approved_amount ?? $claim?->amount ?? 0); @endphp
@extends('pdf.layout')
@section('title', 'Reimbursement Voucher — '.($claim?->claim_code ?? ''))
@section('doc-title', 'Reimbursement Voucher')
@section('doc-meta')
    <div class="row"><span class="label">Voucher</span> <span class="val">{{ $claim?->claim_code ?? '—' }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ optional($claim?->claim_date)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Paid to</td><td class="v">{{ $claim?->employee?->full_name ?? '—' }}</td>
            <td class="k">Employee ID</td><td class="v">{{ $claim?->employee?->employee_code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Department</td><td class="v">{{ $claim?->employee?->department?->name ?? '—' }}</td>
            <td class="k">Category</td><td class="v">{{ $claim?->category?->name ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Particulars</th><th class="tr">Claimed (₹)</th><th class="tr">Approved (₹)</th></tr></thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-weight:bold;">{{ $claim?->title ?? '—' }}</div>
                    @if($claim?->purpose)<div class="muted" style="margin-top:2px;">{{ $claim->purpose }}</div>@endif
                </td>
                <td class="tr">{{ number_format((float) ($claim?->amount ?? 0), 2) }}</td>
                <td class="tr">{{ number_format($payable, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr><td colspan="2">Amount payable</td><td class="tr">{{ number_format($payable, 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="words mt">
        <span class="lbl">Amount in words:</span> <strong>{{ AmountInWords::currency($payable) }}</strong>
    </div>

    <table class="kv mt">
        <tr><td class="k">Status</td><td class="v">{{ ucfirst((string) ($claim?->status ?? '—')) }}</td></tr>
        <tr><td class="k">Reviewed by</td><td class="v">{{ $claim?->reviewer?->display_name ?? '—' }}</td></tr>
        @if($claim?->disbursed_at)
            <tr><td class="k">Disbursed on</td><td class="v">{{ $claim->disbursed_at->format('d M Y') }}</td></tr>
        @endif
        @if($claim?->payment_reference)
            <tr><td class="k">Payment reference</td><td class="v">{{ $claim->payment_reference }}</td></tr>
        @endif
    </table>

    @if($claim?->review_remarks)
        <div class="panel mt"><div class="panel-title">Review remarks</div><div style="font-size:9.5px;">{{ $claim->review_remarks }}</div></div>
    @endif

    <table class="sign-wrap">
        <tr>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Received by</div><div class="sign-role">{{ $claim?->employee?->full_name ?? '' }}</div></div>
            </td>
            <td></td>
            <td class="sign-box">
                <div class="sign-img">
                    @if($business?->signature_path && is_file(storage_path('app/public/'.$business->signature_path)))
                        <img src="{{ storage_path('app/public/'.$business->signature_path) }}" alt="" />
                    @endif
                </div>
                <div class="sign-line">
                    <div class="sign-name">{{ $business?->signatory_name ?: 'Authorised Signatory' }}</div>
                    <div class="sign-role">{{ $business?->signatory_role ?: 'Accounts' }}</div>
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
