@extends('pdf.layout')
@section('title', 'Delivery Challan — '.($order?->order_number ?? ''))
@section('doc-title', 'Delivery Challan')
@section('doc-sub', 'Not a tax invoice — for movement of goods')
@section('doc-meta')
    <div class="row"><span class="label">Challan No.</span> <span class="val">DC/{{ $order?->order_number ?? '—' }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ now()->format('d M Y') }}</span></div>
    <div class="row"><span class="label">Against order</span> <span class="val">{{ $order?->order_number ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Consignee</div>
                    <div style="font-weight:bold; margin-top:2px;">{{ $order?->customer?->name ?? '—' }}</div>
                    @if($order?->customer?->company)<div>{{ $order->customer->company }}</div>@endif
                    <div class="muted">{{ $order?->customer?->shipping_address ?: ($order?->customer?->billing_address ?? '') }}</div>
                    @if($order?->customer?->gst_number)<div style="margin-top:2px;">GSTIN: <strong>{{ $order->customer->gst_number }}</strong></div>@endif
                </td>
                <td>
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Dispatch details</div>
                    <table class="kv" style="margin-top:2px;">
                        <tr><td class="k" style="width:96px;">Vehicle no.</td><td class="v">____________</td></tr>
                        <tr><td class="k">Driver</td><td class="v">____________</td></tr>
                        <tr><td class="k">Dispatched on</td><td class="v">____________</td></tr>
                        <tr><td class="k">E-way bill</td><td class="v">____________</td></tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="data">
        <thead>
            <tr><th class="tc">#</th><th>Description of goods</th><th>SKU / HSN</th><th class="tr">Quantity</th><th class="tr">Value (₹)</th></tr>
        </thead>
        <tbody>
            @forelse($order?->items ?? [] as $i => $item)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="tc">{{ $i + 1 }}</td>
                    <td>{{ $item->product?->name ?? $item->description }}</td>
                    <td>{{ $item->product?->sku ?? '—' }}{{ $item->product?->hsn_code ? ' / '.$item->product->hsn_code : '' }}</td>
                    <td class="tr">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="tr">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No items on this order.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="tr">Total value of goods</td><td class="tr">{{ number_format((float) ($order?->grand_total ?? 0), 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="panel mt">
        <div class="panel-title">Declaration</div>
        <div style="font-size:8.5px;">
            The goods described above are being sent for delivery against the order referenced. This challan is issued
            for the movement of goods and is not a tax invoice. Goods once delivered are subject to the terms agreed
            on the order.
        </div>
    </div>

    <table class="sign-wrap">
        <tr>
            <td class="sign-box"><div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Dispatched by</div><div class="sign-role">For {{ $business?->name ?? 'the Company' }}</div></div></td>
            <td class="sign-box"><div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Received by</div><div class="sign-role">Consignee — signature, name &amp; date</div></div></td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
