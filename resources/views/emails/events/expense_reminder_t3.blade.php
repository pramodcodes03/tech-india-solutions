@extends('emails.layouts.business')

@section('body')
    <h1>Payment due in 3 days</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is a friendly reminder that the following payment is due in 3 days. Please review and arrange payment in time.</p>

    <table class="meta-table">
        <tr><td class="label">Title</td><td class="val">{{ $entity?->title ?? '—' }}</td></tr>
        <tr><td class="label">Due Date</td><td class="val">{{ $entity?->due_date ? \Carbon\Carbon::parse($entity?->due_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Category</td><td class="val">{{ $entity?->category->name ?? ($entity?->category ?? '—') }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Heads up:</strong> Settling on time helps avoid late fees and keeps cash flow planning on track.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
