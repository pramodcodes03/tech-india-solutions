@props(['band' => null, 'color' => null, 'size' => 'sm'])

@php
    $name = $band?->name ?? (is_string($band) ? $band : null);
    $tone = $color ?: ($band?->color ?? '#6b7280');
    $pad = $size === 'lg' ? 'px-3 py-1 text-xs' : 'px-2 py-0.5 text-[11px]';
@endphp

@if($name)
    <span class="inline-block rounded-full font-bold {{ $pad }}"
          style="background: {{ $tone }}1a; color: {{ $tone }};">{{ $name }}</span>
@else
    <span class="text-gray-400">—</span>
@endif
