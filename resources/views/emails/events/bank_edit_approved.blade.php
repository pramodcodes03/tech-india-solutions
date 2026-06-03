@extends('emails.layouts.business')

@section('body')
    <h1>Bank details updated</h1>
    <p class="lede">Your request to change your bank account details has been approved.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>Your new bank details have been saved and will be used for the next salary disbursement.</p>

    <table class="meta-table">
        <tr><td class="label">Bank</td><td class="val">{{ $entity?->requested_bank_name ?? '—' }}</td></tr>
        <tr><td class="label">Account</td><td class="val">@if($entity?->requested_account_number)…{{ substr($entity->requested_account_number, -4) }}@else —@endif</td></tr>
        <tr><td class="label">IFSC</td><td class="val">{{ $entity?->requested_ifsc ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-approved">Approved</span></td></tr>
    </table>

    @if($entity?->review_notes)
        <div class="alert alert-success"><strong>Notes:</strong> {{ $entity->review_notes }}</div>
    @endif

    <p style="color:#64748b; font-size:13px;">If you didn't request this change, contact HR immediately.</p>
@endsection
