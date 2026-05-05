@extends('emails.layouts.business')

@section('body')
    @php
        $newStatus = $context['new_status'] ?? ($entity?->status ?? 'updated');
        $alertClass = match(strtolower((string) $newStatus)) {
            'delivered', 'completed', 'shipped' => 'alert-success',
            'cancelled', 'rejected'              => 'alert-danger',
            'on_hold', 'pending'                 => 'alert-warning',
            default                              => 'alert-info',
        };
    @endphp

    <h1>Order status update</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>We're writing to keep you informed about the latest status of your order. Details are summarised below.</p>

    <table class="meta-table">
        <tr><td class="label">Order No.</td><td class="val">{{ $entity?->order_number }}</td></tr>
        <tr><td class="label">New Status</td><td class="val">{{ ucfirst(str_replace('_', ' ', $newStatus)) }}</td></tr>
        <tr><td class="label">Order Total</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert {{ $alertClass }}">
        <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $newStatus)) }}. We'll send another update if anything changes.
    </div>

    <p>For any questions, reply to this email or contact us at <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>.</p>

    <p>Thank you for your business.<br><strong>{{ $business->name }}</strong></p>
@endsection
