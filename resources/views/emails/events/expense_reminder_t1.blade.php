@extends('emails.layouts.business')

@section('body')
    <h1>Payment due tomorrow</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is a reminder that the following payment is due tomorrow. Please ensure it's reviewed and paid to avoid any disruption.</p>

    <table class="meta-table">
        <tr><td class="label">Title</td><td class="val">{{ $entity?->title ?? '—' }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ $entity?->due_date ? \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Reminder:</strong> Only one day left before the due date. Please prioritise this payment.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
