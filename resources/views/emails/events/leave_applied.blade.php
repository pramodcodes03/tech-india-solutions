@extends('emails.layouts.business')

@section('body')
    <h1>New leave request awaiting approval</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A team member has submitted a leave request and is awaiting your approval. Please review and respond at your earliest convenience.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $entity?->employee->first_name ?? '—' }} {{ $entity?->employee->last_name ?? '' }}</td></tr>
        <tr><td class="label">Leave Type</td><td class="val">{{ $entity?->leave_type ?? '—' }}</td></tr>
        <tr><td class="label">From</td><td class="val">{{ \Carbon\Carbon::parse($entity?->from_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">To</td><td class="val">{{ \Carbon\Carbon::parse($entity?->to_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Days</td><td class="val">{{ $entity?->days ?? '—' }}</td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Action:</strong> Log in to the HR module to approve or reject this request.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
