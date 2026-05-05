@extends('emails.layouts.business')

@section('body')
    <h1>Asset return logged</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The following asset has been marked as returned in the system. Thank you for the timely handover.</p>

    <table class="meta-table">
        <tr><td class="label">Asset Code</td><td class="val">{{ $context['asset_code'] ?? '—' }}</td></tr>
        <tr><td class="label">Returned Date</td><td class="val">{{ $entity?->returned_date ? \Carbon\Carbon::parse($entity?->returned_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Condition</td><td class="val">{{ ucfirst($entity?->condition ?? '—') }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Returned.</strong> The asset record has been closed. Any pending dues, if applicable, will be communicated separately.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
