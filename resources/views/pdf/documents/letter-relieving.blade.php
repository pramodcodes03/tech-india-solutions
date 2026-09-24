@php
    $letterTitle = 'Relieving Letter';
    $reference = 'REL/'.($employee?->employee_code ?? '—');
    $subjectLine = 'Relieving from services';
    $lastDay = $employee?->last_working_date ?? $employee?->relieving_date;
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p>Dear {{ $employee?->first_name ?? 'Sir/Madam' }},</p>

    <p>
        This is with reference to your resignation from the services of
        {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }}.
    </p>

    <p>
        We confirm that you have been relieved from your duties as
        <strong>{{ $employee?->designation?->name ?? '—' }}</strong> at the close of business on
        <strong>{{ optional($lastDay)->format('d F Y') ?? '—' }}</strong>.
        You joined us on <strong>{{ optional($employee?->joining_date)->format('d F Y') ?? '—' }}</strong>.
    </p>

    <p>
        All dues payable to you have been settled and you have handed over the Company property and
        responsibilities in your charge. Nothing remains outstanding from either side as of the date of relieving.
    </p>

    <p>
        We thank you for your services and wish you the very best for the future.
    </p>

    <p style="margin-top:14px;">Yours sincerely,</p>
@endsection
