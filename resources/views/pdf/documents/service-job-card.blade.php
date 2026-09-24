@extends('pdf.layout')
@section('title', 'Job Card — '.($ticket?->ticket_number ?? ''))
@section('doc-title', 'Service Job Card')
@section('doc-meta')
    <div class="row"><span class="label">Ticket</span> <span class="val">{{ $ticket?->ticket_number ?? '—' }}</span></div>
    <div class="row"><span class="label">Priority</span> <span class="val">{{ ucfirst((string) ($ticket?->priority ?? '—')) }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Customer</td><td class="v">{{ $ticket?->customer?->name ?? '—' }}</td>
            <td class="k">Raised on</td><td class="v">{{ optional($ticket?->created_at)->format('d M Y, h:i A') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Contact</td><td class="v">{{ $ticket?->customer?->phone ?? '—' }}</td>
            <td class="k">Status</td><td class="v">{{ ucfirst(str_replace('_', ' ', (string) ($ticket?->status ?? '—'))) }}</td>
        </tr>
        <tr>
            <td class="k">Site address</td><td class="v" colspan="3">{{ $ticket?->customer?->billing_address ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Assigned engineer</td><td class="v">{{ $ticket?->assignedTo?->display_name ?? 'Unassigned' }}</td>
            <td class="k">Due</td><td class="v">{{ optional($ticket?->due_date)->format('d M Y') ?? '—' }}</td>
        </tr>
    </table>

    <div class="panel" style="margin-bottom:12px;">
        <div class="panel-title">Reported issue</div>
        <div style="font-weight:bold; font-size:10px;">{{ $ticket?->subject ?? '—' }}</div>
        @if($ticket?->issue_description)<div style="font-size:9.5px; margin-top:4px;">{{ $ticket->issue_description }}</div>@endif
    </div>

    @if(($ticket?->comments ?? collect())->isNotEmpty())
        <div class="panel-title" style="margin-bottom:5px;">Activity so far</div>
        <table class="data" style="margin-bottom:12px;">
            <thead><tr><th style="width:110px;">When</th><th>Note</th></tr></thead>
            <tbody>
                @foreach($ticket->comments as $i => $comment)
                    <tr @class(['alt' => $i % 2 === 1])>
                        <td class="nowrap">{{ optional($comment->created_at)->format('d M Y, h:i A') }}</td>
                        <td>{{ $comment->body ?? $comment->comment ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Deliberately blank: the engineer fills this in on site and it comes
         back as the record of what was actually done. --}}
    <div class="panel-title" style="margin-bottom:5px;">To be completed on site</div>
    <table class="data">
        <thead><tr><th style="width:34%;">Field</th><th>Details</th></tr></thead>
        <tbody>
            <tr><td>Work carried out</td><td style="height:46px;"></td></tr>
            <tr class="alt"><td>Parts / materials used</td><td style="height:34px;"></td></tr>
            <tr><td>Time in / time out</td><td style="height:22px;"></td></tr>
            <tr class="alt"><td>Follow-up required</td><td style="height:22px;"></td></tr>
        </tbody>
    </table>

    <table class="sign-wrap">
        <tr>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Engineer</div><div class="sign-role">Signature &amp; date</div></div>
            </td>
            <td></td>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Customer</div><div class="sign-role">Signature &amp; date</div></div>
            </td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
