@props(['score' => null, 'size' => 96, 'label' => null, 'color' => null])

{{-- A score as a ring. Pure CSS conic-gradient — no chart library, so it works
     inside tables, cards and PDFs-to-screen alike. --}}
@php
    $value = $score === null ? null : max(0, min(100, (float) $score));
    $ringColor = $color ?: match (true) {
        $value === null => '#9ca3af',
        $value >= 85 => '#00ab55',
        $value >= 75 => '#2196f3',
        $value >= 60 => '#e2a03f',
        $value >= 40 => '#f97316',
        default => '#e7515a',
    };
    $inner = $size - 14;
@endphp

<div class="relative shrink-0 grid place-content-center" style="width: {{ $size }}px; height: {{ $size }}px;">
    <div class="absolute inset-0 rounded-full"
         style="background: conic-gradient({{ $ringColor }} {{ ($value ?? 0) * 3.6 }}deg, rgba(148,163,184,.18) 0deg);"></div>
    <div class="relative rounded-full bg-white dark:bg-[#1b2e4b] grid place-content-center text-center"
         style="width: {{ $inner }}px; height: {{ $inner }}px;">
        <div class="font-extrabold leading-none" style="color: {{ $ringColor }}; font-size: {{ max(13, $size * 0.24) }}px;">
            {{ $value === null ? '—' : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') }}
        </div>
        @if($label)
            <div class="text-[9px] font-bold uppercase tracking-wide text-gray-400 mt-0.5">{{ $label }}</div>
        @endif
    </div>
</div>
