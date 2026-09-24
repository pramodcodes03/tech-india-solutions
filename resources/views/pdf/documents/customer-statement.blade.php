@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Statement of Account — '.($customer?->name ?? ''))
@section('doc-title', 'Statement of Account')
@section('doc-sub', $from->format('d M Y').' — '.$to->format('d M Y'))
@section('doc-meta')
    <div class="row"><span class="label">Customer</span> <span class="val">{{ $customer?->code ?? '—' }}</span></div>
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Account of</td><td class="v">{{ $customer?->name ?? '—' }}</td>
            <td class="k">GSTIN</td><td class="v">{{ $customer?->gst_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Company</td><td class="v">{{ $customer?->company ?: '—' }}</td>
            <td class="k">Contact</td><td class="v">{{ $customer?->phone ?: ($customer?->email ?: '—') }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr><th>Date</th><th>Reference</th><th>Particulars</th><th class="tr">Debit (₹)</th><th class="tr">Credit (₹)</th><th class="tr">Balance (₹)</th></tr>
        </thead>
        <tbody>
            @forelse($entries as $i => $entry)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="nowrap">{{ optional($entry['date'])->format('d-m-Y') }}</td>
                    <td>{{ $entry['ref'] }}</td>
                    <td>{{ $entry['type'] }}</td>
                    <td class="tr">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}</td>
                    <td class="tr">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}</td>
                    <td class="tr" style="font-weight:bold;">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3">Closing balance {{ $closing > 0 ? '(receivable)' : ($closing < 0 ? '(advance held)' : '') }}</td>
                    <td class="tr">{{ number_format((float) $entries->sum('debit'), 2) }}</td>
                    <td class="tr">{{ number_format((float) $entries->sum('credit'), 2) }}</td>
                    <td class="tr">{{ number_format($closing, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if($entries->isNotEmpty())
        <div class="words mt">
            <span class="lbl">Closing balance:</span> <strong>{{ AmountInWords::currency(abs($closing)) }}</strong>
            {{ $closing > 0 ? 'receivable' : ($closing < 0 ? 'held in advance' : '') }}
        </div>
    @endif

    <div class="muted mt" style="font-size:8.5px;">
        Please verify this statement and report any discrepancy within seven days. Payments made after
        {{ $to->format('d M Y') }} are not reflected above.
    </div>
@endsection
