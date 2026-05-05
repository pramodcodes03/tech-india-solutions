@extends('emails.layouts.business')

@section('body')
    <h1>Your leave has been approved</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Good news — your leave request has been approved. Enjoy your time off and please ensure handovers are in place before you leave.</p>

    <table class="meta-table">
        <tr><td class="label">Leave Type</td><td class="val">{{ $entity?->leave_type ?? '—' }}</td></tr>
        <tr><td class="label">From</td><td class="val">{{ \Carbon\Carbon::parse($entity?->from_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">To</td><td class="val">{{ \Carbon\Carbon::parse($entity?->to_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Days</td><td class="val">{{ $entity?->days ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Approved.</strong> Your leave balance has been updated accordingly.
    </div>

    <p>Have a great break.<br><strong>{{ $business->name }}</strong></p>
@endsection
