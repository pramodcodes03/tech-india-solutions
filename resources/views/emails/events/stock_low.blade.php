@extends('emails.layouts.business')

@section('body')
    <h1>Low stock alert</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Stock for the following product has fallen at or below its reorder level. Please review and raise a purchase order if required to avoid stock-outs.</p>

    <table class="meta-table">
        <tr><td class="label">Product Code</td><td class="val">{{ $entity?->code ?? '—' }}</td></tr>
        <tr><td class="label">Product Name</td><td class="val">{{ $entity?->name ?? '—' }}</td></tr>
        <tr><td class="label">Current Stock</td><td class="val">{{ $entity?->current_stock ?? 0 }}</td></tr>
        <tr><td class="label">Reorder Level</td><td class="val">{{ $entity?->reorder_level ?? 0 }}</td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Action recommended:</strong> Initiate a purchase order or reach out to the relevant vendor to replenish stock at the earliest.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
