{{-- Form E · Register of Deductions · Punjab Shops and Commercial Establishments Rules, 1958, Rule 5 --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form E')
@section('form-title', 'Register of Deductions')
@section('form-act', '(Rule 5 of the Punjab Shops and Commercial Establishments Rules, 1958)')

@section('form-meta')
    <table class="meta">
        <tr>
            <td class="k">Month</td>
            <td style="width:120px">{{ $monthLabel }}</td>
            <td>Acts and omissions approved by the authorities.....</td>
        </tr>
    </table>
@endsection

@section('content')
    <table class="grid">
        <thead>
            <tr>
                <th style="width:3%">S.No.</th>
                <th style="width:4%">Emp.ID</th>
                <th style="width:9%">Name of the employee</th>
                <th style="width:9%">Parentage</th>
                <th style="width:6%">Wage Period</th>
                <th style="width:6%">Wages Payable</th>
                <th style="width:6%">Amount deduction made</th>
                <th style="width:9%">Fault for which deduction</th>
                <th style="width:6%">Date of deduction</th>
                <th style="width:8%">Whether employee showed cause against deduction</th>
                <th style="width:9%">Amount of deduction and purpose for which utilized</th>
                <th style="width:6%">Date of utilization</th>
                <th style="width:6%">Balance with the employer</th>
                <th style="width:5%">Signature of employer</th>
                <th style="width:5%">Signature of employee</th>
                <th style="width:5%">Remarks</th>
            </tr>
            <tr class="colno">
                <td>1</td><td>1A</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td>
                <td>8</td><td>9</td><td>10</td><td>11</td><td>12</td><td>13</td><td>14</td><td>15</td>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['sno'] }}</td>
                    <td class="c">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['guardian'] }}</td>
                    <td class="{{ $row['has_event'] ? 'c' : 'nil' }}">{{ $row['wage_period'] }}</td>
                    <td class="{{ $row['has_event'] ? 'r' : 'nil' }}">{{ $row['wages_payable'] }}</td>
                    <td class="{{ $row['has_event'] ? 'r' : 'nil' }}">{{ $row['amount'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['fault'] }}</td>
                    <td class="c">{{ $row['deduction_date'] }}</td>
                    <td class="c">{{ $row['showed_cause'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['purpose'] }}</td>
                    <td class="c">{{ $row['utilised_on'] }}</td>
                    <td class="{{ $row['has_event'] ? 'r' : 'nil' }}">{{ $row['balance'] }}</td>
                    <td></td>
                    <td></td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="16" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
