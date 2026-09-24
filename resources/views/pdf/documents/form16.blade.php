@php
    use App\Support\AmountInWords;
    // Chapter VI-A deductions the payroll engine actually tracks. Anything the
    // employee declares separately (80C investments, HRA proof) is outside the
    // payroll system, so the form states that rather than implying zero.
    $chapterVia = $totals['pf'];
    $grossAfterPt = $totals['gross'] - $totals['pt'];
    $taxableEstimate = max(0, $grossAfterPt - $chapterVia - 50000);
@endphp
@extends('pdf.layout')

@section('title', 'Form 16 — '.($employee?->employee_code ?? ''))
@section('doc-title', 'Form 16')
@section('doc-sub', 'Certificate of tax deducted at source on salary · Financial Year '.$financialYear)

@section('doc-meta')
    <div class="row"><span class="label">Employee</span> <span class="val">{{ $employee?->employee_code ?? '—' }}</span></div>
    <div class="row"><span class="label">Period</span> <span class="val">{{ $periodStart->format('d M Y') }} — {{ $periodEnd->format('d M Y') }}</span></div>
@endsection

@section('content')
    {{-- ── Part A: the parties ── --}}
    <div class="panel-title">Part A — Employer and employee</div>
    <table class="data" style="margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:50%;">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Deductor (employer)</div>
                    <div style="font-weight:bold; margin-top:2px;">{{ $business?->legal_name ?: ($business?->name ?: '—') }}</div>
                    <div class="muted">{{ collect([$business?->address, $business?->city, $business?->state, $business?->pincode])->filter()->implode(', ') }}</div>
                    <div style="margin-top:3px;">PAN: <strong>{{ $business?->pan ?: '—' }}</strong></div>
                </td>
                <td>
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Deductee (employee)</div>
                    <div style="font-weight:bold; margin-top:2px;">{{ $employee?->full_name ?? '—' }}</div>
                    <div class="muted">{{ $employee?->designation?->name ?? '' }}{{ $employee?->department?->name ? ' · '.$employee->department->name : '' }}</div>
                    <div style="margin-top:3px;">PAN: <strong>{{ $employee?->pan_number ?: '—' }}</strong></div>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ── Part A: quarterly TDS summary ── --}}
    <div class="panel-title">Summary of tax deducted at source</div>
    <table class="data" style="margin-bottom:12px;">
        <thead><tr><th>Month</th><th class="tr">Gross salary (₹)</th><th class="tr">Prof. tax (₹)</th><th class="tr">PF (₹)</th><th class="tr">TDS deducted (₹)</th></tr></thead>
        <tbody>
            @forelse($payslips as $i => $p)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $p->period_label }}</td>
                    <td class="tr">{{ number_format((float) $p->gross_earnings, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->professional_tax, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->pf, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->tds, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No payslips in this financial year.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="tr">{{ number_format($totals['gross'], 2) }}</td>
                <td class="tr">{{ number_format($totals['pt'], 2) }}</td>
                <td class="tr">{{ number_format($totals['pf'], 2) }}</td>
                <td class="tr">{{ number_format($totals['tds'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── Part B: computation ── --}}
    <div class="panel-title">Part B — Computation of income and tax</div>
    <table class="data">
        <tbody>
            <tr><td>1. Gross salary paid</td><td class="tr" style="width:150px;">{{ number_format($totals['gross'], 2) }}</td></tr>
            <tr class="alt"><td>2. Less: Tax on employment (professional tax)</td><td class="tr">{{ number_format($totals['pt'], 2) }}</td></tr>
            <tr><td>3. Balance (1 − 2)</td><td class="tr">{{ number_format($grossAfterPt, 2) }}</td></tr>
            <tr class="alt"><td>4. Less: Standard deduction under section 16(ia)</td><td class="tr">{{ number_format(50000, 2) }}</td></tr>
            <tr><td>5. Less: Deduction under Chapter VI-A — employee provident fund</td><td class="tr">{{ number_format($chapterVia, 2) }}</td></tr>
            <tr class="alt"><td><strong>6. Estimated taxable income (3 − 4 − 5)</strong></td><td class="tr"><strong>{{ number_format($taxableEstimate, 2) }}</strong></td></tr>
            <tr><td>7. Tax deducted at source during the year</td><td class="tr">{{ number_format($totals['tds'], 2) }}</td></tr>
        </tbody>
    </table>

    <div class="words mt">
        <span class="lbl">Total tax deducted:</span> <strong>{{ AmountInWords::currency($totals['tds']) }}</strong>
    </div>

    <div class="panel mt">
        <div class="panel-title">Note</div>
        <div style="font-size:9px;">
            This certificate is generated from the payroll records of the deductor and reflects only the salary paid
            and tax deducted by this employer. Investments and exemptions declared by the employee directly to the
            income-tax authorities are not included in the computation above, and the taxable income shown is an
            estimate on that basis.
        </div>
    </div>

    <div class="mt" style="font-size:9.5px;">
        I certify that the information given above is true and correct, and is based on the books of account,
        documents and other records maintained by the deductor.
    </div>
@endsection
