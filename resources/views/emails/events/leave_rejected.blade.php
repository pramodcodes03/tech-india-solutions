@extends('emails.layouts.business')

@section('body')
    <h1>Your leave request was not approved</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>We're sorry to inform you that your leave request could not be approved at this time. Please find the details below and feel free to reach out to your manager for clarifications.</p>

    <table class="meta-table">
        <tr><td class="label">Leave Type</td><td class="val">{{ $entity?->leave_type ?? '—' }}</td></tr>
        <tr><td class="label">From</td><td class="val">{{ \Carbon\Carbon::parse($entity?->from_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">To</td><td class="val">{{ \Carbon\Carbon::parse($entity?->to_date)->format('d M Y') }}</td></tr>
        @if(!empty($context['reason']))
            <tr><td class="label">Reason</td><td class="val">{{ $context['reason'] }}</td></tr>
        @endif
    </table>

    <div class="alert alert-danger">
        <strong>Rejected.</strong> If you'd like to discuss alternatives or resubmit with revised dates, please contact your reporting manager.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
