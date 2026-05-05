@extends('emails.layouts.business')

@section('body')
    <h1>Asset maintenance completed</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The scheduled maintenance for the following asset has been completed. The asset is now back in service.</p>

    <table class="meta-table">
        <tr><td class="label">Asset Code</td><td class="val">{{ $context['asset_code'] ?? '—' }}</td></tr>
        <tr><td class="label">Completed Date</td><td class="val">{{ $entity?->completed_date ? \Carbon\Carbon::parse($entity?->completed_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Cost</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->cost ?? 0, 2) }}</td></tr>
        <tr><td class="label">Vendor</td><td class="val">{{ $entity?->vendor ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Done.</strong> All maintenance records have been updated. Please report any post-service issues promptly.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
