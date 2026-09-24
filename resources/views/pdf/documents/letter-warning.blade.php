@php
    use App\Models\Warning as WarningModel;
    $employee = $warning?->employee;
    $letterTitle = 'Warning Letter';
    $reference = $warning?->warning_code ?? '—';
    $letterDate = $warning?->issued_on;
    $subjectLine = $warning?->title ?? 'Formal warning';
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p>Dear {{ $employee?->first_name ?? 'Sir/Madam' }},</p>

    <p>
        This letter is to place on record a formal warning
        {{ $warning?->issued_on ? 'dated '.$warning->issued_on->format('d F Y') : '' }}
        in respect of the following:
    </p>

    <div class="panel" style="margin:10px 0;">
        <div class="panel-title">Incident</div>
        <div style="font-size:10px;">{{ $warning?->reason ?? '—' }}</div>
        @if($warning?->description)
            <div style="font-size:10px; margin-top:5px;">{{ $warning->description }}</div>
        @endif
    </div>

    <table class="kv" style="margin-bottom:10px;">
        <tr><td class="k">Warning level</td><td class="v">{{ WarningModel::LEVELS[(int) ($warning?->level ?? 0)] ?? '—' }}</td></tr>
        <tr><td class="k">Issued on</td><td class="v">{{ optional($warning?->issued_on)->format('d F Y') ?? '—' }}</td></tr>
        <tr><td class="k">Issued by</td><td class="v">{{ $warning?->issuer?->display_name ?? 'HR' }}</td></tr>
    </table>

    <p>
        You are advised to ensure that such conduct is not repeated. Any recurrence will be viewed seriously
        and may attract further disciplinary action in accordance with the Company's policies.
    </p>

    <p>
        You are requested to acknowledge receipt of this letter. Should you wish to submit an explanation,
        you may do so in writing to the HR department.
    </p>

    <p style="margin-top:14px;">For {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }},</p>
@endsection
