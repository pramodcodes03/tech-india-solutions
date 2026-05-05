@extends('emails.layouts.business')

@section('body')
    @php
        $balance = ($entity?->grand_total ?? 0) - ($entity?->amount_paid ?? 0);
        $daysOverdue = $context['days_overdue'] ?? (
            $entity?->due_date ? max(0, \Carbon\Carbon::parse($entity?->due_date)->diffInDays(now(), false)) : 0
        );
    @endphp

    <h1>Invoice overdue — action required</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>Our records show that the following invoice is now overdue. We'd appreciate your prompt attention to settle the balance at the earliest.</p>

    <table class="meta-table">
        <tr><td class="label">Invoice No.</td><td class="val">{{ $entity?->invoice_number }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Days Overdue</td><td class="val">{{ (int) $daysOverdue }}</td></tr>
        <tr><td class="label">Balance Due</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($balance, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-danger">
        <strong>Action required:</strong> Please arrange payment immediately, or reply to this email if you need to discuss the dues. If payment has already been made, kindly share the transaction reference.
    </div>

    <p>For payment details or any questions, contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your prompt attention.<br><strong>{{ $business->name }}</strong></p>
@endsection
