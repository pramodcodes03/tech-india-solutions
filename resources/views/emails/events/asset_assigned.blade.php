@extends('emails.layouts.business')

@section('body')
    <h1>Asset assigned to you</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A company asset has been assigned to you. Please verify the asset on receipt and report any issues to the admin team.</p>

    <table class="meta-table">
        <tr><td class="label">Asset Code</td><td class="val">{{ $context['asset_code'] ?? '—' }}</td></tr>
        <tr><td class="label">Asset Name</td><td class="val">{{ $context['asset_name'] ?? '—' }}</td></tr>
        <tr><td class="label">Assigned Date</td><td class="val">{{ $entity?->assigned_date ? \Carbon\Carbon::parse($entity?->assigned_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Expected Return</td><td class="val">{{ $entity?->expected_return_date ? \Carbon\Carbon::parse($entity?->expected_return_date)->format('d M Y') : '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Reminder:</strong> You are responsible for the safe use and timely return of this asset. Refer to the asset policy for full guidelines.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
