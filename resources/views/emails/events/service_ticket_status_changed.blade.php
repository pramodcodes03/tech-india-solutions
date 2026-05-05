@extends('emails.layouts.business')

@section('body')
    @php
        $newStatus = $context['new_status'] ?? ($entity?->status ?? 'updated');
        $alertClass = match(strtolower((string) $newStatus)) {
            'resolved', 'closed'   => 'alert-success',
            'reopened', 'on_hold'  => 'alert-warning',
            default                => 'alert-info',
        };
    @endphp

    <h1>Service ticket status updated</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>The status of your service ticket has been updated. Latest details are summarised below.</p>

    <table class="meta-table">
        <tr><td class="label">Ticket No.</td><td class="val">{{ $entity?->ticket_number }}</td></tr>
        <tr><td class="label">New Status</td><td class="val">{{ ucfirst(str_replace('_', ' ', $newStatus)) }}</td></tr>
    </table>

    <div class="alert {{ $alertClass }}">
        <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $newStatus)) }}. If you have additional questions, reply to this email and we'll be happy to help.
    </div>

    <p>Thank you for your patience.<br><strong>{{ $business->name }}</strong></p>
@endsection
