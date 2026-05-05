@props([
    'state' => null,
    'city' => null,
    'stateName' => 'state',
    'cityName' => 'city',
    'required' => false,
    'stateLabel' => 'State',
    'cityLabel' => 'City',
])

@php
    $allCities = \App\Models\City::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'state'])
        ->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name, 'state' => $c->state ?? ''])
        ->values();

    $selectedState = old($stateName, $state) ?? '';
    $selectedCity  = old($cityName, $city) ?? '';
@endphp

<div x-data="{
        city: @js($selectedCity),
        state: @js($selectedState),
        allCities: @js($allCities),
        cityOpen: false,
        citySearch: '',
        get filteredCities() {
            const q = this.citySearch.trim().toLowerCase();
            if (!q) return this.allCities;
            return this.allCities.filter(c =>
                c.name.toLowerCase().includes(q) ||
                (c.state && c.state.toLowerCase().includes(q))
            );
        },
        pickCity(c) {
            this.city = c.name;
            this.state = c.state || '';
            this.cityOpen = false;
            this.citySearch = '';
        },
        clearCity() {
            this.city = '';
            this.state = '';
            this.citySearch = '';
        }
     }"
     class="contents">
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">
            {{ $cityLabel }}@if($required) <span class="text-danger">*</span>@endif
        </label>
        <div class="relative mt-1" @click.outside="cityOpen = false">
            <input type="hidden" name="{{ $cityName }}" :value="city" @if($required) x-bind:required="!city" @endif />
            <button type="button"
                    @click="cityOpen = !cityOpen; if (cityOpen) $nextTick(() => $refs.citySearchBox && $refs.citySearchBox.focus())"
                    class="form-input w-full text-left flex items-center justify-between cursor-pointer"
                    :class="cityOpen ? 'border-primary ring-1 ring-primary' : ''">
                <span :class="city ? 'text-current' : 'text-gray-400'" x-text="city || '-- Select City --'"></span>
                <span class="flex items-center gap-1 ml-2 shrink-0">
                    <svg x-show="city" @click.stop="clearCity()" class="w-4 h-4 text-gray-400 hover:text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="cityOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </button>
            <div x-show="cityOpen" x-cloak x-transition
                 class="absolute z-50 mt-1 w-full bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-72 overflow-hidden flex flex-col"
                 style="display:none">
                <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                    <input type="text"
                           x-model="citySearch"
                           x-ref="citySearchBox"
                           @keydown.escape="cityOpen = false"
                           class="form-input w-full text-sm"
                           placeholder="Type to search cities..."
                           autocomplete="off" />
                </div>
                <ul class="overflow-y-auto flex-1">
                    <template x-for="c in filteredCities" :key="c.id">
                        <li @click="pickCity(c)"
                            class="px-3 py-2 cursor-pointer hover:bg-primary/10 flex items-center justify-between"
                            :class="city === c.name ? 'bg-primary/5 font-semibold text-primary' : ''">
                            <span x-text="c.name"></span>
                            <span class="text-xs text-gray-400" x-text="c.state"></span>
                        </li>
                    </template>
                    <li x-show="filteredCities.length === 0" class="px-3 py-4 text-center text-sm text-gray-500">
                        No cities match "<span x-text="citySearch"></span>".
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">
            {{ $stateLabel }}@if($required) <span class="text-danger">*</span>@endif
        </label>
        <input type="text"
               name="{{ $stateName }}"
               x-model="state"
               readonly
               class="form-input mt-1 bg-gray-50 dark:bg-dark-light/20"
               placeholder="— Auto-fills after city is selected —" />
    </div>
</div>
