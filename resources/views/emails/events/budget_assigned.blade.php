@extends('emails.layouts.business')

@section('body')
    <h1>A budget has been assigned to you</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A budget has been sanctioned to you. You can record spends against it from your portal under <strong>My Budget → Utilize Budget</strong>.</p>

    <table class="meta-table">
        <tr><td class="label">Category</td><td class="val">{{ $entity?->category?->name ?? '—' }}</td></tr>
        <tr><td class="label">Business</td><td class="val">{{ $entity?->business?->name ?? '—' }}</td></tr>
        <tr><td class="label">Amount</td><td class="val">₹{{ number_format((float) ($entity?->amount ?? 0), 2) }}</td></tr>
        <tr><td class="label">Period</td><td class="val">{{ ucfirst($entity?->period_type ?? '') }} · {{ \Carbon\Carbon::parse($entity?->period_start)->format('d M Y') }} – {{ \Carbon\Carbon::parse($entity?->period_end)->format('d M Y') }}</td></tr>
        @if($entity?->notes)
            <tr><td class="label">Notes</td><td class="val">{{ $entity->notes }}</td></tr>
        @endif
    </table>

    <div class="alert alert-info">
        <strong>Action:</strong> Log in to the Employee portal and open <strong>My Budget</strong> to view and utilise it.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
