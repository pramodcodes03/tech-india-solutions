@extends('emails.layouts.business')

@section('body')
    <h1>Penalty amount reduced</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>Following a review, the penalty originally recorded against your account has been reduced. Updated figures are shown below.</p>

    <table class="meta-table">
        <tr><td class="label">Penalty Code</td><td class="val">{{ $entity?->penalty_code ?? '—' }}</td></tr>
        <tr><td class="label">Original Amount</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->original_amount ?? 0, 2) }}</td></tr>
        <tr><td class="label">Revised Amount</td><td class="val"><span class="amount">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->new_amount ?? $entity?->amount ?? 0, 2) }}</span></td></tr>
    </table>

    <div class="alert alert-info">
        <strong>FYI:</strong> The revised amount, if any, will reflect in the next applicable payroll cycle.
    </div>

    <p>Regards,<br><strong>{{ $business->name }} HR</strong></p>
@endsection
