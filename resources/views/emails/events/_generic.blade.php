@extends('emails.layouts.business')

@php
    $event = \App\Notifications\NotificationCatalog::get($eventKey);
    $eventName = $event['name'] ?? $eventKey;
@endphp

@section('body')
    <h1>{{ $eventName }}</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>{{ $event['description'] ?? 'You have a new notification from '.$business->name.'.' }}</p>

    @if($entity)
        <table class="meta-table">
            @if(isset($entity?->code))<tr><td class="label">Code</td><td class="val">{{ $entity?->code }}</td></tr>@endif
            @if(isset($entity?->name))<tr><td class="label">Name</td><td class="val">{{ $entity?->name }}</td></tr>@endif
            @if(isset($entity?->title))<tr><td class="label">Title</td><td class="val">{{ $entity?->title }}</td></tr>@endif
            @if(isset($entity?->amount))<tr><td class="label">Amount</td><td class="val">{{ $business->currency_symbol ?? '₹' }}{{ number_format($entity?->amount, 2) }}</td></tr>@endif
            @if(isset($entity?->status))<tr><td class="label">Status</td><td class="val">{{ ucfirst($entity?->status) }}</td></tr>@endif
            @foreach($context as $k => $v)
                <tr><td class="label">{{ ucfirst(str_replace('_', ' ', $k)) }}</td><td class="val">{{ $v }}</td></tr>
            @endforeach
        </table>
    @endif

    <p>If this notification doesn't apply to you, please contact your {{ $business->name }} administrator.</p>
@endsection
