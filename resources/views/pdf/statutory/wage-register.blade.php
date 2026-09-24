{{-- Form D · Register of wages of Employees · Punjab Shops & Commercial Establishments Rules, 1958, Rule 5 --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form D')
@section('form-title', 'Register of wages of Employees')
@section('form-act', '(Rule '.($establishment['rule'] ?? '5').' of the Punjab Shops and Commercial Establishments Rules, 1958)')

@section('form-meta')
    <table class="meta">
        <tr>
            <td class="k">Wage period :</td>
            <td style="width:90px">{{ $establishment['wage_period'] }}</td>
            <td class="k" style="width:50px">Month :</td>
            <td>{{ $periodLabel }}</td>
        </tr>
    </table>
@endsection

@push('styles')
    <style>
        .w-sno { width: 2.2% } .w-code { width: 3.2% } .w-name { width: 8% }
        .w-guardian { width: 8% } .w-money { width: 4.3% } .w-sig { width: 5% }
        .w-rem { width: 5% }
    </style>
@endpush

@section('content')
    <table class="grid">
        <thead>
            <tr>
                <th class="w-sno" rowspan="3">S.No</th>
                <th class="w-code" rowspan="3">Emp Code</th>
                <th class="w-name" rowspan="3">Name of the employee</th>
                <th class="w-guardian" rowspan="3">Father's name or Husband's name</th>
                <th class="w-money" rowspan="3">Wage fixed</th>
                <th class="w-money" rowspan="3">Arrear from last month</th>
                <th colspan="5">Wages earned during the month</th>
                <th class="w-money" rowspan="3">Gross Wages</th>
                <th class="w-money" rowspan="3">Deductions as shown in Register E</th>
                <th colspan="3">Deductions</th>
                <th class="w-money" rowspan="3">Other Deductions</th>
                <th class="w-money" rowspan="3">Total Deduction</th>
                <th class="w-money" rowspan="3">Advance Made on (Date)</th>
                <th class="w-money" rowspan="3">Payment Made (Net Pay)</th>
                <th class="w-sig" rowspan="3">Signature of Employee</th>
                <th class="w-sig" rowspan="3">Signature of Employer</th>
                <th class="w-rem" rowspan="3">Remarks</th>
            </tr>
            <tr>
                <th colspan="3">Ordinary</th>
                <th class="w-money" rowspan="2">Other Allowances</th>
                <th class="w-money" rowspan="2">Over Time</th>
                <th class="w-money" rowspan="2">EPF</th>
                <th class="w-money" rowspan="2">ESI</th>
                <th class="w-money" rowspan="2">LWF</th>
            </tr>
            <tr>
                <th class="w-money">Basic</th>
                <th class="w-money">HRA</th>
                <th class="w-money">Conveyance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['sno'] }}</td>
                    <td class="c">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['guardian'] }}</td>
                    <td class="r">{{ number_format($row['wage_fixed'], 2) }}</td>
                    <td class="r">{{ number_format($row['arrears'], 2) }}</td>
                    <td class="r">{{ number_format($row['basic'], 2) }}</td>
                    <td class="r">{{ number_format($row['hra'], 2) }}</td>
                    <td class="r">{{ number_format($row['conveyance'], 2) }}</td>
                    <td class="r">{{ number_format($row['other'], 2) }}</td>
                    <td class="r">{{ number_format($row['overtime'], 2) }}</td>
                    <td class="r">{{ number_format($row['gross'], 2) }}</td>
                    <td class="c">{{ $row['register_e'] }}</td>
                    <td class="r">{{ number_format($row['pf'], 2) }}</td>
                    <td class="r">{{ number_format($row['esi'], 2) }}</td>
                    <td class="r">{{ number_format($row['lwf'], 2) }}</td>
                    <td class="r">{{ number_format($row['other_deductions'], 2) }}</td>
                    <td class="r">{{ number_format($row['total_deductions'], 2) }}</td>
                    <td class="c">{{ $row['advance'] }}</td>
                    <td class="r">{{ number_format($row['net'], 2) }}</td>
                    <td></td>
                    <td></td>
                    <td class="c">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="23" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
        @if($rows)
            <tfoot>
                <tr>
                    <td colspan="4" class="r">Total</td>
                    <td class="r">{{ number_format($totals['gross'], 2) }}</td>
                    <td class="r">{{ number_format($totals['arrears'], 2) }}</td>
                    <td class="r">{{ number_format($totals['basic'], 2) }}</td>
                    <td class="r">{{ number_format($totals['hra'], 2) }}</td>
                    <td class="r">{{ number_format($totals['conveyance'], 2) }}</td>
                    <td class="r">{{ number_format($totals['other'], 2) }}</td>
                    <td class="r">{{ number_format($totals['overtime'], 2) }}</td>
                    <td class="r">{{ number_format($totals['gross'], 2) }}</td>
                    <td></td>
                    <td class="r">{{ number_format($totals['pf'], 2) }}</td>
                    <td class="r">{{ number_format($totals['esi'], 2) }}</td>
                    <td class="r">{{ number_format($totals['lwf'], 2) }}</td>
                    <td class="r">{{ number_format($totals['other_deductions'], 2) }}</td>
                    <td class="r">{{ number_format($totals['total_deductions'], 2) }}</td>
                    <td></td>
                    <td class="r">{{ number_format($totals['net'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
