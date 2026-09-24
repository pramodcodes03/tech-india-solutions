@php
    $letterTitle = 'Experience Certificate';
    $reference = 'EXP/'.($employee?->employee_code ?? '—');
    $subjectLine = null;
    $lastDay = $employee?->last_working_date ?? $employee?->relieving_date;
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p style="text-align:center; font-weight:bold; letter-spacing:1px; margin-bottom:14px;">TO WHOMSOEVER IT MAY CONCERN</p>

    <p>
        This is to certify that <strong>{{ $employee?->full_name ?? '—' }}</strong>
        (Employee ID <strong>{{ $employee?->employee_code ?? '—' }}</strong>) was employed with
        {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }} from
        <strong>{{ optional($employee?->joining_date)->format('d F Y') ?? '—' }}</strong>
        to <strong>{{ optional($lastDay)->format('d F Y') ?? 'date' }}</strong>.
    </p>

    <p>
        At the time of leaving, {{ $employee?->gender === 'female' ? 'she' : 'he' }} held the position of
        <strong>{{ $employee?->designation?->name ?? '—' }}</strong> in the
        <strong>{{ $employee?->department?->name ?? '—' }}</strong> department.
    </p>

    <p>
        During {{ $employee?->gender === 'female' ? 'her' : 'his' }} tenure with us,
        {{ $employee?->gender === 'female' ? 'her' : 'his' }} conduct and performance were found to be
        satisfactory. We wish {{ $employee?->gender === 'female' ? 'her' : 'him' }} success in
        {{ $employee?->gender === 'female' ? 'her' : 'his' }} future endeavours.
    </p>

    <p>This certificate is issued on request.</p>

    <p style="margin-top:14px;">For {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }},</p>
@endsection
