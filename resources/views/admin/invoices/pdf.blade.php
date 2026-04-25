<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #2b2b2b; line-height: 1.45; }

        /* Layout helpers — using real <table> not display:table for DOMPDF */
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; }
        .gap-cell { width: 10px; padding: 0; }

        .doc-title { font-size: 24px; font-weight: bold; color: #6b46c1; letter-spacing: 0.5px; margin-bottom: 6px; }
        .doc-meta { font-size: 10.5px; color: #444; }
        .doc-meta .row { margin-top: 2px; }
        .doc-meta .label { color: #666; display: inline-block; min-width: 78px; }
        .doc-meta .val { font-weight: bold; color: #1a1a2e; }
        .logo-img { max-height: 56px; max-width: 180px; }

        /* Billed By / Billed To boxes (cells of layout table) */
        .party-box { background-color: #f3eefb; padding: 11px 13px; }
        .party-title { font-size: 12px; font-weight: bold; color: #6b46c1; margin-bottom: 5px; }
        .party-name { font-weight: bold; font-size: 12px; color: #1a1a2e; margin-bottom: 3px; }
        .party-line { font-size: 10.5px; color: #444; line-height: 1.5; }
        .party-line strong { color: #1a1a2e; }

        /* Items table */
        .items { width: 100%; border-collapse: collapse; }
        .items thead th { background-color: #6b46c1; color: #fff; padding: 8px 9px; text-align: left; font-size: 10px; font-weight: bold; }
        .items thead th.tr { text-align: right; }
        .items thead th.tc { text-align: center; }
        .items tbody td { padding: 7px 9px; border-bottom: 1px solid #ececec; font-size: 10.5px; vertical-align: top; }
        .items tbody td.tr { text-align: right; }
        .items tbody td.tc { text-align: center; }

        /* Totals */
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; font-size: 10.5px; }
        .totals td.label { color: #666; }
        .totals td.val { text-align: right; font-weight: bold; color: #1a1a2e; }
        .totals tr.grand td { border-top: 1.5px solid #1a1a2e; padding-top: 8px; font-size: 12.5px; }
        .totals tr.grand td.label { color: #1a1a2e; }
        .totals tr.due td { background-color: #1a1a2e; color: #fff; padding: 8px 10px; font-size: 12px; }
        .totals tr.due td.label { color: #fff; }

        .amount-words { background-color: #f7f5fb; border-left: 3px solid #6b46c1; padding: 7px 11px; font-size: 10.5px; color: #444; }
        .amount-words strong { color: #1a1a2e; }

        /* Bank box */
        .bank-box { background-color: #f3eefb; padding: 11px 13px; }
        .bank-title { font-size: 11px; font-weight: bold; color: #6b46c1; margin-bottom: 5px; }
        .bank-line { font-size: 10.5px; line-height: 1.6; }
        .bank-line .l { display: inline-block; width: 95px; color: #666; }
        .bank-line .r { font-weight: bold; color: #1a1a2e; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-unpaid, .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-cancelled, .status-draft { background-color: #e0e0e0; color: #555; }

        /* Terms */
        .terms-box { padding: 9px 11px; background-color: #fafafa; }
        .terms-title { font-size: 11px; font-weight: bold; color: #6b46c1; margin-bottom: 4px; }
        .terms-text { font-size: 10px; color: #555; line-height: 1.5; white-space: pre-line; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #999; padding-top: 8px; border-top: 1px solid #eee; }

        /* Signature */
        .sig-cell { padding-top: 24px; border-top: 1px solid #999; font-size: 10px; color: #666; }

        .mb-12 { margin-bottom: 12px; }
        .mb-14 { margin-bottom: 14px; }
        .mt-12 { margin-top: 12px; }
    </style>
</head>
<body>

@php
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
    $bankAccType    = $settings['bank_account_type'] ?? '';
    $bankHolder     = $settings['bank_account_holder'] ?? $companyName;
    $bankBranch     = $settings['bank_branch']     ?? '';
    $currencySymbol = $settings['currency_symbol'] ?? '₹';

    $logoPath = public_path($companyLogo);
    $logoExists = $companyLogo && file_exists($logoPath);

    $discountAmount = $invoice->discount_type === 'percent'
        ? round((float) $invoice->subtotal * (float) $invoice->discount_value / 100, 2)
        : (float) $invoice->discount_value;

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

    $hasBank = $bankName || $bankAccount || $bankIfsc;
    $hasTerms = $invoice->terms || ($settings['terms_and_conditions'] ?? null);
@endphp

{{-- HEADER: title/meta on left, logo on right --}}
<table class="layout mb-14">
    <tr>
        <td style="width: 65%;">
            <div class="doc-title">Tax Invoice</div>
            <div class="doc-meta">
                <div class="row"><span class="label">Invoice No #</span><span class="val">{{ $invoice->invoice_number }}</span></div>
                <div class="row"><span class="label">Invoice Date</span><span class="val">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') }}</span></div>
                <div class="row"><span class="label">Due Date</span><span class="val">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '—' }}</span></div>
                <div class="row"><span class="label">Status</span><span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span></div>
            </div>
        </td>
        <td style="width: 35%; text-align: right;">
            @if($logoExists)
                <img src="{{ $logoPath }}" class="logo-img" alt="{{ $companyName }}" />
            @else
                <div style="font-size: 16px; font-weight: bold; color: #6b46c1;">{{ $companyName }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- BILLED BY / BILLED TO --}}
<table class="layout mb-12">
    <tr>
        <td style="width: 49%;">
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
        </td>
        <td class="gap-cell"></td>
        <td style="width: 49%;">
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
        </td>
    </tr>
</table>

{{-- ITEMS --}}
<table class="items mb-12">
    <thead>
        <tr>
            <th class="tc" style="width: 26px;">#</th>
            <th>Item</th>
            <th style="width: 56px;">HSN</th>
            <th class="tc" style="width: 40px;">Qty</th>
            <th class="tr" style="width: 78px;">Rate</th>
            <th class="tr" style="width: 44px;">Tax%</th>
            <th class="tr" style="width: 80px;">Tax</th>
            <th class="tr" style="width: 86px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $i => $item)
            @php
                $lineSub = (float) $item->line_total;
                $lineTax = round($lineSub * ((float) ($item->tax_percent ?? 0) / 100), 2);
            @endphp
            <tr>
                <td class="tc">{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->hsn_code ?? '-' }}</td>
                <td class="tc">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
                <td class="tr">{{ $currencySymbol }}{{ number_format($item->rate, 2) }}</td>
                <td class="tr">{{ rtrim(rtrim(number_format($item->tax_percent ?? 0, 2, '.', ''), '0'), '.') }}%</td>
                <td class="tr">{{ $currencySymbol }}{{ number_format($lineTax, 2) }}</td>
                <td class="tr">{{ $currencySymbol }}{{ number_format($lineSub + $lineTax, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- AMOUNT IN WORDS + TOTALS --}}
<table class="layout mb-12">
    <tr>
        <td style="width: 55%; padding-right: 12px;">
            <div class="amount-words">
                <strong>Total (in words):</strong> {{ strtoupper($toWords((float) $invoice->grand_total)) }}
            </div>
        </td>
        <td style="width: 45%;">
            <table class="totals">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="val">{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($discountAmount > 0)
                    <tr>
                        <td class="label">Discount @if($invoice->discount_type === 'percent')({{ rtrim(rtrim(number_format($invoice->discount_value, 2, '.', ''), '0'), '.') }}%)@endif</td>
                        <td class="val">− {{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="label">Tax</td>
                        <td class="val">+ {{ $currencySymbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td class="label">Grand Total</td>
                    <td class="val">{{ $currencySymbol }}{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
                @if($invoice->amount_paid > 0)
                    <tr>
                        <td class="label">Amount Paid</td>
                        <td class="val">− {{ $currencySymbol }}{{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                @endif
                <tr class="due">
                    <td class="label">Amount Due</td>
                    <td class="val">{{ $currencySymbol }}{{ number_format(max(0, (float) $invoice->grand_total - (float) $invoice->amount_paid), 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- BANK + TERMS --}}
@if($hasBank || $hasTerms)
<table class="layout mb-12">
    <tr>
        @if($hasBank)
        <td style="width: 49%;">
            <div class="bank-box">
                <div class="bank-title">Bank Details</div>
                <div class="bank-line">
                    @if($bankHolder)<span class="l">Account Holder</span><span class="r">{{ $bankHolder }}</span><br>@endif
                    @if($bankName)<span class="l">Bank</span><span class="r">{{ $bankName }}</span><br>@endif
                    @if($bankBranch)<span class="l">Branch</span><span class="r">{{ $bankBranch }}</span><br>@endif
                    @if($bankAccount)<span class="l">Account No.</span><span class="r">{{ $bankAccount }}</span><br>@endif
                    @if($bankIfsc)<span class="l">IFSC</span><span class="r">{{ $bankIfsc }}</span><br>@endif
                    @if($bankAccType)<span class="l">Account Type</span><span class="r">{{ $bankAccType }}</span>@endif
                </div>
            </div>
        </td>
        @endif
        @if($hasBank && $hasTerms)<td class="gap-cell"></td>@endif
        @if($hasTerms)
        <td style="width: {{ $hasBank ? '49%' : '100%' }};">
            <div class="terms-box">
                <div class="terms-title">Terms &amp; Conditions</div>
                <div class="terms-text">{{ $invoice->terms ?: ($settings['terms_and_conditions'] ?? '') }}</div>
            </div>
        </td>
        @endif
    </tr>
</table>
@endif

{{-- SIGNATURES --}}
<table class="layout mt-12">
    <tr>
        <td style="width: 45%;" class="sig-cell">
            Authorised Signatory<br>
            <strong style="color:#1a1a2e;">{{ $companyName }}</strong>
        </td>
        <td style="width: 10%;"></td>
        <td style="width: 45%;" class="sig-cell">
            Received By<br>
            <span style="color:#999;">Signature &amp; Date</span>
        </td>
    </tr>
</table>

<div class="footer">
    This is a computer-generated invoice.@if($companyEmail) For queries contact {{ $companyEmail }}.@endif
</div>

</body>
</html>
