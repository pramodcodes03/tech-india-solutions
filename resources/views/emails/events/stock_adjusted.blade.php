@extends('emails.layouts.business')

@section('body')
    <h1>Stock adjustment recorded</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A stock adjustment has been logged in the system. Details are summarised below for your records.</p>

    <table class="meta-table">
        <tr><td class="label">Product</td><td class="val">{{ $context['product_name'] ?? '—' }}</td></tr>
        <tr><td class="label">Type</td><td class="val">{{ ucfirst(str_replace('_', ' ', $entity?->type ?? '—')) }}</td></tr>
        <tr><td class="label">Quantity</td><td class="val">{{ $entity?->quantity ?? 0 }}</td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> Inventory levels have been updated. Please verify the change against the physical count if applicable.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
