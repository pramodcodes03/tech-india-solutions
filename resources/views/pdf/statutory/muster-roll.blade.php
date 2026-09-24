{{--
    FORM C · Register of Employees · Punjab Shops and Commercial Establishments
    Rules, 1958, Rule 5.

    Unlike the other registers this one is kept per employee, not per payroll:
    each employee gets their own sheet carrying their identity block and a row
    for every day of the month. So the shared heading is rendered inline once
    per block rather than page-fixed, and each block ends a page.
--}}
@extends('pdf.statutory.layout')

@section('form-no', 'FORM C')
@section('form-title', 'Register of Employees')
@section('form-act', '(Rule 5 of the Punjab Shops and Commercial Establishments Rules, 1958)')

@push('styles')
    <style>
        .emp { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 7px; }
        .emp td { padding: 1px 2px; vertical-align: top; }
        .emp td.k { width: 20%; }
        .emp td.v { width: 30%; font-weight: bold; }
    </style>
@endpush

@section('content')
    @forelse($blocks as $i => $block)
        @php $e = $block['employee']; @endphp

        <div @class(['break' => ! $loop->last])>
            @include('pdf.statutory._head', [
                'establishment' => $establishment,
                'formNo' => 'FORM C',
                'formRule' => '',
                'formTitle' => 'Register of Employees',
                'formAct' => '(Rule 5 of the Punjab Shops and Commercial Establishments Rules, 1958)',
                'formMeta' => '',
            ])

            <table class="emp">
                <tr>
                    <td class="k">Name of the employee</td>
                    <td class="v">{{ $e->full_name }}</td>
                    <td class="k">E.Code</td>
                    <td class="v">{{ $e->employee_code }}</td>
                </tr>
                <tr>
                    <td class="k">Father's / Husband's name</td>
                    <td class="v">{{ $e->guardian_name ?: '-' }}</td>
                    <td class="k">Age</td>
                    <td class="v">{{ $block['age'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Nature of work</td>
                    <td class="v">{{ $e->designation?->name ?: '-' }}</td>
                    <td class="k">Date of appointment</td>
                    <td class="v">{{ $e->joining_date?->format('d-M-Y') ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Whether employed on daily, monthly, contract or piece-rate wages, with rate</td>
                    <td class="v">{{ $establishment['wage_period'] }}</td>
                    <td class="k">Year &amp; Month</td>
                    <td class="v">{{ $year }} {{ $monthName }}</td>
                </tr>
            </table>

            <table class="grid">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:7%">Date</th>
                        <th colspan="2">Spread over</th>
                        <th colspan="2">Interval for rest and meals</th>
                        <th rowspan="2" style="width:6%">Total working hours</th>
                        <th colspan="2">Over time</th>
                        <th colspan="3">Leave</th>
                        <th rowspan="2" style="width:12%">Remarks</th>
                        <th colspan="2">Signature</th>
                    </tr>
                    <tr>
                        <th style="width:7%">From</th>
                        <th style="width:7%">To</th>
                        <th style="width:7%">From</th>
                        <th style="width:7%">To</th>
                        <th style="width:6%">Remuneration</th>
                        <th style="width:6%">Duration</th>
                        <th style="width:5%">due</th>
                        <th style="width:7%">Date of application</th>
                        <th style="width:7%">Date of grant</th>
                        <th style="width:8%">Employer</th>
                        <th style="width:8%">Employee</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($block['days'] as $day)
                        @php
                            [$spreadFrom, $spreadTo] = $day['spread']
                                ? array_map('trim', explode(' To ', $day['spread']))
                                : [null, null];
                            [$restFrom, $restTo] = $day['rest'] && str_contains($day['rest'], ' To ')
                                ? array_map('trim', explode(' To ', explode(',', $day['rest'])[0]))
                                : [null, null];
                        @endphp
                        <tr>
                            <td class="c">{{ $day['date']->format('d-M-Y') }}</td>
                            @if($day['label'])
                                {{-- A non-working day names itself across the spread-over columns. --}}
                                <td class="c" colspan="4">{{ $day['label'] }}</td>
                            @else
                                <td class="c">{{ $spreadFrom ?: '-' }}</td>
                                <td class="c">{{ $spreadTo ?: '-' }}</td>
                                <td class="c">{{ $restFrom ?: '-' }}</td>
                                <td class="c">{{ $restTo ?: '-' }}</td>
                            @endif
                            <td class="c">{{ $day['hours'] }}</td>
                            <td class="r">{{ $day['ot_pay'] !== null ? number_format($day['ot_pay'], 2) : '-' }}</td>
                            <td class="c">{{ $day['ot_hours'] }}</td>
                            <td class="c">{{ $day['leave_days'] !== null ? number_format($day['leave_days'], 2) : '-' }}</td>
                            <td class="c">{{ $day['leave_applied'] ?: '-' }}</td>
                            <td class="c">{{ $day['leave_granted'] ?: '-' }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="sign-off">Signature of Employer / Manager / Contractor / Authorised Person</div>
        </div>
    @empty
        @include('pdf.statutory._head', [
            'establishment' => $establishment,
            'formNo' => 'FORM C',
            'formRule' => '',
            'formTitle' => 'Register of Employees',
            'formAct' => '(Rule 5 of the Punjab Shops and Commercial Establishments Rules, 1958)',
            'formMeta' => '',
        ])
        <table class="grid">
            <tr><td class="c">No employees on the roll for this month.</td></tr>
        </table>
    @endforelse
@endsection

{{-- Each block signs itself off, so the layout must not add a second one. --}}
@section('signature')
@endsection
