@extends('emails.layouts.business')

@section('body')
    <h1>Proforma invoice from {{ $business->name }}</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>Please find below the details of the proforma invoice issued for your reference. The PDF is attached to this email and can be used to arrange the advance payment or for internal approvals.</p>

    <table class="meta-table">
        <tr><td class="label">Proforma No.</td><td class="val">{{ $entity?->proforma_number }}</td></tr>
        <tr><td class="label">Valid Until</td><td class="val">{{ $entity?->valid_until ? \Carbon\Carbon::parse($entity?->valid_until)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Total</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Note:</strong> A proforma invoice is a preliminary bill and is not a tax invoice. A final tax invoice will be issued once the order is processed.
    </div>

    <p>For any questions, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your business.<br><strong>{{ $business->name }}</strong></p>
@endsection
