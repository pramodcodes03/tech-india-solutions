@extends('emails.layouts.business')

@section('body')
    <h1>Your Offer of Employment</h1>

    <p>Dear {{ $candidateName }},</p>

    <p>
        Congratulations! We are pleased to extend an offer of employment to you at
        <strong>{{ $business->name }}</strong>. Please find your official Offer Letter attached to this email
        as a PDF.
    </p>

    <p>
        Kindly review the details, and confirm your acceptance by signing and returning a copy of the letter
        as mentioned in it. If you have any questions, simply reply to this email.
    </p>

    <p>We look forward to welcoming you to the {{ $business->name }} family.</p>

    <p>Warm regards,<br><strong>{{ $business->name }}</strong></p>
@endsection
