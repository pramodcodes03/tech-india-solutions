@php
    use App\Support\AmountInWords;
    $lastDay = $employee?->last_working_date ?? $employee?->relieving_date;

    // Payables: the last payslip's net, plus encashment of any leave left on
    // types marked encashable.
    $encashment = round($encashableDays * $perDayRate, 2);
    $lastNet = (float) ($lastPayslip?->net_pay ?? 0);
    $payable = $lastNet + $encashment;
@endphp
@extends('pdf.layout')

@section('title', 'Full & Final Settlement')
@section('doc-title', 'Full & Final Settlement')
@section('doc-sub', $employee?->full_name.' · '.$employee?->employee_code)

@section('doc-meta')
    <div class="row"><span class="label">Date</span> <span class="val">{{ now()->format('d M Y') }}</span></div>
    <div class="row"><span class="label">Last working day</span> <span class="val">{{ optional($lastDay)->format('d M Y') ?? '—' }}</span></div>
@endsection

@section('content')
    <table class="kv" style="margin-bottom:12px;">
        <tr>
            <td class="k">Employee</td><td class="v">{{ $employee?->full_name ?? '—' }}</td>
            <td class="k">Department</td><td class="v">{{ $employee?->department?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Employee ID</td><td class="v">{{ $employee?->employee_code ?? '—' }}</td>
            <td class="k">Designation</td><td class="v">{{ $employee?->designation?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Date of joining</td><td class="v">{{ optional($employee?->joining_date)->format('d M Y') ?? '—' }}</td>
            <td class="k">Date of leaving</td><td class="v">{{ optional($lastDay)->format('d M Y') ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead><tr><th>Particulars</th><th>Basis</th><th class="tr">Amount (₹)</th></tr></thead>
        <tbody>
            <tr>
                <td>Salary for the final month</td>
                <td class="muted">{{ $lastPayslip ? $lastPayslip->period_label : 'No payslip generated' }}</td>
                <td class="tr">{{ number_format($lastNet, 2) }}</td>
            </tr>
            <tr class="alt">
                <td>Leave encashment</td>
                <td class="muted">
                    {{ rtrim(rtrim(number_format($encashableDays, 1, '.', ''), '0'), '.') }} day(s)
                    × ₹{{ number_format($perDayRate, 2) }} per day
                </td>
                <td class="tr">{{ number_format($encashment, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr><td colspan="2">Total payable</td><td class="tr">{{ number_format($payable, 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="words mt">
        <span class="lbl">Net settlement in words:</span> <strong>{{ AmountInWords::currency($payable) }}</strong>
    </div>

    @if($balances->isNotEmpty())
        <div class="mt-lg">
            <div class="panel-title" style="margin-bottom:5px;">Leave balance at exit</div>
            <table class="data">
                <thead><tr><th>Leave type</th><th class="tr">Allocated</th><th class="tr">Used</th><th class="tr">Balance</th><th class="tc">Encashable</th></tr></thead>
                <tbody>
                    @foreach($balances as $i => $b)
                        <tr @class(['alt' => $i % 2 === 1])>
                            <td>{{ $b->leaveType?->name ?? '—' }}</td>
                            <td class="tr">{{ number_format((float) $b->allocated + (float) $b->carried_forward, 1) }}</td>
                            <td class="tr">{{ number_format((float) $b->used, 1) }}</td>
                            <td class="tr">{{ number_format($b->available, 1) }}</td>
                            <td class="tc">{{ $b->leaveType?->encashable ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="panel mt-lg">
        <div class="panel-title">Declaration</div>
        <div style="font-size:9.5px;">
            I confirm that I have received the full and final settlement of my dues as set out above, that I have
            returned all Company property in my possession, and that I have no further claim of any nature against
            {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }}.
        </div>
    </div>

    <table class="sign-wrap">
        <tr>
            <td class="sign-box">
                <div class="sign-img"></div>
                <div class="sign-line">
                    <div class="sign-name">{{ $employee?->full_name ?? 'Employee' }}</div>
                    <div class="sign-role">Employee · Date: ______________</div>
                </div>
            </td>
            <td></td>
            <td class="sign-box">
                <div class="sign-img">
                    @if($business?->signature_path && is_file(storage_path('app/public/'.$business->signature_path)))
                        <img src="{{ storage_path('app/public/'.$business->signature_path) }}" alt="" />
                    @endif
                </div>
                <div class="sign-line">
                    <div class="sign-name">{{ $business?->signatory_name ?: 'Authorised Signatory' }}</div>
                    <div class="sign-role">{{ $business?->signatory_role ?: 'For '.($business?->name ?: 'the Company') }}</div>
                </div>
            </td>
        </tr>
    </table>
@endsection

@section('signature')@endsection
