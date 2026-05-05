@extends('emails.layouts.business')

@section('body')
    <h1>New invoice from {{ $business->name }}</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>A new invoice has been issued for you. Details below — the PDF is attached to this email.</p>

    <table class="meta-table">
        <tr><td class="label">Invoice No.</td><td class="val">{{ $entity?->invoice_number }}</td></tr>
        <tr><td class="label">Invoice Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->invoice_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ $entity?->due_date ? \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Total Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? $entity?->amount ?? 0, 2) }}</span></td></tr>
        @if(isset($entity?->amount_paid) && $entity?->amount_paid > 0)
            <tr><td class="label">Amount Paid</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount_paid, 2) }}</td></tr>
            <tr><td class="label">Balance Due</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format(($entity?->grand_total ?? 0) - $entity?->amount_paid, 2) }}</td></tr>
        @endif
    </table>

    <div class="alert alert-info">
        <strong>Payment terms:</strong> Please settle by the due date to avoid late payment reminders. If you've already paid, kindly disregard this notice.
    </div>

    <p>For any questions about this invoice, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your business.<br><strong>{{ $business->name }}</strong></p>
@endsection
