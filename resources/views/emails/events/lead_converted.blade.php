@extends('emails.layouts.business')

@section('body')
    <h1>Lead converted — congratulations!</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Great work — a lead has just been converted into a customer. Here's a quick summary of the win.</p>

    <table class="meta-table">
        <tr><td class="label">Lead Name</td><td class="val">{{ $entity?->name ?? '—' }}</td></tr>
        <tr><td class="label">Customer Code</td><td class="val">{{ $entity?->customer->code ?? ($context['customer_code'] ?? '—') }}</td></tr>
        @if(!empty($context['deal_value']))
            <tr><td class="label">Deal Value</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($context['deal_value'], 2) }}</span></td></tr>
        @endif
    </table>

    <div class="alert alert-success">
        <strong>Well done!</strong> Take a moment to celebrate, then make sure the handover to delivery / accounts is smooth.
    </div>

    <p>Cheers,<br><strong>{{ $business->name }}</strong></p>
@endsection
