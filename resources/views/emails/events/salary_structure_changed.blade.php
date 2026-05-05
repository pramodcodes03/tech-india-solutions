@extends('emails.layouts.business')

@section('body')
    <h1>Your salary structure has been updated</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Your salary structure has been revised. The summary below reflects the latest figures effective from the date shown. Please reach out to HR for the full breakup.</p>

    <table class="meta-table">
        <tr><td class="label">Effective From</td><td class="val">{{ $entity?->effective_from ? \Carbon\Carbon::parse($entity?->effective_from)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Annual CTC</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->ctc_annual ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Basic</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->basic ?? 0, 2) }}</td></tr>
        <tr><td class="label">HRA</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->hra ?? 0, 2) }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Note:</strong> A signed salary annexure / appointment addendum will be shared separately by HR.
    </div>

    <p>Thank you,<br><strong>{{ $business->name }}</strong></p>
@endsection
