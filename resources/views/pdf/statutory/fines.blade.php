{{-- Form I · Register of Fines · The Punjab Minimum Wages Rules, 1950, Rule 21(4) --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form I')
@section('form-rule', '[See Rule 21 (4)]')
@section('form-title', 'Register of Fines')
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
                <th style="width:3%">S.No.</th>
                <th style="width:4%">Emp.ID</th>
                <th style="width:12%">Name</th>
                <th style="width:12%">Father's / Husband's Name</th>
                <th style="width:3%">Sex</th>
                <th style="width:9%">Department</th>
                <th style="width:13%">Nature and date of the offence for which fine imposed</th>
                <th style="width:12%">Whether workman showed cause against fine or not. if so enter</th>
                <th style="width:7%">Rate of wages</th>
                <th style="width:9%">Date and amount of fine imposed</th>
                <th style="width:8%">Date on which fine realised</th>
                <th style="width:8%">Remarks</th>
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
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['offence'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['showed_cause'] }}</td>
                    <td class="{{ $row['has_event'] ? 'r' : 'nil' }}">{{ $row['wage_rate'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['fine'] }}</td>
                    <td class="c">{{ $row['realised_on'] }}</td>
                    <td class="{{ $row['has_event'] ? '' : 'nil' }}">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
