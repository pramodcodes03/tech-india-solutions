@extends('emails.layouts.business')

@section('body')
    <h1>Service ticket assigned to you</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A service ticket has been assigned to you. Please review the details and reach out to the customer at the earliest.</p>

    <table class="meta-table">
        <tr><td class="label">Ticket No.</td><td class="val">{{ $entity?->ticket_number }}</td></tr>
        <tr><td class="label">Subject</td><td class="val">{{ $entity?->subject }}</td></tr>
        <tr><td class="label">Customer</td><td class="val">{{ $entity?->customer->name ?? '—' }}</td></tr>
        <tr><td class="label">Priority</td><td class="val">{{ ucfirst($entity?->priority ?? 'normal') }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Action:</strong> Open the ticket in the helpdesk, set an SLA-compliant target, and acknowledge the customer.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
