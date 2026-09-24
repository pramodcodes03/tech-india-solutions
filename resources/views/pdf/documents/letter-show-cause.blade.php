@php
    $employee = $warning?->employee;
    $letterTitle = 'Show-cause Notice';
    $reference = 'SCN/'.($warning?->warning_code ?? '—');
    $letterDate = $warning?->issued_on;
    $subjectLine = 'Show-cause notice — '.($warning?->title ?? 'explanation required');
    $replyBy = ($warning?->issued_on ?? now())->copy()->addDays(3);
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p>Dear {{ $employee?->first_name ?? 'Sir/Madam' }},</p>

    <p>
        It has been brought to the notice of the management that the following incident occurred, which
        prima facie amounts to a breach of the Company's code of conduct:
    </p>

    <div class="panel" style="margin:10px 0;">
        <div class="panel-title">Allegation</div>
        <div style="font-size:10px;">{{ $warning?->reason ?? '—' }}</div>
        @if($warning?->description)
            <div style="font-size:10px; margin-top:5px;">{{ $warning->description }}</div>
        @endif
    </div>

    <p>
        You are hereby called upon to show cause, <strong>in writing, on or before
        {{ $replyBy->format('d F Y') }}</strong>, as to why disciplinary action should not be taken
        against you in this matter.
    </p>

    <p>
        Your explanation will be considered on its merits before any decision is taken. Should no reply be
        received within the stated period, the management will be constrained to proceed on the basis of the
        material on record.
    </p>

    <p>
        This notice is issued without prejudice to any other right or remedy available to the Company.
    </p>

    <p style="margin-top:14px;">For {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }},</p>
@endsection
