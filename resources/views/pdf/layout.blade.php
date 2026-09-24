{{--
    The Letterhead Foundation.

    Every one of the 42 documents in Module D extends this. It provides:
      · the shared letterhead header (logo, legal name, address, GST/PAN/CIN)
      · the shared footer with page numbering
      · the DRAFT / PAID / CANCELLED watermark
      · the authorised-signature and company-seal block

    Sections a document can fill:
      @section('title')     browser/PDF title
      @section('doc-title') the heading printed under the letterhead
      @section('doc-meta')  reference numbers and dates, right of the heading
      @section('content')   the body
      @section('signature') override the default signatory block

    Variables the renderer always passes: $business, $watermark, $generatedAt.
--}}
@php
    // The catalogue's landscape flag, so the @page rule agrees with the
    // setPaper() call the controller made. A template may still override
    // it with @section('page-orientation').
    $orientation = ($landscape ?? false) ? 'landscape' : 'portrait';
    $lh = ($business?->letterhead_enabled ?? true);
    $logoPath = $business?->logo ? public_path(ltrim($business->logo, '/')) : public_path('assets/images/logo.png');
    $hasLogo = $logoPath && is_file($logoPath);

    $signaturePath = $business?->signature_path
        ? storage_path('app/public/'.ltrim($business->signature_path, '/'))
        : null;
    $hasSignature = $signaturePath && is_file($signaturePath);

    $sealPath = $business?->seal_path
        ? storage_path('app/public/'.ltrim($business->seal_path, '/'))
        : null;
    $hasSeal = $sealPath && is_file($sealPath);

    $addressLine = collect([$business?->address, $business?->city, $business?->state, $business?->pincode])
        ->filter()->implode(', ');
    $statutory = collect([
        $business?->gst ? 'GSTIN: '.$business->gst : null,
        $business?->pan ? 'PAN: '.$business->pan : null,
        $business?->cin ? 'CIN: '.$business->cin : null,
    ])->filter()->implode('  ·  ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Document')</title>
    <style>
        @page { margin: @yield('page-margin', '104px 40px 74px 40px'); size: @yield('page-size', 'A4') @yield('page-orientation', $orientation); }
        /* NOT `*` and NOT `body`: DOMPDF implements the @page margin as the
           body element's margin, so resetting either silently collapses the
           page margin to zero — which also drops any position:fixed header or
           footer off the top of the sheet. Reset the elements we actually use
           and leave body alone. */
        div, p, span, table, thead, tbody, tfoot, tr, th, td,
        h1, h2, h3, h4, h5, h6, ul, ol, li, img, figure { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #24292f; line-height: 1.5; }

        /* ── Letterhead ─────────────────────────────────────────────────
           Fixed-position so DOMPDF repeats it on every page. --- */
        .lh-header { position: fixed; top: -84px; left: 0; right: 0; height: 74px; }
        .lh-header table { width: 100%; border-collapse: collapse; }
        .lh-header td { vertical-align: middle; }
        .lh-logo { width: 150px; }
        .lh-logo img { max-height: 52px; max-width: 145px; }
        .lh-name { font-size: 16px; font-weight: bold; color: #0f2b5b; letter-spacing: .2px; }
        .lh-legal { font-size: 9px; color: #6b7280; margin-top: 1px; }
        .lh-contact { text-align: right; font-size: 8.5px; color: #4b5563; line-height: 1.55; }
        .lh-rule { border-bottom: 2px solid #0f2b5b; margin-top: 6px; }
        .lh-statutory { font-size: 8px; color: #6b7280; padding-top: 3px; letter-spacing: .2px; }

        /* ── Footer ─────────────────────────────────────────────────── */
        .lh-footer { position: fixed; bottom: -54px; left: 0; right: 0; height: 44px;
                     border-top: 1px solid #d9dee5; padding-top: 5px; }
        .lh-footer table { width: 100%; border-collapse: collapse; }
        .lh-footer td { font-size: 7.5px; color: #9099a8; vertical-align: top; }
        .lh-footer td.right { text-align: right; }
        .lh-footer .note { color: #6b7280; }
        /* Only counter(page) is printed: DOMPDF resolves counter(pages) to 0,
           and a wrong total on a statutory register is worse than none.
           A real total needs the canvas page_text() API after render. */
        .pagenum:after { content: counter(page); }

        /* ── Watermark ──────────────────────────────────────────────── */
        .watermark { position: fixed; top: 38%; left: 0; right: 0; text-align: center;
                     font-size: 92px; font-weight: bold; letter-spacing: 10px;
                     transform: rotate(-24deg); z-index: -1; }

        /* ── Document heading ───────────────────────────────────────── */
        .doc-head { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .doc-head td { vertical-align: bottom; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0f2b5b; text-transform: uppercase; letter-spacing: .6px; }
        .doc-sub { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .doc-meta { text-align: right; font-size: 9px; color: #4b5563; }
        .doc-meta .row { margin-top: 1.5px; }
        .doc-meta .label { color: #8b95a5; }
        .doc-meta .val { font-weight: bold; color: #24292f; }

        /* ── Shared building blocks every document may use ──────────── */
        table.data { width: 100%; border-collapse: collapse; }
        table.data thead th { background: #0f2b5b; color: #fff; text-align: left;
                              padding: 6px 7px; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; }
        table.data thead th.tr { text-align: right; }
        table.data thead th.tc { text-align: center; }
        table.data tbody td { padding: 5px 7px; border-bottom: 1px solid #e6e9ee; vertical-align: top; }
        table.data tbody td.tr { text-align: right; }
        table.data tbody td.tc { text-align: center; }
        table.data tbody tr.alt td { background: #f7f9fc; }
        table.data tfoot td { padding: 6px 7px; border-top: 1.5px solid #0f2b5b; font-weight: bold; background: #f0f3f8; }
        table.data tfoot td.tr { text-align: right; }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 0; vertical-align: top; font-size: 9.5px; }
        .kv td.k { color: #6b7280; width: 130px; }
        .kv td.v { font-weight: bold; color: #24292f; }

        .panel { background: #f0f3f8; padding: 9px 11px; }
        .panel-title { font-size: 9px; font-weight: bold; color: #0f2b5b;
                       text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }

        .words { background: #f0f3f8; border-left: 3px solid #0f2b5b; padding: 6px 10px; font-size: 9.5px; }
        .words .lbl { color: #6b7280; }
        .words strong { color: #0f2b5b; }

        .letter-body { font-size: 10.5px; line-height: 1.75; text-align: justify; }
        .letter-body p { margin-bottom: 9px; }
        .letter-body strong { color: #0f2b5b; }

        .badge { display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: bold;
                 text-transform: uppercase; letter-spacing: .4px; }
        .badge-green { background: #e6f6ee; color: #047857; }
        .badge-red { background: #fdeaea; color: #b91c1c; }
        .badge-amber { background: #fef4e6; color: #b45309; }
        .badge-grey { background: #eef1f5; color: #64748b; }

        .empty { text-align: center; padding: 26px; color: #9099a8; font-size: 9.5px; }
        .mt { margin-top: 10px; }
        .mt-lg { margin-top: 18px; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }

        /* ── Signature block ────────────────────────────────────────── */
        .sign-wrap { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .sign-wrap td { vertical-align: bottom; font-size: 9px; }
        .sign-box { width: 210px; }
        .sign-img { height: 42px; margin-bottom: 2px; }
        .sign-img img { max-height: 40px; max-width: 150px; }
        .seal-img img { max-height: 62px; max-width: 62px; opacity: .9; }
        .sign-line { border-top: 1px solid #24292f; padding-top: 3px; }
        .sign-name { font-weight: bold; color: #24292f; }
        .sign-role { color: #6b7280; font-size: 8.5px; }
    </style>
    @stack('styles')
</head>
<body>

@if(! empty($watermark))
    @php
        $wmColors = [
            'DRAFT' => 'rgba(107,114,128,0.13)',
            'PAID' => 'rgba(4,120,87,0.12)',
            'CANCELLED' => 'rgba(185,28,28,0.12)',
            'DUPLICATE' => 'rgba(15,43,91,0.10)',
        ];
        $wm = strtoupper($watermark);
    @endphp
    <div class="watermark" style="color: {{ $wmColors[$wm] ?? 'rgba(107,114,128,0.12)' }};">{{ $wm }}</div>
@endif

@if($lh)
    <div class="lh-header">
        <table>
            <tr>
                @if($hasLogo)
                    <td class="lh-logo"><img src="{{ $logoPath }}" alt="" /></td>
                @endif
                <td>
                    <div class="lh-name">{{ $business?->legal_name ?: ($business?->name ?: 'Company') }}</div>
                    @if($business?->legal_name && $business?->name && $business->legal_name !== $business->name)
                        <div class="lh-legal">{{ $business->name }}</div>
                    @endif
                    @if($addressLine)<div class="lh-legal">{{ $addressLine }}</div>@endif
                </td>
                <td class="lh-contact">
                    @if($business?->phone)<div>{{ $business->phone }}</div>@endif
                    @if($business?->email)<div>{{ $business->email }}</div>@endif
                    @if($business?->website)<div>{{ $business->website }}</div>@endif
                </td>
            </tr>
        </table>
        <div class="lh-rule"></div>
        @if($statutory)<div class="lh-statutory">{{ $statutory }}</div>@endif
    </div>

    <div class="lh-footer">
        <table>
            <tr>
                <td>
                    @if($business?->letterhead_footer)
                        <div class="note">{{ $business->letterhead_footer }}</div>
                    @endif
                    <div>Computer-generated on {{ $generatedAt ?? now()->format('d M Y, h:i A') }}</div>
                </td>
                <td class="right">
                    <div>{{ $business?->legal_name ?: ($business?->name ?: '') }}</div>
                    <div>Page <span class="pagenum"></span></div>
                </td>
            </tr>
        </table>
    </div>
@endif

<table class="doc-head">
    <tr>
        <td>
            <div class="doc-title">@yield('doc-title', 'Document')</div>
            @hasSection('doc-sub')<div class="doc-sub">@yield('doc-sub')</div>@endif
        </td>
        <td class="doc-meta">@yield('doc-meta')</td>
    </tr>
</table>

@yield('content')

@hasSection('signature')
    @yield('signature')
@else
    @includeWhen($lh, 'pdf._signature', ['business' => $business, 'hasSignature' => $hasSignature,
        'signaturePath' => $signaturePath, 'hasSeal' => $hasSeal, 'sealPath' => $sealPath])
@endif

</body>
</html>
