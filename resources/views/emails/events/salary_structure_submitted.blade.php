@extends('emails.layouts.business')

@php
    $emp = $entity?->employee;
    $cur = $business->currency_symbol ?? '₹';
@endphp

@section('body')
    <h1>Salary structure pending your approval</h1>
    <p class="lede">A new or revised salary structure needs your review before it takes effect.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>HR has submitted a salary structure for <strong>{{ $emp?->full_name ?? 'an employee' }}</strong>. It will not be applied to payroll until you approve it.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $emp?->full_name ?? '—' }} @if($emp?->employee_code) <span style="color:#64748b;font-weight:400;">({{ $emp->employee_code }})</span>@endif</td></tr>
        <tr><td class="label">Department</td><td class="val">{{ $emp?->department?->name ?? '—' }}</td></tr>
        <tr><td class="label">Effective from</td><td class="val">{{ $entity?->effective_from ? \Carbon\Carbon::parse($entity->effective_from)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Monthly gross</td><td class="val">{{ $cur }}{{ number_format((float) ($entity?->gross_monthly ?? 0), 2) }}</td></tr>
        <tr><td class="label">Annual CTC</td><td class="val">{{ $cur }}{{ number_format((float) ($entity?->ctc_annual ?? 0), 2) }}</td></tr>
        <tr><td class="label">Submitted by</td><td class="val">{{ optional($entity?->submitter)->name ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-pending">Pending approval</span></td></tr>
    </table>

    <div class="alert alert-info"><strong>Next step:</strong> Open the HR module to review and approve or reject this structure.</div>
@endsection
