{{-- Form II-A · Register of Advances of Employed persons · The Punjab Minimum Wages Rules, 1950 --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form II - A')
@section('form-title', 'Register of Advances of Employed persons')
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
                <th style="width:3.5%">S.No.</th>
                <th style="width:4.5%">Emp.ID</th>
                <th style="width:13%">Name</th>
                <th style="width:13%">Father's Name</th>
                <th style="width:10%">Department</th>
                <th style="width:11%">Date and amount of advance made</th>
                <th style="width:12%">Purpose(s) for which advance made</th>
                <th style="width:9%">Number of instalments by which advance to be repaid</th>
                <th style="width:8%">Postponement grounds</th>
                <th style="width:8%">Date on which amount repaid</th>
                <th style="width:8%">Remarks</th>
            </tr>
            <tr class="colno">
                <td>1</td><td>1A</td><td>2</td><td>3</td><td>4</td>
                <td>5</td><td>6</td><td>7</td><td>8</td><td>9</td><td>10</td>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="c">{{ $row['sno'] }}</td>
                    <td class="c">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['guardian'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['advance'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['purpose'] }}</td>
                    <td class="c">{{ $row['instalments'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['postponement'] }}</td>
                    <td class="c">{{ $row['repaid_on'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
