@extends('emails.layouts.business')

@section('body')
    <h1>Payment received — thank you</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>This is a confirmation that we have received your payment. Please retain this email as your receipt.</p>

    <table class="meta-table">
        @if(!empty($entity?->payment_number))
            <tr><td class="label">Receipt No.</td><td class="val">{{ $entity?->payment_number }}</td></tr>
        @endif
        <tr><td class="label">Amount Received</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Payment Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->payment_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Payment Method</td><td class="val">{{ ucfirst(str_replace('_', ' ', $entity?->payment_method ?? '—')) }}</td></tr>
        @if(!empty($context['invoice_number']))
            <tr><td class="label">Against Invoice</td><td class="val">{{ $context['invoice_number'] }}</td></tr>
        @endif
    </table>

    <div class="alert alert-success">
        <strong>All done.</strong> Your account has been updated. No further action is required from your side.
    </div>

    <p>For any questions about this payment, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your prompt payment.<br><strong>{{ $business->name }}</strong></p>
@endsection
