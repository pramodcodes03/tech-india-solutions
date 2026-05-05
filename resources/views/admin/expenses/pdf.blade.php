<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment {{ $expense->isPaid() ? 'Receipt' : 'Voucher' }} - {{ $expense->expense_code }}</title>
    <style>
        @page { margin: 0; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #2b2b2b; line-height: 1.45; padding: 12mm 12mm; }

        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; }
        .gap-cell { width: 10px; padding: 0; }

        .doc-title { font-size: 24px; font-weight: bold; color: #122e6d; letter-spacing: 0.5px; margin-bottom: 6px; }
        .doc-sub { font-size: 11px; color: #666; margin-bottom: 12px; }
        .doc-meta { font-size: 10.5px; color: #444; }
        .doc-meta .row { margin-top: 2px; }
        .doc-meta .label { color: #666; display: inline-block; min-width: 90px; }
        .doc-meta .val { font-weight: bold; color: #1a1a2e; }
        .logo-img { max-height: 56px; max-width: 180px; }

        .party-box { background-color: #e8edf7; padding: 11px 13px; }
        .party-title { font-size: 12px; font-weight: bold; color: #122e6d; margin-bottom: 5px; }
        .party-name { font-weight: bold; font-size: 12px; color: #1a1a2e; margin-bottom: 3px; }
        .party-line { font-size: 10.5px; color: #444; line-height: 1.5; }
        .party-line strong { color: #1a1a2e; }

        .items { width: 100%; border-collapse: collapse; }
        .items thead th { background-color: #122e6d; color: #fff; padding: 8px 9px; text-align: left; font-size: 10px; font-weight: bold; }
        .items thead th.tr { text-align: right; }
        .items tbody td { padding: 9px; border-bottom: 1px solid #ececec; font-size: 10.5px; vertical-align: top; }
        .items tbody td.tr { text-align: right; }

        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; font-size: 10.5px; }
        .totals td.label { color: #666; }
        .totals td.val { text-align: right; font-weight: bold; color: #1a1a2e; }
        .totals tr.grand td { border-top: 1.5px solid #1a1a2e; padding-top: 8px; font-size: 13.5px; }
        .totals tr.grand td.label { color: #1a1a2e; }
        .totals tr.due td { background-color: #1a1a2e; color: #fff; padding: 8px 10px; font-size: 12px; }
        .totals tr.due td.label { color: #fff; }

        .amount-words { background-color: #f0f3fa; border-left: 3px solid #122e6d; padding: 7px 11px; font-size: 10.5px; color: #444; }
        .amount-words strong { color: #1a1a2e; }

        .pay-box { background-color: #e8edf7; padding: 11px 13px; }
        .pay-title { font-size: 11px; font-weight: bold; color: #122e6d; margin-bottom: 5px; }
        .pay-line { font-size: 10.5px; line-height: 1.6; }
        .pay-line .l { display: inline-block; width: 110px; color: #666; }
        .pay-line .r { font-weight: bold; color: #1a1a2e; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-unpaid { background-color: #fff3cd; color: #856404; }
        .status-cancelled { background-color: #e0e0e0; color: #555; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }

        .stamp { transform: rotate(-12deg); border: 3px solid #16a34a; color: #16a34a; padding: 6px 16px; font-size: 22px; font-weight: bold; letter-spacing: 2px; display: inline-block; opacity: 0.85; }
        .stamp.unpaid { border-color: #b45309; color: #b45309; }
        .stamp.cancelled { border-color: #555; color: #555; }

        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #999; padding-top: 8px; border-top: 1px solid #eee; }
        .sig-cell { padding-top: 24px; border-top: 1px solid #999; font-size: 10px; color: #666; text-align: center; }

        .mb-12 { margin-bottom: 12px; }
        .mb-14 { margin-bottom: 14px; }
        .mt-12 { margin-top: 12px; }
        .mt-18 { margin-top: 18px; }
    </style>
</head>
<body>

@php
    $isPaid = $expense->isPaid();
    $isCancelled = $expense->status === 'cancelled';
    $isOverdue = $expense->isOverdue();

    $title = $isPaid ? 'Payment Receipt' : ($isCancelled ? 'Payment Voucher (Cancelled)' : 'Payment Voucher');
    $currency = $business->currency_symbol ?? '₹';

    $logoPath = $business->logo ? storage_path('app/public/'.$business->logo) : public_path('assets/images/logo.png');
    $logoExists = file_exists($logoPath);

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

{{-- HEADER --}}
<table class="layout mb-14">
    <tr>
        <td style="width: 65%;">
            <div class="doc-title">{{ $title }}</div>
            <div class="doc-sub">{{ $expense->isRecurring() ? 'Monthly Recurring Payment' : 'One-off Payment' }}</div>
            <div class="doc-meta">
                <div class="row"><span class="label">Voucher No #</span><span class="val">{{ $expense->expense_code }}</span></div>
                <div class="row"><span class="label">Bill Date</span><span class="val">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, Y') }}</span></div>
                @if($expense->due_date)
                    <div class="row"><span class="label">Due Date</span><span class="val">{{ \Carbon\Carbon::parse($expense->due_date)->format('d M, Y') }}</span></div>
                @endif
                @if($isPaid && $expense->paid_date)
                    <div class="row"><span class="label">Paid Date</span><span class="val">{{ \Carbon\Carbon::parse($expense->paid_date)->format('d M, Y') }}</span></div>
                @endif
                <div class="row">
                    <span class="label">Status</span>
                    <span class="status-badge status-{{ $isOverdue && ! $isPaid ? 'overdue' : $expense->status }}">
                        {{ strtoupper($isOverdue && ! $isPaid ? 'Overdue' : $expense->status) }}
                    </span>
                </div>
            </div>
        </td>
        <td style="width: 35%; text-align: right;">
            @if($logoExists)
                <img src="{{ $logoPath }}" class="logo-img" alt="{{ $business->name }}" />
            @else
                <div style="font-size: 16px; font-weight: bold; color: #122e6d;">{{ $business->name }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- BUSINESS / PAYMENT INFO --}}
<table class="layout mb-12">
    <tr>
        <td style="width: 49%;">
            <div class="party-box">
                <div class="party-title">From (Business)</div>
                <div class="party-name">{{ strtoupper($business->name) }}</div>
                <div class="party-line">
                    @if($business->address){!! nl2br(e($business->address)) !!}<br>@endif
                    @php
                        $loc = collect([$business->city, $business->state, $business->pincode])->filter()->implode(', ');
                    @endphp
                    @if($loc){{ $loc }}<br>@endif
                    @if($business->gst)<strong>GSTIN:</strong> {{ $business->gst }}<br>@endif
                    @if($business->pan)<strong>PAN:</strong> {{ $business->pan }}<br>@endif
                    @if($business->email)<strong>Email:</strong> {{ $business->email }}<br>@endif
                    @if($business->phone)<strong>Phone:</strong> {{ $business->phone }}@endif
                </div>
            </div>
        </td>
        <td class="gap-cell"></td>
        <td style="width: 49%;">
            <div class="party-box">
                <div class="party-title">Payment Category</div>
                <div class="party-name">{{ $expense->category->name ?? '—' }}</div>
                <div class="party-line">
                    @if($expense->subcategory)<strong>Subcategory:</strong> {{ $expense->subcategory->name }}<br>@endif
                    <strong>Type:</strong> {{ $expense->isRecurring() ? 'Monthly Recurring (day '.$expense->due_day_of_month.')' : 'One-off' }}<br>
                    @if($expense->creator)<strong>Recorded by:</strong> {{ $expense->creator->name }}<br>@endif
                    @if($isPaid && $expense->paidByAdmin)
                        <strong>Paid by:</strong> {{ $expense->paidByAdmin->name }}
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- LINE ITEM (single row representing the expense) --}}
<table class="items mb-12">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 60%;">Description</th>
            <th class="tr" style="width: 35%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>
                <strong>{{ $expense->title }}</strong>
                @if($expense->description)
                    <br><span style="color: #666; font-size: 10px;">{{ $expense->description }}</span>
                @endif
            </td>
            <td class="tr">{{ $currency }}{{ number_format($expense->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- TOTALS + PAYMENT INFO --}}
<table class="layout mb-12">
    <tr>
        <td style="width: 55%;">
            <div class="amount-words">
                <strong>Amount in words:</strong> {{ $toWords($expense->amount) }}
            </div>

            @if($isPaid)
                <div class="pay-box mt-12">
                    <div class="pay-title">Payment Information</div>
                    <div class="pay-line"><span class="l">Paid Date</span><span class="r">{{ \Carbon\Carbon::parse($expense->paid_date)->format('d M, Y') }}</span></div>
                    <div class="pay-line"><span class="l">Payment Method</span><span class="r">{{ $expense->payment_method ? ucfirst($expense->payment_method) : '—' }}</span></div>
                    @if($expense->payment_reference)
                        <div class="pay-line"><span class="l">Reference</span><span class="r">{{ $expense->payment_reference }}</span></div>
                    @endif
                    @if($expense->paidByAdmin)
                        <div class="pay-line"><span class="l">Paid By</span><span class="r">{{ $expense->paidByAdmin->name }}</span></div>
                    @endif
                </div>
            @endif
        </td>
        <td class="gap-cell"></td>
        <td style="width: 45%;">
            <table class="totals">
                <tr class="grand">
                    <td class="label">Total Amount</td>
                    <td class="val">{{ $currency }}{{ number_format($expense->amount, 2) }}</td>
                </tr>
                @if($isPaid)
                    <tr>
                        <td class="label">Amount Paid</td>
                        <td class="val" style="color: #16a34a;">{{ $currency }}{{ number_format($expense->amount, 2) }}</td>
                    </tr>
                    <tr class="due" style="background-color: #16a34a;">
                        <td class="label">Balance</td>
                        <td class="val">{{ $currency }}0.00</td>
                    </tr>
                @else
                    <tr class="due">
                        <td class="label">Balance Due</td>
                        <td class="val">{{ $currency }}{{ number_format($expense->amount, 2) }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- STAMP --}}
<table class="layout">
    <tr>
        <td style="width: 50%; text-align: center;">
            @if($isPaid)
                <div class="stamp">PAID</div>
            @elseif($isCancelled)
                <div class="stamp cancelled">CANCELLED</div>
            @elseif($isOverdue)
                <div class="stamp unpaid">OVERDUE</div>
            @else
                <div class="stamp unpaid">UNPAID</div>
            @endif
        </td>
        <td style="width: 50%;">
            <div class="sig-cell" style="margin-top: 30px; width: 60%; margin-left: auto;">
                Authorized Signatory
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    This is a computer-generated document. Generated on {{ now()->format('d M, Y H:i') }}.
</div>

</body>
</html>
