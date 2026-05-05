@extends('emails.layouts.business')

@section('body')
    <h1>Your shift has been updated</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Your work shift has been updated. The new schedule details are listed below — please plan your routine accordingly.</p>

    <table class="meta-table">
        <tr><td class="label">Shift</td><td class="val">{{ $context['shift_name'] ?? '—' }}</td></tr>
        <tr><td class="label">Start Time</td><td class="val">{{ $context['start_time'] ?? '—' }}</td></tr>
        <tr><td class="label">End Time</td><td class="val">{{ $context['end_time'] ?? '—' }}</td></tr>
        <tr><td class="label">Effective From</td><td class="val">{{ !empty($context['effective_from']) ? \Carbon\Carbon::parse($context['effective_from'])->format('d M Y') : '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Note:</strong> If you have any concerns about the new shift, please raise them with your reporting manager.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
