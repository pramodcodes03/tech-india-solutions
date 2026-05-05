@extends('emails.layouts.business')

@section('body')
    <h1>New department feedback submitted</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A new feedback entry has been submitted for one of the departments. Please log in to the HR portal for full details and any required action.</p>

    <table class="meta-table">
        <tr><td class="label">Department</td><td class="val">{{ $entity?->department->name ?? '—' }}</td></tr>
        <tr><td class="label">Rating</td><td class="val">{{ $entity?->rating ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> Aggregated feedback is reviewed periodically as part of organisational health metrics.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
