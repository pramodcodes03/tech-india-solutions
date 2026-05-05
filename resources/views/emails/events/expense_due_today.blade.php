@extends('emails.layouts.business')

@section('body')
    <h1>Payment due today</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The following payment is due today. Please process the payment at the earliest to keep records clean.</p>

    <table class="meta-table">
        <tr><td class="label">Title</td><td class="val">{{ $entity?->title ?? '—' }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ $entity?->due_date ? \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Today is the day:</strong> Please complete the payment and mark the payment as settled in the system.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
