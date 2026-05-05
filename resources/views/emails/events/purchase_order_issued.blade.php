@extends('emails.layouts.business')

@section('body')
    <h1>Purchase order from {{ $business->name }}</h1>

    <p>Hi {{ $recipientName ?? ($entity?->vendor->name ?? 'Vendor') }},</p>

    <p>Please find attached our purchase order. Kindly acknowledge receipt and confirm the expected delivery schedule. A summary of the order is provided below.</p>

    <table class="meta-table">
        <tr><td class="label">PO No.</td><td class="val">{{ $entity?->po_number }}</td></tr>
        <tr><td class="label">PO Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->po_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Expected Delivery</td><td class="val">{{ $entity?->expected_date ? \Carbon\Carbon::parse($entity?->expected_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Vendor</td><td class="val">{{ $entity?->vendor->name ?? '—' }}</td></tr>
        <tr><td class="label">Total</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Next step:</strong> Please confirm receipt and share an updated delivery timeline at your earliest convenience.
    </div>

    <p>For any clarifications, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your continued partnership.<br><strong>{{ $business->name }}</strong></p>
@endsection
