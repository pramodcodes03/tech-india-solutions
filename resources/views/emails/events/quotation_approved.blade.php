@extends('emails.layouts.business')

@section('body')
    <h1>Quotation approved — green light to proceed</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Good news — the customer has approved a quotation. You can now move ahead with order conversion and fulfilment planning.</p>

    <table class="meta-table">
        <tr><td class="label">Quotation No.</td><td class="val">{{ $entity?->quotation_number }}</td></tr>
        <tr><td class="label">Customer</td><td class="val">{{ $entity?->customer->name ?? '—' }}</td></tr>
        <tr><td class="label">Total Value</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->grand_total ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Approved.</strong> Convert this quotation into a sales order and notify operations to begin processing.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
