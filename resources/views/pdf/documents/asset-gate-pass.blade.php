@extends('pdf.layout')
@section('title', 'Gate Pass — '.($asset?->asset_code ?? ''))
@section('doc-title', 'Asset Gate Pass')
@section('doc-sub', 'Authorisation to move an asset off the premises')
@section('doc-meta')
    <div class="row"><span class="label">Pass No.</span> <span class="val">GP/{{ $asset?->asset_code ?? '—' }}/{{ now()->format('ymd') }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ now()->format('d M Y') }}</span></div>
@endsection

@section('content')
    <table class="data" style="margin-bottom:12px;">
        <thead><tr><th>Asset Code</th><th>Description</th><th>Category</th><th>Serial No.</th><th>Current Location</th></tr></thead>
        <tbody>
            <tr>
                <td style="font-weight:bold;">{{ $asset?->asset_code ?? '—' }}</td>
                <td>{{ $asset?->name ?? '—' }}</td>
                <td>{{ $asset?->category?->name ?? '—' }}</td>
                <td>{{ $asset?->serial_number ?: '—' }}</td>
                <td>{{ $asset?->location?->name ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Current custodian</td><td class="v">{{ $asset?->custodian?->full_name ?? 'Unassigned' }}</td>
            <td class="k">Book value</td><td class="v">₹{{ number_format((float) ($asset?->current_book_value ?? $asset?->purchase_cost ?? 0), 2) }}</td>
        </tr>
    </table>

    {{-- Filled in by hand at the gate — a gate pass is only useful if the
         security desk can complete it without a screen. --}}
    <div class="panel-title" style="margin-bottom:5px;">Movement details</div>
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr><td style="width:34%;">Taken out by (name)</td><td style="height:24px;"></td></tr>
            <tr class="alt"><td>Purpose of movement</td><td style="height:24px;"></td></tr>
            <tr><td>Destination</td><td style="height:24px;"></td></tr>
            <tr class="alt"><td>Vehicle number</td><td style="height:24px;"></td></tr>
            <tr>
                <td>Returnable</td>
                <td style="height:24px;">
                    <span style="margin-right:26px;">☐ Yes — expected return date: ____________</span>
                    <span>☐ No (non-returnable)</span>
                </td>
            </tr>
            <tr class="alt"><td>Date &amp; time out</td><td style="height:24px;"></td></tr>
            <tr><td>Date &amp; time in (on return)</td><td style="height:24px;"></td></tr>
        </tbody>
    </table>

    <div class="panel">
        <div class="panel-title">Note</div>
        <div style="font-size:9px;">
            This pass authorises the movement of the asset described above out of the Company premises. The person
            named remains responsible for the asset until it is returned and this pass is closed at the gate.
            A returnable asset not brought back by the expected date must be reported to the asset custodian.
        </div>
    </div>

    <table class="sign-wrap">
        <tr>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Issued by</div><div class="sign-role">Asset custodian</div></div>
            </td>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Approved by</div><div class="sign-role">Authorised signatory</div></div>
            </td>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line"><div class="sign-name">Security</div><div class="sign-role">Gate — out / in</div></div>
            </td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
