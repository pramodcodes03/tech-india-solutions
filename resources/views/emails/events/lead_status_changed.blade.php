@extends('emails.layouts.business')

@section('body')
    <h1>Lead status updated</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The status of one of your leads has changed. A quick summary is provided below — please check the CRM for full context and next steps.</p>

    <table class="meta-table">
        <tr><td class="label">Lead Name</td><td class="val">{{ $entity?->name ?? '—' }}</td></tr>
        <tr><td class="label">Previous Status</td><td class="val">{{ ucfirst(str_replace('_', ' ', $context['old_status'] ?? '—')) }}</td></tr>
        <tr><td class="label">New Status</td><td class="val">{{ ucfirst(str_replace('_', ' ', $context['new_status'] ?? ($entity?->status ?? '—'))) }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> Update notes in the CRM to keep the team aligned on this opportunity.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
