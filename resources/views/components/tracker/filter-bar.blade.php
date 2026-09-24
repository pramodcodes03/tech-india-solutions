@props([
    'filter',
    'sorting' => ['sort' => null, 'dir' => null],
    'exportRoute' => null,
    'exportPermission' => null,
    'searchPlaceholder' => 'Search…',
    'showSearch' => true,
])

{{-- The Monthly / Date-wise / Yearly filter every tracker register shares.

     One GET form: the period buttons only swap which date input is visible, so
     whatever is on screen when "Apply" is pressed is exactly what the server
     filters on. Sort state rides along as hidden inputs so filtering never
     silently resets the column the user chose. --}}
<form method="GET" class="panel p-4 mb-4"
      x-data="{ period: '{{ $filter->mode }}' }">
    <input type="hidden" name="period" :value="period" />
    @if(($sorting['sort'] ?? null))
        <input type="hidden" name="sort" value="{{ $sorting['sort'] }}" />
        <input type="hidden" name="dir" value="{{ $sorting['dir'] }}" />
    @endif

    <div class="flex flex-wrap items-end gap-3">
        {{-- Period mode --}}
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Period</label>
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-[#253b5c] overflow-hidden">
                @foreach(\App\Support\TrackerFilter::MODES as $mode => $label)
                    <button type="button" @click="period = '{{ $mode }}'"
                        class="px-3 py-1.5 text-xs font-semibold transition-colors border-r last:border-r-0 border-gray-200 dark:border-[#253b5c]"
                        :class="period === '{{ $mode }}'
                            ? 'bg-primary text-white'
                            : 'bg-white dark:bg-[#1b2e4b] text-gray-500 hover:bg-primary/10 hover:text-primary'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- The date input for the selected mode --}}
        <div x-show="period === 'month'" x-cloak>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Month</label>
            <input type="month" name="month" value="{{ $filter->rawMonth ?: now()->format('Y-m') }}" class="form-input w-[170px]" />
        </div>
        <div x-show="period === 'date'" x-cloak>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Date</label>
            <input type="date" name="date" value="{{ $filter->rawDate ?: now()->toDateString() }}" class="form-input w-[170px]" />
        </div>
        <template x-if="period === 'range'">
            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">From</label>
                    <input type="date" name="from" value="{{ $filter->rawFrom }}" class="form-input w-[160px]" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">To</label>
                    <input type="date" name="to" value="{{ $filter->rawTo }}" class="form-input w-[160px]" />
                </div>
            </div>
        </template>
        <div x-show="period === 'year'" x-cloak>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Year</label>
            <select name="year" class="form-select w-[120px]">
                @for($y = (int) now()->format('Y'); $y >= (int) now()->format('Y') - 6; $y--)
                    <option value="{{ $y }}" @selected((int) ($filter->rawYear ?: now()->format('Y')) === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>

        {{-- Free-text search --}}
        @if($showSearch)
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ $searchPlaceholder }}" class="form-input w-full" />
        </div>
        @endif

        {{-- Register-specific dropdowns --}}
        {{ $slot }}

        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
        </div>

        @if($exportRoute && (! $exportPermission || auth('admin')->user()->can($exportPermission)))
            <div class="flex items-center gap-2 ltr:ml-auto rtl:mr-auto">
                <a href="{{ $exportRoute }}{{ str_contains($exportRoute, '?') ? '&' : '?' }}format=excel"
                   class="btn btn-outline-success gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Excel
                </a>
                <a href="{{ $exportRoute }}{{ str_contains($exportRoute, '?') ? '&' : '?' }}format=pdf"
                   class="btn btn-outline-danger gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" stroke-linejoin="round"/>
                        <path d="M14 3v5h5" stroke-linejoin="round"/>
                    </svg>
                    PDF
                </a>
            </div>
        @endif
    </div>

    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-[#1b2e4b] text-xs text-gray-500">
        Showing <span class="font-bold text-primary">{{ $filter->label() }}</span>
        @if(request('search'))
            · matching “<span class="font-semibold">{{ request('search') }}</span>”
        @endif
    </div>
</form>
