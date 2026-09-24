@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Vendor Statement — '.($vendor?->name ?? ''))
@section('doc-title', 'Vendor Statement')
@section('doc-sub', $from->format('d M Y').' — '.$to->format('d M Y'))
@section('doc-meta')
    <div class="row"><span class="label">Vendor</span> <span class="val">{{ $vendor?->code ?? $vendor?->id ?? '—' }}</span></div>
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Vendor</td><td class="v">{{ $vendor?->name ?? '—' }}</td>
            <td class="k">GSTIN</td><td class="v">{{ $vendor?->gst_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Contact</td><td class="v">{{ $vendor?->phone ?: ($vendor?->email ?: '—') }}</td>
            <td class="k">Address</td><td class="v">{{ $vendor?->address ?: '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr><th>PO Date</th><th>PO Number</th><th>Status</th><th class="tr">Order Value (₹)</th></tr>
        </thead>
        <tbody>
            @forelse($orders as $i => $order)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="nowrap">{{ optional($order->po_date)->format('d-m-Y') }}</td>
                    <td>{{ $order->po_number }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $order->status)) }}</td>
                    <td class="tr">{{ number_format((float) $order->grand_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No purchase orders in this period.</td></tr>
            @endforelse
        </tbody>
        @if($orders->isNotEmpty())
            <tfoot>
                <tr><td colspan="3">Total ordered in period</td><td class="tr">{{ number_format($total, 2) }}</td></tr>
            </tfoot>
        @endif
    </table>

    @if($orders->isNotEmpty())
        <div class="words mt">
            <span class="lbl">Total in words:</span> <strong>{{ AmountInWords::currency($total) }}</strong>
        </div>
    @endif

    <div class="muted mt" style="font-size:8.5px;">
        Please confirm the balance as per your books. Any difference should be reported within seven days.
    </div>
@endsection
