@extends('emails.layouts.business')

@section('body')
    <h1>Service ticket received</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A new service ticket has been logged. Our team will review it shortly and respond with the next steps. Ticket details are summarised below.</p>

    <table class="meta-table">
        <tr><td class="label">Ticket No.</td><td class="val">{{ $entity?->ticket_number }}</td></tr>
        <tr><td class="label">Subject</td><td class="val">{{ $entity?->subject }}</td></tr>
        <tr><td class="label">Priority</td><td class="val">{{ ucfirst($entity?->priority ?? 'normal') }}</td></tr>
        <tr><td class="label">Customer</td><td class="val">{{ $entity?->customer->name ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>What's next:</strong> You will receive further updates as the ticket progresses. Please reply to this email to add information or attachments.
    </div>

    <p>Thank you for reaching out.<br><strong>{{ $business->name }}</strong></p>
@endsection
