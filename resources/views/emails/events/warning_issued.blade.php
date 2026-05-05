@extends('emails.layouts.business')

@section('body')
    <h1>Formal warning issued</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is a formal communication regarding a warning issued to you. Please read the details below carefully and reach out to HR if you wish to discuss or respond.</p>

    <table class="meta-table">
        <tr><td class="label">Warning Code</td><td class="val">{{ $entity?->warning_code ?? '—' }}</td></tr>
        <tr><td class="label">Issued Date</td><td class="val">{{ $entity?->issued_date ? \Carbon\Carbon::parse($entity?->issued_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Severity</td><td class="val">{{ ucfirst($entity?->severity ?? '—') }}</td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
    </table>

    <div class="alert alert-warning">
        <strong>Please note:</strong> This warning is part of your employee record. Repeated incidents may lead to further disciplinary action.
    </div>

    <p>Regards,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
