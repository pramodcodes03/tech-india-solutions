@php use App\Support\AmountInWords; @endphp
@extends('pdf.layout')
@section('title', 'Bank Transfer Advice — '.$period)
@section('doc-title', 'Bank Transfer Advice')
@section('doc-sub', 'Salary disbursement for '.$period)
@section('doc-meta')
    <div class="row"><span class="label">Beneficiaries</span> <span class="val">{{ $payslips->count() }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ now()->format('d M Y') }}</span></div>
@endsection

@section('content')
    <p style="font-size:10px; margin-bottom:10px;">
        To The Branch Manager,<br>
        Please debit our account and credit the following beneficiaries with the amounts stated against each,
        being salary payable for <strong>{{ $period }}</strong>.
    </p>

    <table class="data">
        <thead>
            <tr>
                <th class="tc">#</th><th>Emp ID</th><th>Beneficiary Name</th>
                <th>Bank</th><th>Account Number</th><th>IFSC</th><th class="tr">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payslips as $i => $p)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="tc">{{ $i + 1 }}</td>
                    <td>{{ $p->employee?->employee_code }}</td>
                    <td>{{ $p->employee?->bank_account_name ?: $p->employee?->full_name }}</td>
                    <td>{{ $p->employee?->bank_name ?: '—' }}</td>
                    <td>{{ $p->employee?->bank_account_number ?: '—' }}</td>
                    <td>{{ $p->employee?->bank_ifsc ?: '—' }}</td>
                    <td class="tr">{{ number_format((float) $p->net_pay, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No payroll generated for {{ $period }}.</td></tr>
            @endforelse
        </tbody>
        @if($payslips->isNotEmpty())
            <tfoot>
                <tr><td colspan="6">Total to be debited</td><td class="tr">{{ number_format($total, 2) }}</td></tr>
            </tfoot>
        @endif
    </table>

    <div class="words mt">
        <span class="lbl">Total in words:</span> <strong>{{ AmountInWords::currency($total) }}</strong>
    </div>

    @php
        $missing = $payslips->filter(fn ($p) => ! $p->employee?->bank_account_number || ! $p->employee?->bank_ifsc);
    @endphp
    @if($missing->isNotEmpty())
        {{-- Flagged rather than silently omitted: a bank file short of a few
             beneficiaries is worse than one that says who is missing. --}}
        <div class="panel mt" style="background:#fdeaea;">
            <div class="panel-title" style="color:#b91c1c;">{{ $missing->count() }} beneficiary(ies) have incomplete bank details</div>
            <div style="font-size:9px;">
                {{ $missing->map(fn ($p) => $p->employee?->full_name.' ('.$p->employee?->employee_code.')')->implode(', ') }}
            </div>
        </div>
    @endif
@endsection
