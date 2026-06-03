@extends('emails.layouts.business')

@php
    $emp = $entity?->employee;
@endphp

@section('body')
    <h1>Salary structure rejected</h1>
    <p class="lede">The submitted structure was not approved and will not take effect.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>The salary structure submitted for <strong>{{ $emp?->full_name ?? 'the employee' }}</strong> has been rejected. Please review the notes below and resubmit if required.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $emp?->full_name ?? '—' }} @if($emp?->employee_code) <span style="color:#64748b;font-weight:400;">({{ $emp->employee_code }})</span>@endif</td></tr>
        <tr><td class="label">Effective from</td><td class="val">{{ $entity?->effective_from ? \Carbon\Carbon::parse($entity->effective_from)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Reviewed by</td><td class="val">{{ optional($entity?->reviewer)->name ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-rejected">Rejected</span></td></tr>
    </table>

    @if($entity?->review_notes)
        <div class="alert alert-danger"><strong>Reason:</strong> {{ $entity->review_notes }}</div>
    @endif

    <p style="color:#64748b; font-size:13px;">Open the HR module to review and submit a revised structure.</p>
@endsection
