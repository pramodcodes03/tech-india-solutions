@props(['current' => 'assigned'])

{{-- Employee → Manager → HR → Admin, with everything before the current stage
     shown as done. One glance tells you whose desk a review is on. --}}
@php
    $stages = [
        'assigned' => ['Assigned', 'Goals set'],
        'self_submitted' => ['Self Assessment', 'Employee'],
        'manager_reviewed' => ['Manager Review', 'Manager'],
        'hr_reviewed' => ['HR Review', 'HR'],
        'finalized' => ['Finalized', 'Admin'],
    ];
    $order = array_keys($stages);
    $currentIndex = array_search($current === 'sent_back' ? 'assigned' : $current, $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
@endphp

<div class="flex items-center gap-1 flex-wrap">
    @foreach($stages as $key => [$title, $who])
        @php
            $i = array_search($key, $order, true);
            $done = $i < $currentIndex;
            $active = $i === $currentIndex;
        @endphp
        <div class="flex items-center gap-1">
            <div @class([
                'flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap',
                'bg-success/10 text-success' => $done,
                'bg-primary text-white shadow-sm' => $active && $current !== 'sent_back',
                'bg-warning text-white shadow-sm' => $active && $current === 'sent_back',
                'bg-gray-100 dark:bg-[#1b2e4b] text-gray-400' => ! $done && ! $active,
            ])>
                @if($done)
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @endif
                <span>{{ $current === 'sent_back' && $active ? 'Sent Back' : $title }}</span>
            </div>
            @if(! $loop->last)
                <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </div>
    @endforeach
</div>
