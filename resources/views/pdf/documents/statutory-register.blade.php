@extends('pdf.layout')
@section('title', 'Statutory Register — '.$period)
@section('doc-title', 'PF / ESI / PT Register')
@section('doc-sub', $period)
@section('doc-meta')
    <div class="row"><span class="label">Employees</span> <span class="val">{{ $payslips->count() }}</span></div>
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
@endsection

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th>Emp ID</th><th>Employee</th><th>Department</th>
                <th>UAN</th><th>ESI No.</th>
                <th class="tr">Gross (₹)</th><th class="tr">PF (₹)</th><th class="tr">ESI (₹)</th><th class="tr">PT (₹)</th>
                <th class="tr">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $i => $p)
                @php $statutory = (float) $p->pf + (float) $p->esi + (float) $p->professional_tax; @endphp
                <tr @class(['alt' => $i % 2 === 1])>
                    <td>{{ $p->employee?->employee_code }}</td>
                    <td>{{ $p->employee?->full_name }}</td>
                    <td>{{ $p->employee?->department?->name ?? '—' }}</td>
                    <td>{{ $p->employee?->uan_number ?: '—' }}</td>
                    <td>{{ $p->employee?->esi_number ?: '—' }}</td>
                    <td class="tr">{{ number_format((float) $p->gross_earnings, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->pf, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->esi, 2) }}</td>
                    <td class="tr">{{ number_format((float) $p->professional_tax, 2) }}</td>
                    <td class="tr">{{ number_format($statutory, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">No payroll generated for {{ $period }}.</td></tr>
            @endforelse
        </tbody>
        @if($payslips->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5">Total</td>
                    <td class="tr">{{ number_format((float) $payslips->sum('gross_earnings'), 2) }}</td>
                    <td class="tr">{{ number_format((float) $payslips->sum('pf'), 2) }}</td>
                    <td class="tr">{{ number_format((float) $payslips->sum('esi'), 2) }}</td>
                    <td class="tr">{{ number_format((float) $payslips->sum('professional_tax'), 2) }}</td>
                    <td class="tr">{{ number_format((float) ($payslips->sum('pf') + $payslips->sum('esi') + $payslips->sum('professional_tax')), 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

@section('signature')@endsection
