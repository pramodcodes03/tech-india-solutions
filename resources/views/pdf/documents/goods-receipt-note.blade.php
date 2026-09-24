@extends('pdf.layout')
@section('title', 'GRN — '.($grn?->grn_number ?? ''))
@section('doc-title', 'Goods Receipt Note')
@section('doc-meta')
    <div class="row"><span class="label">GRN No.</span> <span class="val">{{ $grn?->grn_number ?? '—' }}</span></div>
    <div class="row"><span class="label">Received on</span> <span class="val">{{ optional($grn?->received_date)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Vendor</td><td class="v">{{ $grn?->purchaseOrder?->vendor?->name ?? '—' }}</td>
            <td class="k">Purchase order</td><td class="v">{{ $grn?->purchaseOrder?->po_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">PO date</td><td class="v">{{ optional($grn?->purchaseOrder?->po_date)->format('d M Y') ?? '—' }}</td>
            <td class="k">Received by</td><td class="v">{{ $grn?->creator?->display_name ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr><th class="tc">#</th><th>Item</th><th class="tr">Ordered</th><th class="tr">Received</th><th class="tr">Rate (₹)</th><th class="tr">Value (₹)</th></tr>
        </thead>
        <tbody>
            @forelse($grn?->items ?? [] as $i => $item)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="tc">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:bold;">{{ $item->product?->name ?? '—' }}</div>
                        @if($item->product?->sku)<div class="muted" style="font-size:8px;">SKU: {{ $item->product->sku }}</div>@endif
                    </td>
                    <td class="tr">{{ $item->ordered_quantity !== null ? number_format((float) $item->ordered_quantity, 2) : '—' }}</td>
                    <td class="tr" style="font-weight:bold;">{{ number_format((float) $item->received_quantity, 2) }}</td>
                    <td class="tr">{{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2) : '—' }}</td>
                    <td class="tr">{{ number_format((float) $item->received_quantity * (float) ($item->unit_price ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No items recorded on this GRN.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($grn?->notes)
        <div class="panel mt"><div class="panel-title">Notes</div><div style="font-size:9.5px;">{{ $grn->notes }}</div></div>
    @endif

    <div class="panel mt">
        <div class="panel-title">Quality check</div>
        <div style="font-size:9px;">
            ☐ Received in good condition &nbsp;&nbsp; ☐ Short supply &nbsp;&nbsp; ☐ Damaged &nbsp;&nbsp; ☐ Rejected
            <div style="margin-top:8px;">Remarks: ______________________________________________________________</div>
        </div>
    </div>

    <table class="sign-wrap">
        <tr>
            <td class="sign-box"><div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Received &amp; checked by</div><div class="sign-role">Stores</div></div></td>
            <td class="sign-box"><div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Approved by</div><div class="sign-role">Authorised signatory</div></div></td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
