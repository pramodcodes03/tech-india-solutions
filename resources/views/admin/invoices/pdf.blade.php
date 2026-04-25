<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2b2b2b; line-height: 1.5; }
        .container { padding: 30px 35px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 22px; }
        .header-left { display: table-cell; width: 65%; vertical-align: top; }
        .header-right { display: table-cell; width: 35%; vertical-align: top; text-align: right; }
        .doc-title { font-size: 28px; font-weight: bold; color: #6b46c1; letter-spacing: 0.5px; margin-bottom: 6px; }
        .doc-meta { font-size: 11px; color: #444; }
        .doc-meta .row { margin-top: 3px; }
        .doc-meta .label { color: #666; display: inline-block; min-width: 78px; }
        .doc-meta .val { font-weight: bold; color: #1a1a2e; }

        .logo-img { max-height: 60px; max-width: 200px; }

        /* Billed By / Billed To */
        .parties { display: table; width: 100%; margin-bottom: 18px; border-spacing: 10px 0; }
        .party-cell { display: table-cell; width: 50%; vertical-align: top; }
        .party-box { background-color: #f3eefb; border-radius: 6px; padding: 14px 16px; height: 100%; }
        .party-title { font-size: 13px; font-weight: bold; color: #6b46c1; margin-bottom: 6px; }
        .party-name { font-weight: bold; font-size: 13px; color: #1a1a2e; margin-bottom: 4px; }
        .party-line { font-size: 11px; color: #444; line-height: 1.55; }
        .party-line strong { color: #1a1a2e; }

        /* Items table */
        .items { width: 100%; border-collapse: collapse; margin-bottom: 18px; border-radius: 6px; overflow: hidden; }
        .items thead th { background-color: #6b46c1; color: #fff; padding: 9px 10px; text-align: left; font-size: 10.5px; font-weight: bold; }
        .items thead th.text-right { text-align: right; }
        .items thead th.text-center { text-align: center; }
        .items tbody td { padding: 9px 10px; border-bottom: 1px solid #ececec; font-size: 11px; vertical-align: top; }
        .items tbody td.text-right { text-align: right; }
        .items tbody td.text-center { text-align: center; }
        .items tbody tr:last-child td { border-bottom: none; }

        /* Totals row layout */
        .totals-wrap { display: table; width: 100%; margin-bottom: 18px; }
        .totals-left { display: table-cell; width: 55%; vertical-align: top; padding-right: 12px; font-size: 11px; color: #444; }
        .totals-right { display: table-cell; width: 45%; vertical-align: top; }
        .totals-right table { width: 100%; border-collapse: collapse; }
        .totals-right td { padding: 5px 0; font-size: 11px; }
        .totals-right td:first-child { color: #666; }
        .totals-right td:last-child { text-align: right; font-weight: bold; color: #1a1a2e; }
        .totals-right .grand td { border-top: 1.5px solid #1a1a2e; padding-top: 10px; font-size: 13.5px; }
        .totals-right .grand td:first-child { color: #1a1a2e; }
        .totals-right .due { background-color: #1a1a2e; color: #fff; }
        .totals-right .due td { color: #fff; padding: 9px 10px; font-size: 13px; }
        .totals-right .due td:first-child { color: #fff; }

        .amount-words { background-color: #f7f5fb; border-left: 3px solid #6b46c1; padding: 8px 12px; border-radius: 4px; font-size: 11px; color: #444; }
        .amount-words strong { color: #1a1a2e; }

        /* Bank + Terms */
        .bottom { display: table; width: 100%; margin-top: 18px; border-spacing: 10px 0; }
        .bottom-cell { display: table-cell; width: 50%; vertical-align: top; }
        .bank-box { background-color: #f3eefb; border-radius: 6px; padding: 14px 16px; }
        .bank-title { font-size: 12px; font-weight: bold; color: #6b46c1; margin-bottom: 8px; }
        .bank-row { display: table; width: 100%; margin-bottom: 4px; }
        .bank-row .l { display: table-cell; color: #666; width: 38%; font-size: 11px; }
        .bank-row .r { display: table-cell; font-weight: bold; color: #1a1a2e; font-size: 11px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-unpaid, .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #e0e0e0; color: #555; }
        .status-draft { background-color: #e0e0e0; color: #555; }

        /* Terms / footer */
        .terms { margin-top: 16px; padding: 12px 14px; background-color: #fafafa; border-radius: 5px; }
        .terms-title { font-size: 12px; font-weight: bold; color: #6b46c1; margin-bottom: 5px; }
        .terms-text { font-size: 10.5px; color: #555; line-height: 1.5; white-space: pre-line; }
        .footer { margin-top: 22px; text-align: center; font-size: 9.5px; color: #999; padding-top: 10px; border-top: 1px solid #eee; }

        /* Watermark */
        .watermark { position: fixed; top: 38%; left: 8%; font-size: 95px; font-weight: 900; color: rgba(107,70,193,0.05); transform: rotate(-30deg); text-transform: uppercase; letter-spacing: 12px; z-index: -1; }

        /* Signature */
        .sig { display: table; width: 100%; margin-top: 28px; }
        .sig-cell { display: table-cell; width: 45%; vertical-align: top; padding-top: 30px; border-top: 1px solid #999; font-size: 10.5px; color: #666; }
        .sig-spacer { display: table-cell; width: 10%; }
    </style>
</head>
<body>

@php
    // Settings (passed in by controller, with sensible fallbacks)
    $companyName    = $settings['company_name']    ?? 'Tech India Solutions';
    $companyAddress = $settings['company_address'] ?? '';
    $companyPhone   = $settings['company_phone']   ?? '';
    $companyEmail   = $settings['company_email']   ?? '';
    $companyGst     = $settings['company_gst']     ?? ($settings['company_gstin'] ?? '');
    $companyPan     = $settings['company_pan']     ?? '';
    $companyLogo    = $settings['company_logo']    ?? 'assets/images/logo.png';
    $bankName       = $settings['bank_name']       ?? '';
    $bankAccount    = $settings['bank_account']    ?? '';
    $bankIfsc       = $settings['bank_ifsc']       ?? '';
    $bankAccType    = $settings['bank_account_type'] ?? 'Current';
    $currencySymbol = $settings['currency_symbol'] ?? '₹';

    // Resolve logo path for DOMPDF (it needs an absolute filesystem path).
    $logoPath = public_path($companyLogo);
    $logoExists = $companyLogo && file_exists($logoPath);

    // Compute discount amount for display
    $discountAmount = $invoice->discount_type === 'percent'
        ? round((float) $invoice->subtotal * (float) $invoice->discount_value / 100, 2)
        : (float) $invoice->discount_value;

    // Convert grand total to words (Indian format)
    $toWords = function (float $n) {
        if ($n == 0) return 'Zero Rupees Only';
        $words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $convert = function ($n) use (&$convert, $words, $tens) {
            if ($n < 20) return $words[$n];
            if ($n < 100) return rtrim($tens[(int)($n/10)].' '.$words[$n%10]);
            return $words[(int)($n/100)].' Hundred '.($n%100 ? $convert($n%100) : '');
        };
        $n = (int) round($n);
        $crore = (int) ($n / 10000000); $n %= 10000000;
        $lakh  = (int) ($n / 100000);   $n %= 100000;
        $thou  = (int) ($n / 1000);     $n %= 1000;
        $hund  = $n;
        $out = '';
        if ($crore) $out .= $convert($crore).' Crore ';
        if ($lakh)  $out .= $convert($lakh).' Lakh ';
        if ($thou)  $out .= $convert($thou).' Thousand ';
        if ($hund)  $out .= $convert($hund);
        return trim($out).' Rupees Only';
    };
@endphp

@if($invoice->status === 'draft')
<div class="watermark">DRAFT</div>
@elseif($invoice->status === 'paid')
<div class="watermark" style="color:rgba(0,171,85,0.07);">PAID</div>
@elseif($invoice->status === 'cancelled')
<div class="watermark" style="color:rgba(231,81,90,0.07);">VOID</div>
@endif

<div class="container">

    {{-- Header: title + meta on left, logo on right --}}
    <div class="header">
        <div class="header-left">
            <div class="doc-title">Tax Invoice</div>
            <div class="doc-meta">
                <div class="row"><span class="label">Invoice No #</span><span class="val">{{ $invoice->invoice_number }}</span></div>
                <div class="row"><span class="label">Invoice Date</span><span class="val">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') }}</span></div>
                <div class="row"><span class="label">Due Date</span><span class="val">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '—' }}</span></div>
                <div class="row"><span class="label">Status</span><span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span></div>
            </div>
        </div>
        <div class="header-right">
            @if($logoExists)
                <img src="{{ $logoPath }}" class="logo-img" alt="{{ $companyName }}" />
            @else
                <div style="font-size:18px;font-weight:bold;color:#6b46c1;">{{ $companyName }}</div>
            @endif
        </div>
    </div>

    {{-- Billed By / Billed To --}}
    <div class="parties">
        <div class="party-cell">
            <div class="party-box">
                <div class="party-title">Billed By</div>
                <div class="party-name">{{ strtoupper($companyName) }}</div>
                <div class="party-line">
                    @if($companyAddress){!! nl2br(e($companyAddress)) !!}<br>@endif
                    @if($companyGst)<strong>GSTIN:</strong> {{ $companyGst }}<br>@endif
                    @if($companyPan)<strong>PAN:</strong> {{ $companyPan }}<br>@endif
                    @if($companyEmail)<strong>Email:</strong> {{ $companyEmail }}<br>@endif
                    @if($companyPhone)<strong>Phone:</strong> {{ $companyPhone }}@endif
                </div>
            </div>
        </div>
        <div class="party-cell">
            <div class="party-box">
                <div class="party-title">Billed To</div>
                <div class="party-name">{{ $invoice->customer->name ?? '—' }}</div>
                <div class="party-line">
                    @if($invoice->customer?->company){{ $invoice->customer->company }}<br>@endif
                    @if($invoice->customer?->billing_address){!! nl2br(e($invoice->customer->billing_address)) !!}<br>@endif
                    @php
                        $loc = collect([$invoice->customer?->city, $invoice->customer?->state, $invoice->customer?->pincode])->filter()->implode(', ');
                    @endphp
                    @if($loc){{ $loc }}<br>@endif
                    @if($invoice->customer?->gst_number)<strong>GSTIN:</strong> {{ $invoice->customer->gst_number }}<br>@endif
                    @if($invoice->customer?->phone)<strong>Phone:</strong> {{ $invoice->customer->phone }}<br>@endif
                    @if($invoice->customer?->email)<strong>Email:</strong> {{ $invoice->customer->email }}@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">#</th>
                <th>Item</th>
                <th style="width:60px;">HSN</th>
                <th style="width:46px;" class="text-center">Qty</th>
                <th style="width:84px;" class="text-right">Rate</th>
                <th style="width:50px;" class="text-right">Tax%</th>
                <th style="width:90px;" class="text-right">Tax</th>
                <th style="width:90px;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                @php
                    $lineSub = (float) $item->line_total;
                    $lineTax = round($lineSub * ((float) ($item->tax_percent ?? 0) / 100), 2);
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->hsn_code ?? '-' }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($item->tax_percent ?? 0, 2, '.', ''), '0'), '.') }}%</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($lineTax, 2) }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($lineSub + $lineTax, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Amount in words + Totals box --}}
    <div class="totals-wrap">
        <div class="totals-left">
            <div class="amount-words">
                <strong>Total (in words):</strong> {{ strtoupper($toWords((float) $invoice->grand_total)) }}
            </div>
        </div>
        <div class="totals-right">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($discountAmount > 0)
                    <tr>
                        <td>Discount @if($invoice->discount_type === 'percent')({{ rtrim(rtrim(number_format($invoice->discount_value, 2, '.', ''), '0'), '.') }}%)@endif</td>
                        <td>− {{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->tax_amount > 0)
                    <tr>
                        <td>Tax</td>
                        <td>+ {{ $currencySymbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Grand Total</td>
                    <td>{{ $currencySymbol }}{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
                @if($invoice->amount_paid > 0)
                    <tr>
                        <td>Amount Paid</td>
                        <td>− {{ $currencySymbol }}{{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                @endif
                <tr class="due">
                    <td>Amount Due</td>
                    <td>{{ $currencySymbol }}{{ number_format(max(0, (float) $invoice->grand_total - (float) $invoice->amount_paid), 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Bank details + Terms side-by-side --}}
    <div class="bottom">
        @if($bankName || $bankAccount || $bankIfsc)
        <div class="bottom-cell">
            <div class="bank-box">
                <div class="bank-title">Bank Details</div>
                @if($bankName)<div class="bank-row"><span class="l">Bank</span><span class="r">{{ $bankName }}</span></div>@endif
                @if($bankAccount)<div class="bank-row"><span class="l">Account No.</span><span class="r">{{ $bankAccount }}</span></div>@endif
                @if($bankIfsc)<div class="bank-row"><span class="l">IFSC</span><span class="r">{{ $bankIfsc }}</span></div>@endif
                @if($bankAccType)<div class="bank-row"><span class="l">Account Type</span><span class="r">{{ $bankAccType }}</span></div>@endif
            </div>
        </div>
        @endif

        @if($invoice->terms || ($settings['terms_and_conditions'] ?? null))
        <div class="bottom-cell">
            <div class="terms" style="margin-top:0;">
                <div class="terms-title">Terms &amp; Conditions</div>
                <div class="terms-text">{{ $invoice->terms ?: ($settings['terms_and_conditions'] ?? '') }}</div>
            </div>
        </div>
        @endif
    </div>

    {{-- Signature line --}}
    <div class="sig">
        <div class="sig-cell">
            Authorised Signatory<br>
            <strong style="color:#1a1a2e;">{{ $companyName }}</strong>
        </div>
        <div class="sig-spacer"></div>
        <div class="sig-cell">
            Received By<br>
            <span style="color:#999;">Signature &amp; Date</span>
        </div>
    </div>

    <div class="footer">
        This is a computer-generated invoice. @if($companyEmail) For queries contact {{ $companyEmail }}. @endif
    </div>
</div>

</body>
</html>
