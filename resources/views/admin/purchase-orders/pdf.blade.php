<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $entity->po_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2d3748; background: #fff; line-height: 1.5; }
        .page { padding: 36px 40px 30px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .company-name { font-size: 20px; font-weight: bold; color: #1e3a5f; letter-spacing: 0.5px; }
        .company-info { font-size: 9.5px; color: #718096; line-height: 1.7; margin-top: 5px; }
        .doc-title { font-size: 26px; font-weight: bold; color: #1e3a5f; letter-spacing: 3px; text-align: right; }
        .doc-subtitle { font-size: 9px; color: #a0aec0; letter-spacing: 2px; text-transform: uppercase; text-align: right; margin-top: 2px; }
        .meta-table { border-collapse: collapse; margin-left: auto; margin-top: 10px; }
        .meta-label { color: #718096; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 3px 16px 3px 0; text-align: right; min-width: 100px; }
        .meta-value { color: #2d3748; font-weight: bold; white-space: nowrap; padding: 3px 0; }
        .divider { border: none; border-top: 2.5px solid #1e3a5f; margin: 16px 0 18px; }
        .divider-thin { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }
        .section-label { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #a0aec0; margin-bottom: 6px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; display: inline-block; }
        .bill-name { font-size: 13px; font-weight: bold; color: #1e3a5f; margin: 6px 0 3px; }
        .bill-info { font-size: 10px; color: #4a5568; line-height: 1.7; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; background: #ebf8ff; color: #2b6cb0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #1e3a5f; color: #fff; padding: 9px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; }
        .items-table th.r, .items-table td.r { text-align: right; }
        .items-table th.c, .items-table td.c { text-align: center; }
        .items-table td { padding: 9px 8px; border-bottom: 1px solid #edf2f7; font-size: 10.5px; vertical-align: top; }
        .items-table tbody tr:nth-child(even) td { background: #f7fafc; }
        .item-name { font-weight: bold; color: #1e3a5f; }
        .totals-outer { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .notes-cell { vertical-align: top; padding-right: 24px; width: 52%; }
        .totals-cell { vertical-align: top; width: 48%; }
        .notes-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #718096; margin-bottom: 5px; }
        .notes-text  { font-size: 9.5px; color: #4a5568; line-height: 1.65; }
        .totals-box { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
        .totals-box td { padding: 8px 14px; font-size: 10.5px; }
        .totals-box tr.row-border td { border-top: 1px solid #e2e8f0; }
        .t-label { color: #718096; text-align: left; }
        .t-value { text-align: right; font-weight: bold; color: #2d3748; white-space: nowrap; }
        .t-discount { color: #c53030; }
        .t-tax      { color: #276749; }
        .grand-row td { background: #1e3a5f; color: #fff; font-size: 13px; font-weight: bold; border-top: none; }
        .grand-row td.t-label { color: #bee3f8; font-size: 11px; letter-spacing: 0.3px; }
        .grand-row td.t-value { color: #fff; font-size: 15px; }
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .sig-line { border-top: 1px solid #2d3748; padding-top: 6px; }
        .sig-name  { font-size: 10px; font-weight: bold; color: #1e3a5f; }
        .sig-label { font-size: 8.5px; color: #a0aec0; margin-top: 1px; }
        .footer { margin-top: 26px; padding-top: 12px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8.5px; color: #a0aec0; }
        .accent { background: #1e3a5f; height: 6px; width: 100%; }
    </style>
</head>
<body>

<div class="accent"></div>

<div class="page">

    <table class="header-table">
        <tr>
            <td style="width:55%; vertical-align:top;">
                @php
                    $bizName    = $business?->name    ?? ($settings['company_name'] ?? 'Company');
                    $bizAddress = $business?->address ?? ($settings['company_address'] ?? '');
                    $bizCityLine = $business
                        ? collect([$business->city, $business->state, $business->pincode])->filter()->implode(', ')
                        : '';
                    $bizPhone = $business?->phone ?? ($settings['company_phone'] ?? '');
                    $bizEmail = $business?->email ?? ($settings['company_email'] ?? '');
                    $bizGst   = $business?->gst   ?? ($settings['company_gstin'] ?? ($settings['company_gst'] ?? ''));
                @endphp
                <div class="company-name">{{ $bizName }}</div>
                <div class="company-info">
                    @if($bizAddress){{ $bizAddress }}<br>@endif
                    @if($bizCityLine){{ $bizCityLine }}<br>@endif
                    @if($bizPhone)Phone: {{ $bizPhone }}<br>@endif
                    @if($bizEmail)Email: {{ $bizEmail }}<br>@endif
                    @if($bizGst)<strong>GSTIN:</strong> {{ $bizGst }}@endif
                </div>
            </td>
            <td style="width:45%; vertical-align:top; text-align:right;">
                <div class="doc-title">PURCHASE ORDER</div>
                <div class="doc-subtitle">Order to Vendor</div>
                <table class="meta-table" style="border-spacing: 0 4px;">
                    <tr>
                        <td class="meta-label">PO #</td>
                        <td class="meta-value">{{ $entity->po_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date</td>
                        <td class="meta-value">{{ $entity->po_date ? \Carbon\Carbon::parse($entity->po_date)->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Expected</td>
                        <td class="meta-value">{{ $entity->expected_date ? \Carbon\Carbon::parse($entity->expected_date)->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Status</td>
                        <td class="meta-value"><span class="badge">{{ ucfirst($entity->status) }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <div class="section-label">Vendor</div>
    <div class="bill-name">{{ $entity->vendor->name ?? '-' }}</div>
    <div class="bill-info">
        @if(trim($entity->vendor->address ?? '')){{ trim($entity->vendor->address) }}<br>@endif
        @php
            $parts = array_filter([trim($entity->vendor->city ?? ''), trim($entity->vendor->state ?? '')]);
            $loc = implode(', ', $parts);
        @endphp
        @if($loc){{ $loc }}@if(trim($entity->vendor->pincode ?? '')) &ndash; {{ trim($entity->vendor->pincode) }}@endif<br>@endif
        @if($entity->vendor->phone ?? false)Phone: {{ $entity->vendor->phone }}<br>@endif
        @if($entity->vendor->email ?? false)Email: {{ $entity->vendor->email }}<br>@endif
        @if($entity->vendor->gstin ?? false)<strong>GSTIN:</strong> {{ $entity->vendor->gstin }}@endif
    </div>

    <hr class="divider-thin" style="margin-top:16px;">

    <table class="items-table">
        <thead>
            <tr>
                <th class="c" style="width:26px;">#</th>
                <th style="text-align:left;">Description</th>
                <th class="r" style="width:50px;">Qty</th>
                <th class="c" style="width:40px;">Unit</th>
                <th class="r" style="width:80px;">Rate (&#8377;)</th>
                <th class="r" style="width:48px;">Tax%</th>
                <th class="r" style="width:90px;">Amount (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entity->items as $idx => $item)
            @php
                $g = floatval($item->quantity ?? 0) * floatval($item->rate ?? 0);
                $lt = round($g + $g * (floatval($item->tax_percent ?? 0) / 100), 2);
            @endphp
            <tr>
                <td class="c" style="color:#a0aec0;">{{ $idx + 1 }}</td>
                <td><span class="item-name">{{ $item->description ?: ($item->product->name ?? '-') }}</span></td>
                <td class="r">{{ rtrim(rtrim(number_format(floatval($item->quantity), 2), '0'), '.') }}</td>
                <td class="c" style="color:#718096;">{{ ucfirst($item->unit ?? '') }}</td>
                <td class="r">{{ number_format(floatval($item->rate), 2) }}</td>
                <td class="r" style="color:#718096;">{{ number_format(floatval($item->tax_percent ?? 0), 2) }}%</td>
                <td class="r" style="font-weight:bold; color:#1e3a5f;">{{ number_format($lt, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-outer">
        <tr>
            <td class="notes-cell">
                @if($entity->terms)
                    <div class="notes-title">Terms &amp; Conditions</div>
                    <div class="notes-text">{{ $entity->terms }}</div>
                @endif
                @if($entity->notes)
                    <div style="margin-top:10px;">
                        <div class="notes-title">Notes</div>
                        <div class="notes-text">{{ $entity->notes }}</div>
                    </div>
                @endif
            </td>
            <td class="totals-cell">
                <table class="totals-box">
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value">{{ number_format($pdfSubtotal ?? $entity->subtotal ?? 0, 2) }}</td>
                    </tr>
                    @if(($pdfDiscAmt ?? 0) > 0)
                    <tr class="row-border">
                        <td class="t-label">Discount{{ $entity->discount_type === 'percent' ? ' (' . number_format($pdfDiscVal ?? 0, 0) . '%)' : '' }}</td>
                        <td class="t-value t-discount">&minus; {{ number_format($pdfDiscAmt, 2) }}</td>
                    </tr>
                    @endif
                    @if(($pdfTaxAmt ?? $entity->tax_amount ?? 0) > 0)
                    <tr class="row-border">
                        <td class="t-label">Tax ({{ number_format(floatval($entity->tax_percent ?? 0), 0) }}%)</td>
                        <td class="t-value t-tax">+ {{ number_format($pdfTaxAmt ?? $entity->tax_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-row row-border">
                        <td class="t-label">Grand Total</td>
                        <td class="t-value">&#8377; {{ number_format($pdfGrandTotal ?? $entity->grand_total ?? 0, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="sig-table">
        <tr>
            <td style="width:42%; vertical-align:bottom;">
                <div class="sig-line">
                    <div class="sig-name">{{ $business?->name ?? ($settings['company_name'] ?? 'Company Name') }}</div>
                    <div class="sig-label">Authorised Signatory</div>
                </div>
            </td>
            <td style="width:16%;"></td>
            <td style="width:42%; vertical-align:bottom;">
                <div class="sig-line">
                    <div class="sig-name" style="color:#cbd5e0;">________________________</div>
                    <div class="sig-label">Vendor Acknowledgement</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Purchase Order issued by {{ $business?->name ?? 'us' }}.
        @php
            $footerEmail = $business?->email ?? ($settings['company_email'] ?? '');
            $footerPhone = $business?->phone ?? ($settings['company_phone'] ?? '');
        @endphp
        @if($footerEmail)&nbsp;&bull;&nbsp;{{ $footerEmail }}@endif
        @if($footerPhone)&nbsp;&bull;&nbsp;{{ $footerPhone }}@endif
    </div>

</div>
</body>
</html>
