@extends('emails.layouts.business')

@section('body')
    <h1>Quotation rejected</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The customer has rejected the following quotation. Please review and follow up if appropriate — there may be an opportunity to revise terms or capture feedback for future deals.</p>

    <table class="meta-table">
        <tr><td class="label">Quotation No.</td><td class="val">{{ $entity?->quotation_number }}</td></tr>
        <tr><td class="label">Customer</td><td class="val">{{ $entity?->customer->name ?? '—' }}</td></tr>
        @if(!empty($context['reason']))
            <tr><td class="label">Reason</td><td class="val">{{ $context['reason'] }}</td></tr>
        @endif
    </table>

    <div class="alert alert-warning">
        <strong>Heads up:</strong> Consider reaching out to the customer to understand the decision and explore whether a revised offer could win the deal.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
