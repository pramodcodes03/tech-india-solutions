@extends('emails.layouts.business')

@section('body')
    <h1>Payroll run completed</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>The payroll run for the latest pay period has been completed successfully. A summary is provided below for your records.</p>

    <table class="meta-table">
        <tr><td class="label">Pay Period</td><td class="val">{{ $context['period'] ?? '—' }}</td></tr>
        <tr><td class="label">Employees Processed</td><td class="val">{{ $context['employees_count'] ?? 0 }}</td></tr>
        <tr><td class="label">Total Disbursed</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($context['total_amount'] ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-success">
        <strong>Run successful.</strong> Payslips have been generated and disbursement instructions can now be initiated with the bank.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
