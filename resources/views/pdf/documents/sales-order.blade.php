@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Sales Order — '.($order?->order_number ?? ''))
@section('doc-title', 'Sales Order')
@section('doc-meta')
    <div class="row"><span class="label">Order No.</span> <span class="val">{{ $order?->order_number ?? '—' }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ optional($order?->order_date)->format('d M Y') ?? '—' }}</span></div>
    <div class="row"><span class="label">Status</span> <span class="val">{{ ucfirst((string) ($order?->status ?? '—')) }}</span></div>
@endsection

@section('content')
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Sold to</div>
                    <div style="font-weight:bold; margin-top:2px;">{{ $order?->customer?->name ?? '—' }}</div>
                    @if($order?->customer?->company)<div>{{ $order->customer->company }}</div>@endif
                    <div class="muted">{{ $order?->customer?->billing_address ?? '' }}</div>
                    @if($order?->customer?->gst_number)<div style="margin-top:2px;">GSTIN: <strong>{{ $order->customer->gst_number }}</strong></div>@endif
                </td>
                <td>
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Ship to</div>
                    <div class="muted" style="margin-top:2px;">{{ $order?->customer?->shipping_address ?: ($order?->customer?->billing_address ?? '—') }}</div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="data">
        <thead>
            <tr><th class="tc">#</th><th>Item</th><th class="tr">Qty</th><th class="tr">Rate (₹)</th><th class="tr">Tax %</th><th class="tr">Amount (₹)</th></tr>
        </thead>
        <tbody>
            @forelse($order?->items ?? [] as $i => $item)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="tc">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:bold;">{{ $item->product?->name ?? $item->description }}</div>
                        @if($item->product?->sku)<div class="muted" style="font-size:8px;">SKU: {{ $item->product->sku }}</div>@endif
                    </td>
                    <td class="tr">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="tr">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="tr">{{ number_format((float) ($item->tax_percent ?? 0), 2) }}</td>
                    <td class="tr">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No line items on this order.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="tr">Subtotal</td><td class="tr">{{ number_format((float) ($order?->subtotal ?? 0), 2) }}</td></tr>
            @if((float) ($order?->discount_amount ?? 0) > 0)
                <tr><td colspan="5" class="tr">Discount</td><td class="tr">− {{ number_format((float) $order->discount_amount, 2) }}</td></tr>
            @endif
            @if((float) ($order?->tax_amount ?? 0) > 0)
                <tr><td colspan="5" class="tr">Tax</td><td class="tr">{{ number_format((float) $order->tax_amount, 2) }}</td></tr>
            @endif
            <tr><td colspan="5" class="tr">Grand Total</td><td class="tr">{{ number_format((float) ($order?->grand_total ?? 0), 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="words mt">
        <span class="lbl">Total in words:</span> <strong>{{ AmountInWords::currency($order?->grand_total ?? 0) }}</strong>
    </div>

    @if($business?->terms_and_conditions)
        <div class="panel mt-lg">
            <div class="panel-title">Terms &amp; conditions</div>
            <div style="font-size:8.5px; white-space:pre-line;">{{ $business->terms_and_conditions }}</div>
        </div>
    @endif
@endsection
