@extends('emails.layouts.business')

@section('body')
    <h1>Your appraisal has been recorded</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Your performance appraisal for the current cycle has been recorded. A summary is provided below and the full report is attached as a PDF.</p>

    <table class="meta-table">
        <tr><td class="label">Cycle</td><td class="val">{{ $entity?->cycle ?? '—' }}</td></tr>
        <tr><td class="label">Performance Score</td><td class="val">{{ $entity?->performance_score ?? '—' }}</td></tr>
        <tr><td class="label">Overall Rating</td><td class="val">{{ $entity?->overall_rating ?? '—' }}</td></tr>
        <tr><td class="label">Recommended Hike</td><td class="val">{{ isset($entity?->recommended_hike_percent) ? $entity?->recommended_hike_percent.'%' : '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Next steps:</strong> Please review the attached report. Your manager / HR will follow up to discuss outcomes and growth areas.
    </div>

    <p>Thank you for your contribution.<br><strong>{{ $business->name }}</strong></p>
@endsection
