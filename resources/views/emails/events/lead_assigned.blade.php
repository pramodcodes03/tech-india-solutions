@extends('emails.layouts.business')

@section('body')
    <h1>New lead assigned to you</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A new lead has just been routed to you. Please reach out promptly — the first 24 hours often make the difference. Lead summary below.</p>

    <table class="meta-table">
        <tr><td class="label">Lead Name</td><td class="val">{{ $entity?->name ?? '—' }}</td></tr>
        <tr><td class="label">Source</td><td class="val">{{ $entity?->source ?? '—' }}</td></tr>
        <tr><td class="label">Phone</td><td class="val">{{ $entity?->phone ?? '—' }}</td></tr>
        <tr><td class="label">Email</td><td class="val">{{ $entity?->email ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val">{{ ucfirst(str_replace('_', ' ', $entity?->status ?? 'new')) }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Action:</strong> Log into the CRM, review the lead details, and schedule the first contact today.
    </div>

    <p>Happy selling.<br><strong>{{ $business->name }}</strong></p>
@endsection
