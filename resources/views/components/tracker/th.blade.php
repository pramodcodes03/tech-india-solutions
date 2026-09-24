@props(['label', 'column', 'sorting', 'align' => 'left'])

{{-- A sortable column header. Clicking toggles asc/desc on that column and
     keeps every other query parameter (period, search, filters) intact. --}}
@php
    $active = ($sorting['sort'] ?? null) === $column;
    $nextDir = $active && ($sorting['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc';
    $href = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => 1]);
@endphp

<th class="whitespace-nowrap {{ $align === 'right' ? 'text-right' : '' }}">
    <a href="{{ $href }}"
       class="inline-flex items-center gap-1 group {{ $active ? 'text-primary' : 'hover:text-primary' }}">
        <span>{{ $label }}</span>
        <span class="inline-flex flex-col leading-none {{ $active ? 'opacity-100' : 'opacity-30 group-hover:opacity-70' }}">
            <svg class="w-2.5 h-2.5 {{ $active && ($sorting['dir'] ?? '') === 'asc' ? 'text-primary' : '' }}" viewBox="0 0 10 6" fill="currentColor"><path d="M5 0L10 6H0z"/></svg>
            <svg class="w-2.5 h-2.5 -mt-[2px] {{ $active && ($sorting['dir'] ?? '') === 'desc' ? 'text-primary' : '' }}" viewBox="0 0 10 6" fill="currentColor"><path d="M5 6L0 0h10z"/></svg>
        </span>
    </a>
</th>
