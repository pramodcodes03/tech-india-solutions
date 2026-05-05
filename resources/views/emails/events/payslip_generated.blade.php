@extends('emails.layouts.business')

@section('body')
    <h1>Your payslip is ready</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Your payslip for the latest pay period has been generated and is attached to this email as a PDF. A summary is provided below for your reference.</p>

    <table class="meta-table">
        <tr><td class="label">Pay Period</td><td class="val">{{ $context['period'] ?? '—' }}</td></tr>
        <tr><td class="label">Payslip Code</td><td class="val">{{ $entity?->payslip_code ?? '—' }}</td></tr>
        <tr><td class="label">Gross</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->gross ?? 0, 2) }}</td></tr>
        <tr><td class="label">Net Pay</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->net ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Note:</strong> Please review your payslip carefully. If you spot any discrepancy, contact HR within 7 days.
    </div>

    <p>Thank you for your contribution.<br><strong>{{ $business->name }}</strong></p>
@endsection
