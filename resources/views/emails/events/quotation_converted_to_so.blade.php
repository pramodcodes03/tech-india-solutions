@extends('emails.layouts.business')

@section('body')
    <h1>Order confirmed — thank you</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>Thank you for confirming our quotation. Your sales order has been created and is now scheduled for processing. We'll keep you posted as it progresses.</p>

    <table class="meta-table">
        <tr><td class="label">Quotation No.</td><td class="val">{{ $entity?->quotation_number }}</td></tr>
        <tr><td class="label">Sales Order No.</td><td class="val">{{ $context['order_number'] ?? '—' }}</td></tr>
        <tr><td class="label">Total Value</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-success">
        <strong>What happens next:</strong> Our team is preparing your order. You'll receive further updates and an invoice in due course.
    </div>

    <p>For any questions, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your business.<br><strong>{{ $business->name }}</strong></p>
@endsection
