@extends('emails.layouts.business')

@section('body')
    <h1>Invoice cancelled</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>We're writing to let you know that the following invoice from {{ $business->name }} has been cancelled. Our apologies for any inconvenience this may cause — please disregard the original invoice.</p>

    <table class="meta-table">
        <tr><td class="label">Invoice No.</td><td class="val">{{ $entity?->invoice_number }}</td></tr>
        <tr><td class="label">Invoice Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->invoice_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Total Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Status</td><td class="val">{{ ucfirst($entity?->status ?? 'Cancelled') }}</td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Please note:</strong> No payment is required against this invoice. If you have already paid, please get in touch and we will arrange a refund or credit at the earliest.
    </div>

    <p>For any clarifications, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>We appreciate your understanding.<br><strong>{{ $business->name }}</strong></p>
@endsection
