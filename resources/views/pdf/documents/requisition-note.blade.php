@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Requisition Note — '.($requisition?->requisition_code ?? ''))
@section('doc-title', 'Requisition Approval Note')
@section('doc-meta')
    <div class="row"><span class="label">Requisition</span> <span class="val">{{ $requisition?->requisition_code ?? '—' }}</span></div>
    <div class="row"><span class="label">Status</span> <span class="val">{{ ucfirst((string) ($requisition?->status ?? '—')) }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Requested by</td><td class="v">{{ $requisition?->requester?->display_name ?? '—' }}</td>
            <td class="k">Category</td><td class="v">{{ $requisition?->category_label ?? $requisition?->category ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Raised on</td><td class="v">{{ optional($requisition?->created_at)->format('d M Y') ?? '—' }}</td>
            <td class="k">Current level</td><td class="v">{{ $requisition?->current_level ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Particulars</th><th class="tr">Requested (₹)</th><th class="tr">Estimated (₹)</th></tr></thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-weight:bold;">{{ $requisition?->title ?? '—' }}</div>
                    @if($requisition?->purpose)<div class="muted" style="margin-top:2px;">{{ $requisition->purpose }}</div>@endif
                </td>
                <td class="tr">{{ number_format((float) ($requisition?->requested_amount ?? 0), 2) }}</td>
                <td class="tr">{{ number_format((float) ($requisition?->estimated_amount ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="words mt">
        <span class="lbl">Requested amount in words:</span>
        <strong>{{ AmountInWords::currency($requisition?->requested_amount ?? 0) }}</strong>
    </div>

    <div class="mt-lg">
        <div class="panel-title" style="margin-bottom:5px;">Approval chain</div>
        <table class="data">
            <thead><tr><th class="tc">Level</th><th>Approver</th><th>Decision</th><th>Date</th><th>Remarks</th></tr></thead>
            <tbody>
                @forelse($requisition?->approvals ?? [] as $i => $approval)
                    <tr @class(['alt' => $i % 2 === 1])>
                        <td class="tc">{{ $approval->level ?? $i + 1 }}</td>
                        <td>{{ $approval->approver?->display_name ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match ($approval->status) {
                                    'approved' => 'badge-green', 'rejected' => 'badge-red', default => 'badge-amber',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst((string) $approval->status) }}</span>
                        </td>
                        <td>{{ optional($approval->actioned_at)->format('d M Y') ?? '—' }}</td>
                        <td class="muted">{{ $approval->remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No approvals recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requisition?->disbursed_at)
        <div class="panel mt">
            <div class="panel-title">Disbursement</div>
            <div style="font-size:9.5px;">
                Disbursed on {{ $requisition->disbursed_at->format('d F Y') }}
                @if($requisition->payment_reference) · Reference {{ $requisition->payment_reference }} @endif
            </div>
        </div>
    @endif
@endsection
