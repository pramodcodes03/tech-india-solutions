@extends('pdf.layout')
@section('title', 'Leave Card — '.($employee?->employee_code ?? ''))
@section('doc-title', 'Leave Card')
@section('doc-sub', $employee?->full_name.' · Calendar year '.$year)
@section('doc-meta')
    <div class="row"><span class="label">Employee ID</span> <span class="val">{{ $employee?->employee_code ?? '—' }}</span></div>
    <div class="row"><span class="label">Department</span> <span class="val">{{ $employee?->department?->name ?? '—' }}</span></div>
@endsection

@section('content')
    <div class="panel-title">Balance statement</div>
    <table class="data" style="margin-bottom:14px;">
        <thead>
            <tr>
                <th>Leave Type</th><th class="tr">Opening / Carried</th><th class="tr">Allocated</th>
                <th class="tr">Used</th><th class="tr">Pending</th><th class="tr">Available</th>
            </tr>
        </thead>
        <tbody>
            @forelse($balances as $i => $b)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $b->leaveType?->name ?? '—' }} <span class="muted">({{ $b->leaveType?->code }})</span></td>
                    <td class="tr">{{ number_format((float) $b->carried_forward, 1) }}</td>
                    <td class="tr">{{ number_format((float) $b->allocated, 1) }}</td>
                    <td class="tr">{{ number_format((float) $b->used, 1) }}</td>
                    <td class="tr">{{ number_format((float) $b->pending, 1) }}</td>
                    <td class="tr" style="font-weight:bold;">{{ number_format($b->available, 1) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No leave balances allocated for {{ $year }}.</td></tr>
            @endforelse
        </tbody>
        @if($balances->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="tr">{{ number_format((float) $balances->sum('carried_forward'), 1) }}</td>
                    <td class="tr">{{ number_format((float) $balances->sum('allocated'), 1) }}</td>
                    <td class="tr">{{ number_format((float) $balances->sum('used'), 1) }}</td>
                    <td class="tr">{{ number_format((float) $balances->sum('pending'), 1) }}</td>
                    <td class="tr">{{ number_format((float) $balances->sum(fn ($b) => $b->available), 1) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="panel-title">Leave taken in {{ $year }}</div>
    <table class="data">
        <thead>
            <tr>
                <th>Request</th><th>Leave Type</th><th>From</th><th>To</th>
                <th class="tr">Days</th><th class="tr">Paid</th><th class="tr">Unpaid</th><th>Status</th><th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $i => $r)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $r->request_code }}</td>
                    <td>
                        {{ $r->leaveType?->name ?? '—' }}
                        @if($r->is_combined)<div class="muted" style="font-size:7.5px;">Combined</div>@endif
                    </td>
                    <td>{{ optional($r->from_date)->format('d-m-Y') }}</td>
                    <td>{{ optional($r->to_date)->format('d-m-Y') }}</td>
                    <td class="tr">{{ number_format((float) $r->days, 1) }}</td>
                    <td class="tr">{{ number_format((float) $r->paid_days, 1) }}</td>
                    <td class="tr">{{ number_format((float) $r->unpaid_days, 1) }}</td>
                    <td>
                        @php
                            $badge = match ($r->status) {
                                'approved' => 'badge-green', 'rejected' => 'badge-red',
                                'pending' => 'badge-amber', default => 'badge-grey',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst((string) $r->status) }}</span>
                    </td>
                    <td class="muted">{{ \Illuminate\Support\Str::limit($r->reason, 40) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No leave requests in {{ $year }}.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('signature')@endsection
