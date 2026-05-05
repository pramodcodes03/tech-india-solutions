@extends('emails.layouts.business')

@section('body')
    <h1>Asset maintenance scheduled</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Maintenance has been scheduled for the following asset. Please plan around the indicated date and ensure the asset is available for service.</p>

    <table class="meta-table">
        <tr><td class="label">Asset Code</td><td class="val">{{ $context['asset_code'] ?? '—' }}</td></tr>
        <tr><td class="label">Scheduled Date</td><td class="val">{{ $entity?->scheduled_date ? \Carbon\Carbon::parse($entity?->scheduled_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Maintenance Type</td><td class="val">{{ ucfirst(str_replace('_', ' ', $entity?->type ?? '—')) }}</td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Heads up:</strong> The asset may be unavailable during maintenance. Please coordinate with the admin team for any conflicts.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
