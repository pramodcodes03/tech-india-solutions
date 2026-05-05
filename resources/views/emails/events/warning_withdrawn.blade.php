@extends('emails.layouts.business')

@section('body')
    <h1>Warning withdrawn</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>We're writing to confirm that the previously issued warning has been withdrawn. Your record has been updated accordingly.</p>

    <table class="meta-table">
        <tr><td class="label">Warning Code</td><td class="val">{{ $entity?->warning_code ?? '—' }}</td></tr>
        <tr><td class="label">Original Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Withdrawn.</strong> No further action is required from your side. Thank you for your cooperation.
    </div>

    <p>Regards,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
