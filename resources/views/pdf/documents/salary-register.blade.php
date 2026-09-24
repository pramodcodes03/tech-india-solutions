@extends('pdf.layout')
@section('title', 'Salary Register — '.$period)
@section('doc-title', 'Salary Register')
@section('doc-sub', $period)
@section('doc-meta')
    <div class="row"><span class="label">Employees</span> <span class="val">{{ $payslips->count() }}</span></div>
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th>Emp ID</th><th>Employee</th><th>Department</th><th class="tr">Paid Days</th>
                <th class="tr">Basic</th><th class="tr">HRA</th><th class="tr">Other</th><th class="tr">Gross</th>
                <th class="tr">PF</th><th class="tr">ESI</th><th class="tr">PT</th><th class="tr">TDS</th>
                <th class="tr">Other Ded.</th><th class="tr">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $i => $p)
                @php
                    $other = (float) $p->conveyance + (float) $p->medical + (float) $p->special
                           + (float) $p->other_allowance + (float) $p->bonus;
                    $otherDed = (float) $p->penalty_deduction + (float) $p->lop_deduction + (float) $p->other_deductions;
                @endphp
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $p->employee?->employee_code }}</td>
                    <td>{{ $p->employee?->full_name }}</td>
                    <td>{{ $p->employee?->department?->name ?? '—' }}</td>
                    <td class="tr">{{ number_format((float) $p->paid_days, 1) }}</td>
                    <td class="tr">{{ number_format((float) $p->basic, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->hra, 2) }}</td>
                    <td class="tr">{{ number_format($other, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->gross_earnings, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->pf, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->esi, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->professional_tax, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->tds, 2) }}</td>
                    <td class="tr">{{ number_format($otherDed, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->net_pay, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="14" class="empty">No payroll generated for {{ $period }}.</td></tr>
            @endforelse
        </tbody>
        @if($payslips->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="7">Total ({{ $payslips->count() }} employees)</td>
                    <td class="tr">{{ number_format($totals['gross'], 2) }}</td>
                    <td colspan="5" class="tr">Deductions {{ number_format($totals['deductions'], 2) }}</td>
                    <td class="tr">{{ number_format($totals['net'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

@section('signature')@endsection
