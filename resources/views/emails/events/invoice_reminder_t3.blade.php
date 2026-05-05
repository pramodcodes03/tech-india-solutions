@extends('emails.layouts.business')

@section('body')
    @php
        $balance = ($entity?->grand_total ?? 0) - ($entity?->amount_paid ?? 0);
    @endphp

    <h1>Friendly reminder — invoice due in 3 days</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>This is a gentle reminder that the following invoice is due in 3 days. If you've already arranged the payment, please ignore this note.</p>

    <table class="meta-table">
        <tr><td class="label">Invoice No.</td><td class="val">{{ $entity?->invoice_number }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Balance Due</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($balance, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Heads up:</strong> Settling on or before the due date helps you avoid overdue notices and keeps your account in good standing.
    </div>

    <p>For payment details or any questions, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your business.<br><strong>{{ $business->name }}</strong></p>
@endsection
