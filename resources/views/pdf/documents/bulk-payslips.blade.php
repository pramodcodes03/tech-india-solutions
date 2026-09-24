@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Payslips — '.$period)
@section('doc-title', 'Payslips')
@section('doc-sub', $period.' · '.$payslips->count().' employee(s)')
@section('doc-meta')
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    @forelse($payslips as $index => $p)
        {{-- One payslip per page. The first follows the heading; each subsequent
             one starts a fresh page so a slip is never split in half. --}}
        <div @if($index > 0) style="page-break-before: always;" @endif>
            <div class="panel" style="margin-bottom:9px;">
                <table class="kv">
                    <tr>
                        <td class="k">Employee</td><td class="v">{{ $p->employee?->full_name }}</td>
                        <td class="k">Employee ID</td><td class="v">{{ $p->employee?->employee_code }}</td>
                    </tr>
                    <tr>
                        <td class="k">Department</td><td class="v">{{ $p->employee?->department?->name ?? '—' }}</td>
                        <td class="k">Designation</td><td class="v">{{ $p->employee?->designation?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Pay period</td><td class="v">{{ $p->period_label }}</td>
                        <td class="k">Payslip no.</td><td class="v">{{ $p->payslip_code }}</td>
                    </tr>
                    <tr>
                        <td class="k">Paid days</td><td class="v">{{ number_format((float) $p->paid_days, 1) }} of {{ $p->working_days }}</td>
                        <td class="k">LOP days</td><td class="v">{{ number_format((float) $p->lop_days, 1) }}</td>
                    </tr>
                </table>
            </div>

            <table class="data">
                <thead><tr><th>Earnings</th><th class="tr">Amount (₹)</th><th>Deductions</th><th class="tr">Amount (₹)</th></tr></thead>
                <tbody>
                    @php
                        $earnings = array_filter([
                            'Basic' => (float) $p->basic,
                            'House Rent Allowance' => (float) $p->hra,
                            'Conveyance' => (float) $p->conveyance,
                            'Medical' => (float) $p->medical,
                            'Special Allowance' => (float) $p->special,
                            'Other Allowance' => (float) $p->other_allowance,
                            'Bonus / Incentive' => (float) $p->bonus,
                        ], fn ($v) => $v > 0);
                        $deductions = array_filter([
                            'Provident Fund' => (float) $p->pf,
                            'ESI' => (float) $p->esi,
                            'Professional Tax' => (float) $p->professional_tax,
                            'TDS' => (float) $p->tds,
                            'Penalty' => (float) $p->penalty_deduction,
                            'Loss of Pay' => (float) $p->lop_deduction,
                            'Other Deductions' => (float) $p->other_deductions,
                        ], fn ($v) => $v > 0);
                        $rows = max(count($earnings), count($deductions));
                        $eKeys = array_keys($earnings);
                        $dKeys = array_keys($deductions);
                    @endphp
                    @for($r = 0; $r < $rows; $r++)
                        <tr @class(['alt' => $r % 2 === 1])>
                            <td>{{ $eKeys[$r] ?? '' }}</td>
                            <td class="tr">{{ isset($eKeys[$r]) ? number_format($earnings[$eKeys[$r]], 2) : '' }}</td>
                            <td>{{ $dKeys[$r] ?? '' }}</td>
                            <td class="tr">{{ isset($dKeys[$r]) ? number_format($deductions[$dKeys[$r]], 2) : '' }}</td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr>
                        <td>Gross Earnings</td><td class="tr">{{ number_format((float) $p->gross_earnings, 2) }}</td>
                        <td>Total Deductions</td><td class="tr">{{ number_format((float) $p->total_deductions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="data mt">
                <tr>
                    <td style="background:#0f2b5b; color:#fff; font-weight:bold; padding:8px 10px;">Net Pay</td>
                    <td style="background:#0f2b5b; color:#fff; font-weight:bold; padding:8px 10px; text-align:right; font-size:13px;">
                        ₹{{ number_format((float) $p->net_pay, 2) }}
                    </td>
                </tr>
            </table>

            <div class="words mt">
                <span class="lbl">Net pay in words:</span> <strong>{{ AmountInWords::currency($p->net_pay) }}</strong>
            </div>

            <div class="muted mt" style="font-size:8px;">
                This is a computer-generated payslip and does not require a signature.
            </div>
        </div>
    @empty
        <div class="empty">No payroll generated for {{ $period }}.</div>
    @endforelse
@endsection

@section('signature')@endsection
