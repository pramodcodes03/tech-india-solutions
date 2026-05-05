@extends('emails.layouts.business')

@section('body')
    <h1>Salary credited</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>This is to confirm that your salary for the latest pay period has been disbursed. Please check your bank account for the credit.</p>

    <table class="meta-table">
        <tr><td class="label">Pay Period</td><td class="val">{{ $context['period'] ?? '—' }}</td></tr>
        <tr><td class="label">Net Paid</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->net ?? 0, 2) }}</span></td></tr>
        <tr><td class="label">Paid Date</td><td class="val">{{ $entity?->paid_date ? \Carbon\Carbon::parse($entity?->paid_date)->format('d M Y') : '—' }}</td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Payment processed.</strong> If the credit is not visible within 1–2 working days, please contact HR / Payroll.
    </div>

    <p>Thank you,<br><strong>{{ $business->name }}</strong></p>
@endsection
