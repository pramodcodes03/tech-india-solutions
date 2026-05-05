@extends('emails.layouts.business')

@section('body')
    <h1>Your leave balance has been updated</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Your latest leave balance is summarised below. Please log in to the HR portal for the full breakdown.</p>

    <table class="meta-table">
        <tr><td class="label">Leave Type</td><td class="val">{{ $entity?->leaveType->name ?? ($entity?->leave_type ?? '—') }}</td></tr>
        <tr><td class="label">Allocated</td><td class="val">{{ $entity?->allocated ?? 0 }}</td></tr>
        <tr><td class="label">Used</td><td class="val">{{ $entity?->used ?? 0 }}</td></tr>
        <tr><td class="label">Available</td><td class="val">{{ $entity?->available ?? 0 }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Tip:</strong> Plan your leaves in advance to ensure smooth approvals and team coverage.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
