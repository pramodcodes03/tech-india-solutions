<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2d3748; background: #fff; line-height: 1.5; }

        .page { padding: 36px 40px 30px; }

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .company-name { font-size: 20px; font-weight: bold; color: #1e3a5f; letter-spacing: 0.5px; }
        .company-info { font-size: 9.5px; color: #718096; line-height: 1.7; margin-top: 5px; }
        .doc-title { font-size: 30px; font-weight: bold; color: #1e3a5f; letter-spacing: 4px; text-align: right; }
        .doc-subtitle { font-size: 9px; color: #a0aec0; letter-spacing: 2px; text-transform: uppercase; text-align: right; margin-top: 2px; }

        /* Meta table */
        .meta-table { border-collapse: collapse; margin-left: auto; margin-top: 10px; }
        .meta-table td { padding: 2.5px 0; font-size: 10px; }
        .meta-label { color: #718096; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 3px 16px 3px 0; text-align: right; min-width: 90px; }
        .meta-value { color: #2d3748; font-weight: bold; white-space: nowrap; padding: 3px 0; }

        /* Divider */
        .divider { border: none; border-top: 2.5px solid #1e3a5f; margin: 16px 0 18px; }
        .divider-thin { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }

        /* Bill To */
        .section-label { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #a0aec0; margin-bottom: 6px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; display: inline-block; }
        .bill-name { font-size: 13px; font-weight: bold; color: #1e3a5f; margin: 6px 0 3px; }
        .bill-info { font-size: 10px; color: #4a5568; line-height: 1.7; }

        /* Status badge */
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; }
        .badge-draft    { background: #edf2f7; color: #4a5568; }
        .badge-sent     { background: #ebf8ff; color: #2b6cb0; }
        .badge-accepted { background: #f0fff4; color: #276749; }
        .badge-rejected { background: #fff5f5; color: #9b2c2c; }
        .badge-expired  { background: #fffff0; color: #744210; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #1e3a5f; color: #fff; padding: 9px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; }
        .items-table th.r, .items-table td.r { text-align: right; }
        .items-table th.c, .items-table td.c { text-align: center; }
        .items-table td { padding: 9px 8px; border-bottom: 1px solid #edf2f7; font-size: 10.5px; vertical-align: top; }
        .items-table tbody tr:nth-child(even) td { background: #f7fafc; }
        .item-name { font-weight: bold; color: #1e3a5f; }

        /* Totals */
        .totals-outer { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .notes-cell { vertical-align: top; padding-right: 24px; width: 52%; }
        .totals-cell { vertical-align: top; width: 48%; }
        .notes-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #718096; margin-bottom: 5px; }
        .notes-text  { font-size: 9.5px; color: #4a5568; line-height: 1.65; }

        .totals-box { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 4px; }
        .totals-box td { padding: 8px 14px; font-size: 10.5px; }
        .totals-box tr.row-border td { border-top: 1px solid #e2e8f0; }
        .t-label { color: #718096; text-align: left; }
        .t-value { text-align: right; font-weight: bold; color: #2d3748; white-space: nowrap; }
        .t-discount { color: #c53030; }
        .t-tax      { color: #276749; }
        .grand-row td { background: #1e3a5f; color: #fff; font-size: 13px; font-weight: bold; border-top: none; }
        .grand-row td.t-label { color: #bee3f8; font-size: 11px; letter-spacing: 0.3px; }
        .grand-row td.t-value { color: #fff; font-size: 15px; }

        /* Signature */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .sig-line { border-top: 1px solid #2d3748; padding-top: 6px; }
        .sig-name  { font-size: 10px; font-weight: bold; color: #1e3a5f; }
        .sig-label { font-size: 8.5px; color: #a0aec0; margin-top: 1px; }

        /* Footer */
        .footer { margin-top: 26px; padding-top: 12px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8.5px; color: #a0aec0; }

        /* Watermark */
        .watermark { position: fixed; top: 36%; left: 5%; font-size: 88px; font-weight: 900; color: rgba(0,0,0,0.045); transform: rotate(-38deg); text-transform: uppercase; letter-spacing: 12px; }

        /* Accent bar */
        .accent { background: #1e3a5f; height: 6px; width: 100%; margin-bottom: 0; }
    </style>
</head>
<body>

@if($quotation->status === 'draft')
<div class="watermark">DRAFT</div>
@endif

<div class="accent"></div>

<div class="page">

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width:55%; vertical-align:top;">
                <div class="company-name">{{ $settings['company_name'] ?? 'Leather Technics' }}</div>
                <div class="company-info">
                    @if(!empty($settings['company_address'])){{ $settings['company_address'] }}<br>@endif
                    @if(!empty($settings['company_phone']))Phone: {{ $settings['company_phone'] }}<br>@endif
                    @if(!empty($settings['company_email']))Email: {{ $settings['company_email'] }}<br>@endif
                    @if(!empty($settings['company_gstin']))<strong>GSTIN:</strong> {{ $settings['company_gstin'] }}@endif
                </div>
            </td>
            <td style="width:45%; vertical-align:top; text-align:right;">
                <div class="doc-title">QUOTATION</div>
                <div class="doc-subtitle">Price Estimate</div>
                <table class="meta-table" style="border-spacing: 0 4px;">
                    <tr>
                        <td class="meta-label" style="width:90px; padding-right:16px;">Quotation #</td>
                        <td class="meta-value">{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label" style="width:90px; padding-right:16px;">Date</td>
                        <td class="meta-value">{{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label" style="width:90px; padding-right:16px;">Valid Until</td>
                        <td class="meta-value">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label" style="width:90px; padding-right:16px;">Status</td>
                        <td class="meta-value">
                            <span class="badge badge-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- BILL TO --}}
    <div class="section-label">Bill To</div>
    <div class="bill-name">{{ $quotation->customer->name ?? '-' }}</div>
    <div class="bill-info">
        @if(trim($quotation->customer->address ?? '')){{ trim($quotation->customer->address) }}<br>@endif
        @php
            $cityName = is_object($quotation->customer->city ?? null)
                ? ($quotation->customer->city->name ?? '')
                : ($quotation->customer->city ?? '');
            $parts = array_filter([trim($cityName), trim($quotation->customer->state ?? '')]);
            $loc = implode(', ', $parts);
        @endphp
        @if($loc){{ $loc }}@if(trim($quotation->customer->pincode ?? '')) &ndash; {{ trim($quotation->customer->pincode) }}@endif<br>@endif
        @if($quotation->customer->phone ?? false)Phone: {{ $quotation->customer->phone }}<br>@endif
        @if($quotation->customer->email ?? false)Email: {{ $quotation->customer->email }}<br>@endif
        @if($quotation->customer->gstin ?? false)<strong>GSTIN:</strong> {{ $quotation->customer->gstin }}@endif
    </div>

    <hr class="divider-thin" style="margin-top:16px;">

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="c" style="width:26px;">#</th>
                <th style="text-align:left;">Description</th>
                <th style="width:60px; text-align:left;">HSN</th>
                <th class="r" style="width:44px;">Qty</th>
                <th class="c" style="width:36px;">Unit</th>
                <th class="r" style="width:72px;">Rate (&#8377;)</th>
                <th class="r" style="width:44px;">Disc%</th>
                <th class="r" style="width:44px;">Tax%</th>
                <th class="r" style="width:80px;">Amount (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $idx => $item)
            @php
                $g = floatval($item->quantity ?? 0) * floatval($item->rate ?? 0);
                $a = $g - $g * (floatval($item->discount_percent ?? 0) / 100);
                $lt = round($a + $a * (floatval($item->tax_percent ?? 0) / 100), 2);
            @endphp
            <tr>
                <td class="c" style="color:#a0aec0;">{{ $idx + 1 }}</td>
                <td><span class="item-name">{{ $item->description ?: ($item->product->name ?? '-') }}</span></td>
                <td style="color:#718096;">{{ $item->hsn_code ?: '-' }}</td>
                <td class="r">{{ rtrim(rtrim(number_format(floatval($item->quantity), 2), '0'), '.') }}</td>
                <td class="c" style="color:#718096;">{{ ucfirst($item->unit ?? '') }}</td>
                <td class="r">{{ number_format(floatval($item->rate), 2) }}</td>
                <td class="r" style="color:#718096;">{{ number_format(floatval($item->discount_percent ?? 0), 2) }}%</td>
                <td class="r" style="color:#718096;">{{ number_format(floatval($item->tax_percent ?? 0), 2) }}%</td>
                <td class="r" style="font-weight:bold; color:#1e3a5f;">{{ number_format($lt, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS + NOTES --}}
    <table class="totals-outer">
        <tr>
            <td class="notes-cell">
                @if($quotation->terms)
                    <div class="notes-title">Terms &amp; Conditions</div>
                    <div class="notes-text">{{ $quotation->terms }}</div>
                @endif
                @if($quotation->notes)
                    <div style="margin-top:10px;">
                        <div class="notes-title">Notes</div>
                        <div class="notes-text">{{ $quotation->notes }}</div>
                    </div>
                @endif
            </td>
            <td class="totals-cell">
                <table class="totals-box">
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value">{{ number_format($pdfSubtotal, 2) }}</td>
                    </tr>
                    @if($pdfDiscAmt > 0)
                    <tr class="row-border">
                        <td class="t-label">Discount{{ $quotation->discount_type === 'percent' ? ' (' . number_format($pdfDiscVal, 0) . '%)' : '' }}</td>
                        <td class="t-value t-discount">&minus; {{ number_format($pdfDiscAmt, 2) }}</td>
                    </tr>
                    @endif
                    @if($pdfTaxAmt > 0)
                    <tr class="row-border">
                        <td class="t-label">Tax ({{ number_format(floatval($quotation->tax_percent ?? 0), 0) }}%)</td>
                        <td class="t-value t-tax">+ {{ number_format($pdfTaxAmt, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-row row-border">
                        <td class="t-label">Grand Total</td>
                        <td class="t-value">&#8377; {{ number_format($pdfGrandTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- SIGNATURE --}}
    <table class="sig-table">
        <tr>
            <td style="width:42%; vertical-align:bottom;">
                <div class="sig-line">
                    <div class="sig-name">{{ $settings['company_name'] ?? 'Company Name' }}</div>
                    <div class="sig-label">Authorised Signatory</div>
                </div>
            </td>
            <td style="width:16%;"></td>
            <td style="width:42%; vertical-align:bottom;">
                <div class="sig-line">
                    <div class="sig-name" style="color:#cbd5e0;">________________________</div>
                    <div class="sig-label">Customer Acceptance &amp; Stamp</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        This is a computer-generated quotation and does not require a physical signature.
        @if(!empty($settings['company_email']))&nbsp;&bull;&nbsp;{{ $settings['company_email'] }}@endif
        @if(!empty($settings['company_phone']))&nbsp;&bull;&nbsp;{{ $settings['company_phone'] }}@endif
    </div>

</div>
</body>
</html>
