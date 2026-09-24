@php
    use App\Support\AmountInWords;
    $letterTitle = 'Appointment Letter';
    $reference = 'APT/'.($employee?->employee_code ?? '—');
    $subjectLine = 'Appointment to the post of '.($employee?->designation?->name ?? 'the offered role');
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p>Dear {{ $employee?->first_name ?? 'Sir/Madam' }},</p>

    <p>
        We are pleased to appoint you as <strong>{{ $employee?->designation?->name ?? '—' }}</strong>
        in the <strong>{{ $employee?->department?->name ?? '—' }}</strong> department of
        {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }},
        with effect from <strong>{{ optional($employee?->joining_date)->format('d F Y') ?? '—' }}</strong>.
    </p>

    @if($salary)
        <p>
            Your annual cost to company is
            <strong>₹{{ number_format((float) $salary->ctc_annual, 2) }}</strong>
            ({{ AmountInWords::currency($salary->ctc_annual) }}), with a monthly gross of
            <strong>₹{{ number_format((float) $salary->gross_monthly, 2) }}</strong>.
            The detailed break-up is annexed to this letter.
        </p>
    @endif

    <p>
        You will report to {{ $employee?->reportingManager?->full_name ?? 'the reporting manager assigned to you' }}.
        Your employment is subject to the terms of service, the code of conduct and the policies of the Company
        as amended from time to time.
    </p>

    @if($employee?->probation_end_date)
        <p>
            You will serve a probationary period ending
            <strong>{{ $employee->probation_end_date->format('d F Y') }}</strong>. Confirmation in service is
            subject to satisfactory performance during this period.
        </p>
    @endif

    <p>
        Please sign and return a copy of this letter as acceptance of the terms set out above.
        We look forward to your contribution.
    </p>

    <p style="margin-top:14px;">Yours sincerely,</p>
@endsection
