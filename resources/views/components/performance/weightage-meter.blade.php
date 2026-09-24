@props(['total' => 0, 'showLabel' => true])

{{-- The 100% validator. Amber under, red over, green exactly on — an employee
     whose KRAs do not total 100 cannot be scored meaningfully. --}}
@php
    $value = round((float) $total, 2);
    $balanced = abs($value - 100) < 0.01;
    $over = $value > 100;
    $tone = $balanced ? 'success' : ($over ? 'danger' : 'warning');
@endphp

<div class="min-w-[120px]">
    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden">
        <div class="h-full rounded-full bg-{{ $tone }} transition-all" style="width: {{ min($value, 100) }}%"></div>
    </div>
    @if($showLabel)
        <div class="text-[11px] font-bold mt-1 text-{{ $tone }}">
            {{ rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') }}%
            @if($balanced) · balanced
            @elseif($over) · over by {{ rtrim(rtrim(number_format($value - 100, 2, '.', ''), '0'), '.') }}
            @else · {{ rtrim(rtrim(number_format(100 - $value, 2, '.', ''), '0'), '.') }} short
            @endif
        </div>
    @endif
</div>
