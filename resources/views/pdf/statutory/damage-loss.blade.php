{{-- Form II · Register of deductions for damage or loss · The Punjab Minimum Wages Rules, 1950, Rule 21(4) --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form II')
@section('form-rule', '[Rule 21(4)]')
@section('form-title', 'Register of deduction for damage or loss caused to the employer by the neglect or default of the employed persons')
@section('form-act', 'The Punjab Minimum Wages Rules, 1950')

@section('form-meta')
    <table class="meta">
        <tr><td class="k">Month</td><td>{{ $monthLabel }}</td></tr>
    </table>
@endsection

@section('content')
    <table class="grid">
        <thead>
            <tr>
                <th style="width:3%">S.No</th>
                <th style="width:4%">Emp.ID</th>
                <th style="width:11%">Name</th>
                <th style="width:11%">Father's / Husband's Name</th>
                <th style="width:3%">Sex</th>
                <th style="width:8%">Department</th>
                <th style="width:12%">Damage or loss caused with date</th>
                <th style="width:14%">Whether worker showed cause against deduction. If so enter date &amp; particulars of the person in whose presence the cause was shown</th>
                <th style="width:10%">Date and amount of deduction imposed</th>
                <th style="width:7%">Number of instalments if any</th>
                <th style="width:8%">Date on which total amount realized</th>
                <th style="width:7%">Remarks</th>
            </tr>
            <tr class="colno">
                <td>1</td><td>1A</td><td>2</td><td>3</td><td>4</td><td>5</td>
                <td>6</td><td>7</td><td>8</td><td>9</td><td>10</td><td>11</td>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['sno'] }}</td>
                    <td class="c">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['guardian'] }}</td>
                    <td class="c">{{ $row['sex'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['damage'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['showed_cause'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['deduction'] }}</td>
                    <td class="c">{{ $row['instalments'] }}</td>
                    <td class="c">{{ $row['realised_on'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
