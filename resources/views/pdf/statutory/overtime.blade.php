{{-- Form IV · Overtime Register for workers · The Punjab Minimum Wages Rules, 1950, Rule 25(3) --}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form IV')
@section('form-rule', '[Rule 25 (3)]')
@section('form-title', 'Overtime Register for workers')
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
                <th style="width:9%">Name</th>
                <th style="width:9%">Father's / Husband's Name</th>
                <th style="width:3%">Sex</th>
                <th style="width:10%">Designation &amp; Department</th>
                <th style="width:8%">Dates on which over-time worked</th>
                <th style="width:7%">Extent of over-time on each occasion</th>
                <th style="width:8%">Total over-time worked or Production in case of piece workers</th>
                <th style="width:5%">Normal hours</th>
                <th style="width:5%">Normal rate</th>
                <th style="width:5%">Over time rate</th>
                <th style="width:6%">Normal earnings</th>
                <th style="width:6%">Overtime earnings</th>
                <th style="width:6%">Total earnings</th>
                <th style="width:6%">Dates on which over time payments Made</th>
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
                    <td class="c">{{ $row['sex'] }}</td>
                    <td>{{ $row['designation'] }}</td>
                    <td class="{{ $row['has_records'] ? '' : 'nil' }}">{{ $row['dates'] }}</td>
                    <td class="{{ $row['has_records'] ? '' : 'nil' }}">{{ $row['extent'] }}</td>
                    <td class="{{ $row['has_records'] ? 'c' : 'nil' }}">{{ $row['total_hours'] }}</td>
                    <td class="c">{{ $row['normal_hours'] }}</td>
                    <td class="{{ $row['has_records'] ? 'r' : 'nil' }}">{{ $row['normal_rate'] }}</td>
                    <td class="{{ $row['has_records'] ? 'r' : 'nil' }}">{{ $row['overtime_rate'] }}</td>
                    <td class="{{ $row['has_records'] ? 'r' : 'nil' }}">{{ $row['normal_earnings'] }}</td>
                    <td class="{{ $row['has_records'] ? 'r' : 'nil' }}">{{ $row['overtime_earnings'] }}</td>
                    <td class="{{ $row['has_records'] ? 'r' : 'nil' }}">{{ $row['total_earnings'] }}</td>
                    <td class="c">{{ $row['paid_on'] }}</td>
                </tr>
            @empty
                <tr><td colspan="16" class="c">No employees on the roll for this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
