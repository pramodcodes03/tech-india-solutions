@props(['items' => []])

<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center flex-wrap gap-y-1 space-x-1 rtl:space-x-reverse text-sm">
        <li class="inline-flex items-center">
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-1 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-white transition-colors">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                </svg>
                <span>Home</span>
            </a>
        </li>

        @foreach($items as $item)
            <li class="inline-flex items-center">
                <svg class="w-3 h-3 text-gray-400 mx-0.5 rtl:rotate-180 shrink-0" fill="none" viewBox="0 0 6 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                </svg>
                @if(isset($item['url']))
                    <a href="{{ $item['url'] }}"
                       class="ml-1 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-white transition-colors">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="ml-1 font-medium text-primary dark:text-white">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
