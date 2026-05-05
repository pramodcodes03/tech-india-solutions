@extends('emails.layouts.business')

@section('body')
    <h1>Leave request cancelled</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The following leave request has been cancelled. Please update planning and roster information accordingly.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $entity?->employee->first_name ?? '—' }} {{ $entity?->employee->last_name ?? '' }}</td></tr>
        <tr><td class="label">Leave Type</td><td class="val">{{ $entity?->leave_type ?? '—' }}</td></tr>
        <tr><td class="label">From</td><td class="val">{{ \Carbon\Carbon::parse($entity?->from_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">To</td><td class="val">{{ \Carbon\Carbon::parse($entity?->to_date)->format('d M Y') }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> Leave balance, if previously deducted, has been reverted.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
