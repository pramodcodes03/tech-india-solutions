@php
    $letterTitle = 'Confirmation Letter';
    $reference = 'CNF/'.($employee?->employee_code ?? '—');
    $subjectLine = 'Confirmation of employment';
    $confirmedOn = $employee?->confirmation_date ?? $employee?->probation_end_date;
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p>Dear {{ $employee?->first_name ?? 'Sir/Madam' }},</p>

    <p>
        Further to your appointment as <strong>{{ $employee?->designation?->name ?? '—' }}</strong> on
        <strong>{{ optional($employee?->joining_date)->format('d F Y') ?? '—' }}</strong>, we are pleased to
        confirm you in the services of {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }}
        with effect from <strong>{{ optional($confirmedOn)->format('d F Y') ?? 'the date of this letter' }}</strong>.
    </p>

    <p>
        Your performance during the probationary period has been found satisfactory. All other terms and
        conditions of your appointment remain unchanged.
    </p>

    <p>
        We take this opportunity to thank you for your contribution and wish you a long and rewarding
        association with us.
    </p>

    <p style="margin-top:14px;">Yours sincerely,</p>
@endsection
