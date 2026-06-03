@extends('emails.layouts.business')

@php
    $emp = $entity?->employee;
@endphp

@section('body')
    <h1>Bank detail change request</h1>
    <p class="lede">An employee has requested a change to their bank account details.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>Please review the requested change below before approving it. Once approved, the new details will be used for the next salary disbursement.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $emp?->full_name ?? '—' }} @if($emp?->employee_code) <span style="color:#64748b;font-weight:400;">({{ $emp->employee_code }})</span>@endif</td></tr>
        <tr><td class="label">Current bank</td><td class="val">{{ $entity?->current_bank_name ?? '—' }}@if($entity?->current_account_number) · …{{ substr($entity->current_account_number, -4) }}@endif</td></tr>
        <tr><td class="label">Requested bank</td><td class="val">{{ $entity?->requested_bank_name ?? '—' }}@if($entity?->requested_account_number) · …{{ substr($entity->requested_account_number, -4) }}@endif</td></tr>
        <tr><td class="label">Requested IFSC</td><td class="val">{{ $entity?->requested_ifsc ?? '—' }}</td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-pending">Pending approval</span></td></tr>
    </table>

    <div class="alert alert-warning"><strong>Verify before approving.</strong> Bank changes affect salary credits. Please confirm with the employee through a separate channel.</div>
@endsection
