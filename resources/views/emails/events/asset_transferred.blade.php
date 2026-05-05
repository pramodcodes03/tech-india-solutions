@extends('emails.layouts.business')

@section('body')
    <h1>Asset transfer recorded</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>An asset has been transferred between employees. The records have been updated accordingly — please review the details below.</p>

    <table class="meta-table">
        <tr><td class="label">Asset Code</td><td class="val">{{ $context['asset_code'] ?? '—' }}</td></tr>
        <tr><td class="label">Transferred From</td><td class="val">{{ $context['from_employee'] ?? '—' }}</td></tr>
        <tr><td class="label">Transferred To</td><td class="val">{{ $context['to_employee'] ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> Please ensure both employees acknowledge the handover and any accompanying accessories.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
