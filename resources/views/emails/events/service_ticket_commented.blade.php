@extends('emails.layouts.business')

@section('body')
    <h1>New comment on your service ticket</h1>

    <p>Hi {{ $recipientName ?? ($entity?->customer->name ?? 'Customer') }},</p>

    <p>Our team has posted a new comment on your service ticket. A short excerpt is included below — log in to the helpdesk for the full conversation.</p>

    <table class="meta-table">
        <tr><td class="label">Ticket No.</td><td class="val">{{ $entity?->ticket_number }}</td></tr>
        <tr><td class="label">Subject</td><td class="val">{{ $entity?->subject }}</td></tr>
        <tr><td class="label">From</td><td class="val">{{ $context['author'] ?? 'Support team' }}</td></tr>
    </table>

    @if(!empty($context['comment_excerpt']))
        <div class="alert alert-info">
            <strong>Comment:</strong> {{ $context['comment_excerpt'] }}
        </div>
    @endif

    <p>Reply to this email or open the ticket to continue the conversation.</p>

    <p>Thank you,<br><strong>{{ $business->name }}</strong></p>
@endsection
