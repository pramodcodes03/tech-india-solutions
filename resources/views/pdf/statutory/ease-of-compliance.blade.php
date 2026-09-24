{{-- FORM B · Format for Wage Register · The Ease of Compliance to Maintain Registers under various Labour Laws Rules, 2017, Rule 3 --}}
@extends('pdf.statutory.layout')

@section('form-no', 'FORM B')
@section('form-rule', '[See rule 3]')
@section('form-title', 'FORMAT FOR WAGE REGISTER')
@section('form-act', 'The Ease of Compliance to Maintain Registers under various Labour Laws Rules, 2017')

@section('form-meta')
    <table class="meta">
        <tr>
            <td class="k" style="width:auto">Name of the Establishment</td>
            <td>{{ $establishment['name'] }}, {{ $establishment['address'] }}</td>
            <td class="k" style="width:26px">LIN</td>
            <td style="width:70px">{{ $establishment['lin'] }}</td>
        </tr>
        <tr>
            <td class="k">Name of Owner</td>
            <td>{{ collect([$establishment['employer_name'], $establishment['employer_designation']])->filter()->implode(', ') }}</td>
            <td colspan="2">Wage Period : {{ $establishment['wage_period'] }}
                &nbsp; Month : {{ $monthName }} &nbsp; Year : {{ $year }}</td>
        </tr>
    </table>
@endsection

@push('styles')
    <style>
        table.rates { width: 46%; border-collapse: collapse; margin-top: 4px; font-size: 6.4px; }
        table.rates th, table.rates td { border: 0.6px solid #000; padding: 1.5px 3px; text-align: center; }
        table.rates td.k { text-align: left; font-weight: bold; width: 22%; }
        .rates-title { font-size: 7px; font-weight: bold; margin-top: 4px; }
    </style>
@endpush

@section('content')
    {{-- The notified minimum wage as it stood in the month being printed. --}}
    <div class="rates-title">
        Rate of Minimum Wages and since the date
        {{ $rateDate?->format('d M Y') ?: '—' }}
    </div>
    <table class="rates">
        <tr>
            <td class="k"></td>
            @foreach($categories as $slug => $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
        <tr>
            <td class="k">Minimum Basic</td>
            @foreach($categories as $slug => $label)
                <td>{{ isset($wageRates[$slug]) ? rtrim(rtrim(number_format((float) $wageRates[$slug]->basic, 2, '.', ''), '0'), '.') : '—' }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="k">DA</td>
            @foreach($categories as $slug => $label)
                <td>{{ isset($wageRates[$slug]) && (float) $wageRates[$slug]->da > 0
                        ? rtrim(rtrim(number_format((float) $wageRates[$slug]->da, 2, '.', ''), '0'), '.') : '' }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="k">Overtime</td>
            @foreach($categories as $slug => $label)
                <td>{{ $wageRates[$slug]->overtime_note ?? 'Double the wages' }}</td>
            @endforeach
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" style="width:3.4%">Sl. No. in Employee register</th>
                <th rowspan="2" style="width:7%">Name</th>
                <th rowspan="2" style="width:3.6%">Rate of Wage</th>
                <th rowspan="2" style="width:2.8%">No. of Days worked</th>
                <th rowspan="2" style="width:2.8%">Overtime hours worked</th>
                <th colspan="9">Payments</th>
                <th colspan="9">Deductions</th>
                <th rowspan="2" style="width:3.6%">Net Payment</th>
                <th rowspan="2" style="width:3.2%">Employer Share PF</th>
                <th rowspan="2" style="width:4.4%">Receipt by Employee / Bank Transaction ID</th>
                <th rowspan="2" style="width:3.6%">Date of Payment</th>
                <th rowspan="2" style="width:2.6%">Remarks</th>
            </tr>
            <tr>
                <th style="width:3.2%">Basic</th>
                <th style="width:2.6%">Special Basic</th>
                <th style="width:2.4%">DA</th>
                <th style="width:2.6%">Overtime</th>
                <th style="width:3%">HRA</th>
                <th style="width:2.8%">Conveyance</th>
                <th style="width:2.8%">Special Allowance</th>
                <th style="width:3%">Others</th>
                <th style="width:3.4%">Total</th>
                <th style="width:2.6%">PF</th>
                <th style="width:2.4%">ESIC</th>
                <th style="width:2.6%">Society</th>
                <th style="width:2.6%">Income Tax</th>
                <th style="width:2.4%">P Tax</th>
                <th style="width:2.4%">Insurance</th>
                <th style="width:2.6%">Welfare Fund</th>
                <th style="width:2.8%">Others Recoveries</th>
                <th style="width:2.8%">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="r">{{ number_format($row['rate_of_wage'], 1) }}</td>
                    <td class="r">{{ number_format($row['days_worked'], 1) }}</td>
                    <td class="r">{{ number_format($row['overtime_hours'], 1) }}</td>
                    <td class="r">{{ number_format($row['basic'], 2) }}</td>
                    <td class="c">{{ $row['special_basic'] }}</td>
                    <td class="r">{{ number_format($row['da'], 2) }}</td>
                    <td class="r">{{ number_format($row['overtime'], 2) }}</td>
                    <td class="r">{{ number_format($row['hra'], 2) }}</td>
                    <td class="r">{{ number_format($row['conveyance'], 2) }}</td>
                    <td class="r">{{ number_format($row['special_allowance'], 2) }}</td>
                    <td class="r">{{ number_format($row['others'], 2) }}</td>
                    <td class="r">{{ number_format($row['total'], 2) }}</td>
                    <td class="r">{{ number_format($row['pf'], 2) }}</td>
                    <td class="r">{{ number_format($row['esic'], 2) }}</td>
                    <td class="c">{{ $row['society'] }}</td>
                    <td class="r">{{ number_format($row['income_tax'], 2) }}</td>
                    <td class="r">{{ number_format($row['p_tax'], 2) }}</td>
                    <td class="r">{{ number_format($row['insurance'], 2) }}</td>
                    <td class="r">{{ number_format($row['lwf'], 2) }}</td>
                    <td class="r">{{ number_format($row['other_recoveries'], 2) }}</td>
                    <td class="r">{{ number_format($row['total_deductions'], 2) }}</td>
                    <td class="r">{{ number_format($row['net'], 2) }}</td>
                    <td class="r">{{ number_format($row['employer_pf'], 2) }}</td>
                    <td class="c">{{ $row['receipt'] }}</td>
                    <td class="c">{{ $row['paid_on'] }}</td>
                    <td class="c">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="28" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
