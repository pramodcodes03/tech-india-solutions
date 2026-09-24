@props(['label', 'value', 'sub' => null, 'tone' => 'primary', 'icon' => null])

@php
    $tones = [
        'primary' => 'text-primary bg-primary/10',
        'success' => 'text-success bg-success/10',
        'warning' => 'text-warning bg-warning/10',
        'danger'  => 'text-danger bg-danger/10',
        'info'    => 'text-info bg-info/10',
        'secondary' => 'text-secondary bg-secondary/10',
    ];
    $toneClass = $tones[$tone] ?? $tones['primary'];
@endphp

<div class="panel p-4 flex items-start gap-3">
    @if($icon)
        <div class="w-10 h-10 rounded-xl grid place-content-center shrink-0 {{ $toneClass }}">
            {!! $icon !!}
        </div>
    @endif
    <div class="min-w-0">
        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $label }}</div>
        <div class="text-2xl font-extrabold mt-0.5 leading-tight truncate">{{ $value }}</div>
        @if($sub)
            <div class="text-xs text-gray-500 mt-0.5">{{ $sub }}</div>
        @endif
    </div>
</div>
