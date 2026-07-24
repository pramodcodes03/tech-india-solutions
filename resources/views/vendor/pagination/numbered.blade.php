@if ($paginator->hasPages())
    <nav class="flex items-center flex-wrap gap-1" role="navigation">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1 rounded-md text-xs text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-default select-none">« Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="px-3 py-1 rounded-md text-xs text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-[#1b2e4b]">« Prev</a>
        @endif

        {{-- Numbered links (Laravel's window already inserts "1 2 3 … last") --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 py-1 text-xs text-gray-400 select-none">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-1 rounded-md text-xs font-bold bg-primary text-white border border-primary select-none">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}"
                           class="px-3 py-1 rounded-md text-xs text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-[#1b2e4b]">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="px-3 py-1 rounded-md text-xs text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-[#1b2e4b]">Next »</a>
        @else
            <span class="px-3 py-1 rounded-md text-xs text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-default select-none">Next »</span>
        @endif
    </nav>
@endif
