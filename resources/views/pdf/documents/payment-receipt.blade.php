@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Payment Receipt')
@section('doc-title', 'Payment Receipt')
@section('doc-meta')
    <div class="row"><span class="label">Receipt No.</span> <span class="val">RCP/{{ $payment?->id ?? '—' }}/{{ optional($payment?->payment_date)->format('ymd') }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ optional($payment?->payment_date)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <p style="font-size:10.5px; margin-bottom:12px;">
        Received with thanks from <strong>{{ $payment?->invoice?->customer?->name ?? '—' }}</strong>
        @if($payment?->invoice?->customer?->company) ({{ $payment->invoice->customer->company }}) @endif
        the sum of <strong>₹{{ number_format((float) ($payment?->amount ?? 0), 2) }}</strong>
        towards invoice <strong>{{ $payment?->invoice?->invoice_number ?? '—' }}</strong>.
    </p>

    <div class="words" style="margin-bottom:12px;">
        <span class="lbl">Rupees:</span> <strong>{{ AmountInWords::currency($payment?->amount ?? 0) }}</strong>
    </div>

    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Payment mode</td><td class="v">{{ ucfirst(str_replace('_', ' ', (string) ($payment?->mode ?? '—'))) }}</td>
            <td class="k">Reference / UTR</td><td class="v">{{ $payment?->reference_no ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Against invoice</td><td class="v">{{ $payment?->invoice?->invoice_number ?? '—' }}</td>
            <td class="k">Invoice date</td><td class="v">{{ optional($payment?->invoice?->invoice_date)->format('d M Y') ?? '—' }}</td>
        </tr>
    </table>

    @php
        $invoiceTotal = (float) ($payment?->invoice?->grand_total ?? 0);
        $paidToDate = (float) ($payment?->invoice?->payments?->sum('amount') ?? 0);
        $balance = $invoiceTotal - $paidToDate;
    @endphp
    <table class="data">
        <thead><tr><th>Particulars</th><th class="tr">Amount (₹)</th></tr></thead>
        <tbody>
            <tr><td>Invoice total</td><td class="tr">{{ number_format($invoiceTotal, 2) }}</td></tr>
            <tr class="alt"><td>Total received to date (including this receipt)</td><td class="tr">{{ number_format($paidToDate, 2) }}</td></tr>
        </tbody>
        <tfoot>
            <tr>
                <td>Balance outstanding</td>
                <td class="tr">{{ number_format(max(0, $balance), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($payment?->notes)
        <div class="panel mt"><div class="panel-title">Note</div><div style="font-size:9.5px;">{{ $payment->notes }}</div></div>
    @endif

    <div class="muted mt" style="font-size:8.5px;">
        Subject to realisation of the instrument, where applicable.
    </div>
@endsection
