{{-- FORM A · Register of Wages · The Punjab Labour Welfare Fund Act, 1965 & Rules, 1966, Rule 22 --}}
@extends('pdf.statutory.layout')

@section('form-no', 'FORM A')
@section('form-rule', '(See Rule 22)')
@section('form-title', 'Register of Wages')
@section('form-act', 'The Punjab Labour Welfare Fund Act, 1965 & Rules, 1966')

@section('form-meta')
    <table class="meta">
        <tr>
            <td class="k">For the month of</td>
            <td>{{ $monthLabel }}</td>
        </tr>
    </table>
@endsection

@section('content')
    <table class="grid">
        <thead>
            <tr>
                <th rowspan="3" style="width:2.4%">S.No.</th>
                <th rowspan="3" style="width:8%">Name of the Employee</th>
                <th rowspan="3" style="width:3.4%">Ticket no. and badge no.</th>
                <th rowspan="3" style="width:9%">Occupation</th>
                <th colspan="5">Amount payable during the month</th>
                <th colspan="3">Amount deducted during the month</th>
                <th colspan="5">Amount actually paid during the month</th>
                <th colspan="4">Balance due to the employee</th>
            </tr>
            <tr>
                <th rowspan="2" style="width:4.4%">Basic wages</th>
                <th rowspan="2" style="width:3.4%">Over Time</th>
                <th rowspan="2" style="width:4.6%">D.A and other allowances</th>
                <th rowspan="2" style="width:3.4%">Bonus</th>
                <th rowspan="2" style="width:4.6%">Total Amount payable</th>
                <th rowspan="2" style="width:3.4%">Fines</th>
                <th rowspan="2" style="width:3.6%">Deductions</th>
                <th rowspan="2" style="width:4.4%">Total Amount deducted</th>
                <th rowspan="2" style="width:4.4%">Basic wages</th>
                <th rowspan="2" style="width:3.4%">Over Time</th>
                <th rowspan="2" style="width:4.6%">D.A. and other allowances</th>
                <th rowspan="2" style="width:3.4%">Bonus</th>
                <th rowspan="2" style="width:4.4%">Total Amount paid</th>
                <th rowspan="2" style="width:3.4%">Basic wages</th>
                <th rowspan="2" style="width:3.2%">Over Time</th>
                <th rowspan="2" style="width:3.2%">Bonus</th>
                <th rowspan="2" style="width:4.4%">D.A. and other allowances</th>
            </tr>
            <tr></tr>
            <tr class="colno">
                @for($i = 1; $i <= 21; $i++)<td>{{ $i }}</td>@endfor
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['sno'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="c">{{ $row['ticket'] }}</td>
                    <td>{{ $row['occupation'] }}</td>
                    <td class="r">{{ number_format($row['basic'], 2) }}</td>
                    <td class="r">{{ number_format($row['overtime'], 2) }}</td>
                    <td class="r">{{ number_format($row['allowances'], 2) }}</td>
                    <td class="r">{{ number_format($row['bonus'], 2) }}</td>
                    <td class="r">{{ number_format($row['total_payable'], 2) }}</td>
                    <td class="c">{{ $row['fines'] }}</td>
                    <td class="r">{{ number_format($row['deductions'], 2) }}</td>
                    <td class="r">{{ number_format($row['total_deducted'], 2) }}</td>
                    <td class="r">{{ number_format($row['paid_basic'], 2) }}</td>
                    <td class="r">{{ number_format($row['paid_overtime'], 2) }}</td>
                    <td class="r">{{ number_format($row['paid_allowances'], 2) }}</td>
                    <td class="r">{{ number_format($row['paid_bonus'], 2) }}</td>
                    <td class="r">{{ number_format($row['total_paid'], 2) }}</td>
                    <td class="c">{{ $row['balance_basic'] }}</td>
                    <td class="c">{{ $row['balance_overtime'] }}</td>
                    <td class="c">{{ $row['balance_bonus'] }}</td>
                    <td class="c">{{ $row['balance_allowances'] }}</td>
                </tr>
            @empty
                <tr><td colspan="21" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
