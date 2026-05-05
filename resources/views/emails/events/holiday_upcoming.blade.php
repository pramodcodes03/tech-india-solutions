@extends('emails.layouts.business')

@section('body')
    <h1>Upcoming holiday reminder</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is a quick heads-up about an upcoming holiday in the company calendar. Please plan your work and handovers accordingly.</p>

    <table class="meta-table">
        <tr><td class="label">Holiday</td><td class="val">{{ $entity?->name ?? '—' }}</td></tr>
        <tr><td class="label">Date</td><td class="val">{{ $entity?->date ? \Carbon\Carbon::parse($entity?->date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Day</td><td class="val">{{ $entity?->day_name ?? ($entity?->date ? \Carbon\Carbon::parse($entity?->date)->format('l') : '—') }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Reminder:</strong> The office will remain closed on this day. For any urgent issues, follow the on-call escalation matrix.
    </div>

    <p>Regards,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
