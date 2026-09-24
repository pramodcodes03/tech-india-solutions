{{--
    Colour-coded payment mode badge — one source of truth so every screen
    (payments, invoices, expenses, budgets, reports) reads the same at a glance.

        Cash          → red     (physical cash needs the most scrutiny)
        Bank Transfer → green   (traceable, settled)
        Cheque        → yellow  (traceable but can still bounce)
        UPI           → blue    (instant + traceable)
        Card          → purple

    Handles both vocabularies in the schema: payments.mode uses
    `bank_transfer`, expenses.payment_method uses `bank`.
--}}
@props(['mode', 'label' => null])

@php
    $key = strtolower(str_replace([' ', '-'], '_', trim((string) $mode)));

    $map = [
        'cash'          => ['Cash',          'bg-danger'],
        'bank_transfer' => ['Bank Transfer', 'bg-success'],
        'bank'          => ['Bank Transfer', 'bg-success'],
        'neft'          => ['Bank Transfer', 'bg-success'],
        'cheque'        => ['Cheque',        'bg-warning'],
        'upi'           => ['UPI',           'bg-info'],
        'card'          => ['Card',          'bg-secondary'],
    ];

    [$text, $class] = $map[$key] ?? [
        ucwords(str_replace('_', ' ', (string) $mode)),
        'bg-dark',
    ];
@endphp

@if(filled($mode))
    <span {{ $attributes->merge(['class' => 'badge '.$class]) }}>{{ $label ?? $text }}</span>
@else
    <span class="text-gray-400">&mdash;</span>
@endif
