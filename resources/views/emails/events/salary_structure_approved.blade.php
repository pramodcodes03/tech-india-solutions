@extends('emails.layouts.business')

@php
    $emp = $entity?->employee;
    $cur = $business->currency_symbol ?? '₹';
@endphp

@section('body')
    <h1>Salary structure approved</h1>
    <p class="lede">The new structure is now live and will be applied in the next payroll cycle.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>The salary structure for <strong>{{ $emp?->full_name ?? 'the employee' }}</strong> has been approved.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $emp?->full_name ?? '—' }} @if($emp?->employee_code) <span style="color:#64748b;font-weight:400;">({{ $emp->employee_code }})</span>@endif</td></tr>
        <tr><td class="label">Effective from</td><td class="val">{{ $entity?->effective_from ? \Carbon\Carbon::parse($entity->effective_from)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Monthly gross</td><td class="val">{{ $cur }}{{ number_format((float) ($entity?->gross_monthly ?? 0), 2) }}</td></tr>
        <tr><td class="label">Annual CTC</td><td class="val">{{ $cur }}{{ number_format((float) ($entity?->ctc_annual ?? 0), 2) }}</td></tr>
        <tr><td class="label">Approved by</td><td class="val">{{ optional($entity?->reviewer)->name ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-approved">Approved</span></td></tr>
    </table>

    @if($entity?->review_notes)
        <div class="alert alert-success"><strong>Notes:</strong> {{ $entity->review_notes }}</div>
    @endif
@endsection
