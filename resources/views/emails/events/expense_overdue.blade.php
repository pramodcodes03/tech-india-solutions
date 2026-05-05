@extends('emails.layouts.business')

@section('body')
    @php
        $daysOverdue = $context['days_overdue'] ?? (
            !empty($entity?->due_date) ? max(0, \Carbon\Carbon::parse($entity?->due_date)->diffInDays(now(), false)) : 0
        );
    @endphp

    <h1>Payment overdue — action required</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The following payment has crossed its due date and is now overdue. Please review and clear it immediately to avoid penalties or service disruption.</p>

    <table class="meta-table">
        <tr><td class="label">Title</td><td class="val">{{ $entity?->title ?? '—' }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ $entity?->due_date ? \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Days Overdue</td><td class="val">{{ (int) $daysOverdue }}</td></tr>
    </table>

    <div class="alert alert-danger">
        <strong>Action required:</strong> Please process this payment without further delay and update the system once settled.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
