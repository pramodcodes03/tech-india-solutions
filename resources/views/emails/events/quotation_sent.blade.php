@extends('emails.layouts.business')

@section('body')
    <h1>Your quotation from {{ $business->name }}</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>Thank you for considering {{ $business->name }}. Please find your quotation attached as a PDF. A summary of the offer is provided below for quick reference.</p>

    <table class="meta-table">
        <tr><td class="label">Quotation No.</td><td class="val">{{ $entity?->quotation_number }}</td></tr>
        <tr><td class="label">Quotation Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->quotation_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Valid Until</td><td class="val">{{ $entity?->valid_until ? \Carbon\Carbon::parse($entity?->valid_until)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Total</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Next step:</strong> Review the attached PDF and let us know if you'd like to proceed or if any adjustments are needed. We'll be glad to assist.
    </div>

    <p>For questions or to confirm the order, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>We look forward to working with you.<br><strong>{{ $business->name }}</strong></p>
@endsection
