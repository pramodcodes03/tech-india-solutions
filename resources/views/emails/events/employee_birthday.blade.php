@extends('emails.layouts.business')

@section('body')
    <h1>Happy birthday, {{ $recipientName ?? ($entity?->first_name ?? 'there') }}!</h1>

    <p>Wishing you a fantastic year ahead, filled with joy, good health, and great memories.</p>

    <p>Thank you for being a valued part of the {{ $business->name }} family — today is all about you.</p>

    <div class="alert alert-success">
        <strong>Have a wonderful day!</strong> Take a moment to celebrate — you deserve it.
    </div>

    <p>With warm wishes,<br><strong>The {{ $business->name }} Team</strong></p>
@endsection
