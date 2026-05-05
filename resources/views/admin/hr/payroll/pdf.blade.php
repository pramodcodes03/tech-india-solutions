<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip {{ $payslip->payslip_code }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 20px; color: #0f172a; font-size: 11px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px; }
        .header > div { display: table-cell; vertical-align: top; }
        .header h1 { margin: 0; font-size: 20px; }
        .header .right { text-align: right; }
        .info-grid { display: table; width: 100%; margin-bottom: 15px; }
        .info-grid .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .info-grid .row { padding: 3px 0; }
        .info-grid .label { color: #64748b; width: 120px; display: inline-block; }
        .earn-deduct { display: table; width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .earn-deduct .col { display: table-cell; width: 50%; padding: 0 5px; vertical-align: top; }
        .earn-deduct table { width: 100%; border-collapse: collapse; }
        .earn-deduct th { text-align: left; padding: 6px 8px; font-size: 12px; }
        .earn-deduct td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; }
        .earn-deduct .num { text-align: right; }
        .earn-head-e { background: #ecfdf5; color: #047857; }
        .earn-head-d { background: #fef2f2; color: #b91c1c; }
        .total-row { background: #f8fafc; font-weight: bold; }
        .net-box { background: #1e40af; color: white; padding: 15px; border-radius: 6px; margin-top: 10px; }
        .net-box .row { display: table; width: 100%; }
        .net-box .lbl { display: table-cell; }
        .net-box .val { display: table-cell; text-align: right; font-size: 22px; font-weight: bold; }
        .footer { text-align: center; color: #94a3b8; font-size: 9px; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    @php $p = $payslip; $emp = $p->employee; @endphp

    <div class="header">
        <div>
            <h1>Pay Slip</h1>
            <div>{{ $p->period_label }} · {{ $p->payslip_code }}</div>
        </div>
        <div class="right">
            <div style="font-weight:bold">Tech India Solutions</div>
            <div style="color:#64748b;font-size:10px">Payroll Division</div>
            <div style="margin-top:5px;padding:2px 8px;display:inline-block;background:#e0e7ff;color:#3730a3;border-radius:4px;font-size:10px;">{{ strtoupper($p->status) }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="col">
            <div class="row"><span class="label">Name:</span> <strong>{{ $emp->full_name }}</strong></div>
            <div class="row"><span class="label">Code:</span> {{ $emp->employee_code }}</div>
            <div class="row"><span class="label">Department:</span> {{ $emp->department?->name ?? '—' }}</div>
            <div class="row"><span class="label">Designation:</span> {{ $emp->designation?->name ?? '—' }}</div>
            <div class="row"><span class="label">Period:</span> {{ $p->period_start->format('d M') }} – {{ $p->period_end->format('d M Y') }}</div>
        </div>
        <div class="col">
            <div class="row"><span class="label">PAN:</span> {{ $emp->pan_number ?? '—' }}</div>
            <div class="row"><span class="label">UAN:</span> {{ $emp->uan_number ?? '—' }}</div>
            <div class="row"><span class="label">Bank A/C:</span> {{ $emp->bank_account_number ? '****'.substr($emp->bank_account_number, -4) : '—' }}</div>
            <div class="row"><span class="label">Working Days:</span> {{ $p->working_days }}</div>
            <div class="row"><span class="label">Paid Days:</span> {{ number_format($p->paid_days, 1) }} ({{ number_format($p->lop_days, 1) }} LOP)</div>
        </div>
    </div>

    <div class="earn-deduct">
        <div class="col">
            <table>
                <tr class="earn-head-e"><th colspan="2">Earnings</th></tr>
                @foreach([['Basic', $p->basic],['HRA', $p->hra],['Conveyance', $p->conveyance],['Medical', $p->medical],['Special Allowance', $p->special],['Other Allowance', $p->other_allowance],['Bonus', $p->bonus]] as [$l, $v])
                    <tr><td>{{ $l }}</td><td class="num">₹{{ number_format($v, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Gross Earnings</td><td class="num">₹{{ number_format($p->gross_earnings, 2) }}</td></tr>
            </table>
        </div>
        <div class="col">
            <table>
                <tr class="earn-head-d"><th colspan="2">Deductions</th></tr>
                @foreach([['PF (Employee)', $p->pf],['ESI', $p->esi],['LWF / Professional Tax', $p->professional_tax],['TDS', $p->tds],['LOP Deduction', $p->lop_deduction],['Penalty Deduction', $p->penalty_deduction],['Other Deductions', $p->other_deductions]] as [$l, $v])
                    <tr><td>{{ $l }}</td><td class="num">₹{{ number_format($v, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total Deductions</td><td class="num">₹{{ number_format($p->total_deductions, 2) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="net-box">
        <div class="row">
            <div class="lbl">
                <div style="font-size:10px;opacity:.8;text-transform:uppercase">Net Pay</div>
                <div style="font-size:12px;opacity:.8;margin-top:4px">For {{ $p->period_label }}</div>
            </div>
            <div class="val">₹{{ number_format($p->net_pay, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        This is a system-generated pay slip. No signature required.
    </div>
</body>
</html>
