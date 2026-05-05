@extends('emails.layouts.business')

@section('body')
    <h1>Goods receipt logged</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A goods receipt note (GRN) has just been recorded in the system. Stock levels have been updated accordingly.</p>

    <table class="meta-table">
        <tr><td class="label">GRN No.</td><td class="val">{{ $entity?->grn_number }}</td></tr>
        <tr><td class="label">Against PO</td><td class="val">{{ $entity?->purchaseOrder->po_number ?? ($context['po_number'] ?? '—') }}</td></tr>
        <tr><td class="label">Received Date</td><td class="val">{{ \Carbon\Carbon::parse($entity?->received_date)->format('d M Y') }}</td></tr>
        <tr><td class="label">Total Items</td><td class="val">{{ $entity?->items_count ?? ($entity?->items()->count() ?? 0) }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Inventory updated.</strong> Please verify the items against the PO and report any discrepancies promptly.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
