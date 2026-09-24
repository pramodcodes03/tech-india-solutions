{{--
    Form A · The Punjab Child Labour (Prohibition And Regulation) Rules, 1997,
    Rule 4(1).

    An establishment that employs no children still files this form, so an empty
    register prints as a single Nil row rather than an empty grid — that is the
    nil return, and it is what an inspector expects to see.
--}}
@extends('pdf.statutory.layout')

@section('form-no', 'Form A')
@section('form-rule', '[See Rule 4 (1)]')
@section('form-title', 'Register of Child Labour')
@section('form-act', 'The Punjab Child Labour (Prohibition And Regulation) Rules, 1997')

@section('form-meta')
    <table class="meta">
        <tr>
            <td class="k">Name and address of the Employer</td>
            <td>
                <div>{{ $establishment['employer_name'] }}</div>
                <div>{{ $establishment['employer_address'] }}</div>
            </td>
        </tr>
        <tr>
            <td class="k">Nature of work done by the Establishment</td>
            <td>{{ $establishment['nature_of_work'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="k">Month &amp; Year</td>
            <td>{{ $monthLabel }} &nbsp;&nbsp; Place of work : {{ $establishment['place_of_work'] ?: '-' }}</td>
        </tr>
    </table>
@endsection

@section('content')
    <table class="grid">
        <thead>
            <tr>
                <th style="width:4%">Sl.No</th>
                <th style="width:12%">Name of the child</th>
                <th style="width:12%">Father's Name</th>
                <th style="width:8%">Date of Birth</th>
                <th style="width:16%">Permanent Address</th>
                <th style="width:9%">Date of Joining in the establishment</th>
                <th style="width:11%">Nature of work employed</th>
                <th style="width:7%">Daily hours of work</th>
                <th style="width:7%">Intervals of rest</th>
                <th style="width:7%">Wages paid</th>
                <th style="width:7%">Remarks</th>
            </tr>
            <tr class="colno">
                <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td>
                <td>7</td><td>8</td><td>9</td><td>10</td><td>11</td>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $i => $record)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $record->child_name }}</td>
                    <td>{{ $record->father_name ?: 'Nil' }}</td>
                    <td class="c">{{ $record->date_of_birth?->format('d-M-Y') ?: 'Nil' }}</td>
                    <td>{{ $record->permanent_address ?: 'Nil' }}</td>
                    <td class="c">{{ $record->joined_on?->format('d-M-Y') ?: 'Nil' }}</td>
                    <td>{{ $record->nature_of_work ?: 'Nil' }}</td>
                    <td class="c">{{ $record->daily_hours ?: 'Nil' }}</td>
                    <td class="c">{{ $record->rest_intervals ?: 'Nil' }}</td>
                    <td class="r">{{ number_format((float) $record->wages_paid, 2) }}</td>
                    <td>{{ $record->remarks ?: 'Nil' }}</td>
                </tr>
            @empty
                {{-- The nil return: no children employed in this month. --}}
                <tr>
                    <td class="c">1</td>
                    @for($c = 0; $c < 10; $c++)<td class="nil">Nil</td>@endfor
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
