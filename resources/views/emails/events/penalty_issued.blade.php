@extends('emails.layouts.business')

@section('body')
    <h1>Penalty issued</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is to inform you that a penalty has been recorded against your account. Details are summarised below — please contact HR if you have any questions or wish to dispute the entry.</p>

    <table class="meta-table">
        <tr><td class="label">Penalty Code</td><td class="val">{{ $entity?->penalty_code ?? '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
        <tr><td class="label">Issued Date</td><td class="val">{{ $entity?->issued_date ? \Carbon\Carbon::parse($entity?->issued_date)->format('d M Y') : '—' }}</td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Note:</strong> The penalty amount will be adjusted in the next applicable payroll cycle unless reviewed and reversed.
    </div>

    <p>Regards,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
