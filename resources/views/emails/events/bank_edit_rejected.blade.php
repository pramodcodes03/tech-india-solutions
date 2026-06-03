@extends('emails.layouts.business')

@section('body')
    <h1>Bank detail change rejected</h1>
    <p class="lede">Your request to change your bank account details was not approved.</p>

    @if($recipientName)<p>Hi {{ $recipientName }},</p>@endif

    <p>Your existing bank details remain unchanged. Please review the notes below and resubmit if needed.</p>

    <table class="meta-table">
        <tr><td class="label">Requested bank</td><td class="val">{{ $entity?->requested_bank_name ?? '—' }}</td></tr>
        <tr><td class="label">Requested account</td><td class="val">@if($entity?->requested_account_number)…{{ substr($entity->requested_account_number, -4) }}@else —@endif</td></tr>
        <tr><td class="label">Status</td><td class="val"><span class="badge badge-rejected">Rejected</span></td></tr>
    </table>

    @if($entity?->review_notes)
        <div class="alert alert-danger"><strong>Reason:</strong> {{ $entity->review_notes }}</div>
    @endif

    <p style="color:#64748b; font-size:13px;">Open your employee portal to resubmit a corrected request.</p>
@endsection
