@php
    use App\Support\AmountInWords;
    $invoiceTotal = (float) ($invoice?->grand_total ?? 0);
    $paid = (float) ($invoice?->payments?->sum('amount') ?? 0);
    $outstanding = $invoiceTotal - $paid;
@endphp
@extends('pdf.layout')
@section('title', 'Credit / Debit Note — '.($invoice?->invoice_number ?? ''))
@section('doc-title', 'Credit / Debit Note')
@section('doc-sub', 'Adjustment against invoice '.($invoice?->invoice_number ?? '—'))
@section('doc-meta')
    <div class="row"><span class="label">Note No.</span> <span class="val">CDN/{{ $invoice?->invoice_number ?? '—' }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ now()->format('d M Y') }}</span></div>
@endsection

@section('content')
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Issued to</div>
                    <div style="font-weight:bold; margin-top:2px;">{{ $invoice?->customer?->name ?? '—' }}</div>
                    @if($invoice?->customer?->company)<div>{{ $invoice->customer->company }}</div>@endif
                    <div class="muted">{{ $invoice?->customer?->billing_address ?? '' }}</div>
                    @if($invoice?->customer?->gst_number)<div style="margin-top:2px;">GSTIN: <strong>{{ $invoice->customer->gst_number }}</strong></div>@endif
                </td>
                <td>
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Original invoice</div>
                    <table class="kv" style="margin-top:2px;">
                        <tr><td class="k" style="width:88px;">Invoice no.</td><td class="v">{{ $invoice?->invoice_number ?? '—' }}</td></tr>
                        <tr><td class="k">Invoice date</td><td class="v">{{ optional($invoice?->invoice_date)->format('d M Y') ?? '—' }}</td></tr>
                        <tr><td class="k">Invoice value</td><td class="v">₹{{ number_format($invoiceTotal, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Type and amount are filled in when the note is raised — the system
         records the adjustment, the note states it formally. --}}
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:34%;">Nature of note</td>
                <td>
                    <span style="margin-right:30px;">☐ Credit Note (reduces the amount receivable)</span>
                    <span>☐ Debit Note (increases the amount receivable)</span>
                </td>
            </tr>
            <tr class="alt"><td>Reason for adjustment</td><td style="height:30px;"></td></tr>
            <tr><td>Amount of adjustment (₹)</td><td style="height:24px;"></td></tr>
        </tbody>
    </table>

    <div class="panel-title" style="margin-bottom:5px;">Invoice position before adjustment</div>
    <table class="data">
        <thead><tr><th>Particulars</th><th class="tr">Amount (₹)</th></tr></thead>
        <tbody>
            <tr><td>Invoice value</td><td class="tr">{{ number_format($invoiceTotal, 2) }}</td></tr>
            <tr class="alt"><td>Less: payments received</td><td class="tr">{{ number_format($paid, 2) }}</td></tr>
        </tbody>
        <tfoot>
            <tr><td>Outstanding before adjustment</td><td class="tr">{{ number_format($outstanding, 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="words mt">
        <span class="lbl">Outstanding in words:</span> <strong>{{ AmountInWords::currency($outstanding) }}</strong>
    </div>

    <div class="muted mt" style="font-size:8.5px;">
        This note is issued in accordance with the applicable GST provisions and adjusts the invoice referenced above.
        The revised balance takes effect from the date of this note.
    </div>
@endsection
