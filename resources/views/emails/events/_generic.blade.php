@extends('emails.layouts.business')

@php
    $event = \App\Notifications\NotificationCatalog::get($eventKey);
    $eventName = $event['name'] ?? str_replace(['.', '_'], [' — ', ' '], $eventKey);

    // Auto-detect entity fields worth showing — avoids leaking internal config text.
    $autoFields = collect([
        'Code'     => $entity?->code ?? $entity?->employee_code ?? $entity?->request_code ?? null,
        'Name'     => $entity?->name ?? null,
        'Title'    => $entity?->title ?? null,
        'Subject'  => $entity?->subject ?? null,
        'Employee' => optional($entity?->employee)->full_name ?? (isset($entity->first_name) ? trim($entity->first_name.' '.($entity->last_name ?? '')) : null),
        'Amount'   => isset($entity?->amount) ? ($business->currency_symbol ?? '₹').number_format((float) $entity->amount, 2) : null,
        'Status'   => $entity?->status ?? null,
        'Date'     => $entity?->date ?? null,
    ])->filter()->all();
@endphp

@section('body')
    <h1>{{ $eventName }}</h1>
    <p class="lede">{{ $business->name }} notification</p>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @endif

    <p>You have a new <strong>{{ strtolower($eventName) }}</strong> notification from {{ $business->name }}.</p>

    @if(! empty($autoFields) || ! empty($context))
        <table class="meta-table">
            @foreach($autoFields as $label => $val)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td class="val">
                        @if($label === 'Status')
                            <span class="badge badge-{{ strtolower($val) }}">{{ ucfirst(str_replace('_',' ',$val)) }}</span>
                        @else
                            {{ $val }}
                        @endif
                    </td>
                </tr>
            @endforeach
            @foreach($context as $k => $v)
                @continue(is_array($v) || is_object($v))
                <tr>
                    <td class="label">{{ ucfirst(str_replace('_', ' ', $k)) }}</td>
                    <td class="val">{{ $v }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p style="color:#64748b; font-size:13px;">Log in to your {{ $business->name }} portal to view full details.</p>
@endsection
