@props([
    'name',
    'options',
    'selected' => null,
    'placeholder' => '-- Select --',
    'required' => false,
])

@php
    $selectedId   = old($name, $selected);
    $selectedLabel = '';
    foreach ($options as $opt) {
        $id  = is_array($opt) ? $opt['id']   : $opt->id;
        $lbl = is_array($opt) ? $opt['name'] : $opt->name;
        if ((string)$id === (string)$selectedId) {
            $selectedLabel = $lbl;
            break;
        }
    }
    $optionsJson = collect($options)->map(fn($o) => ['id' => (string)(is_array($o) ? $o['id'] : $o->id), 'name' => is_array($o) ? $o['name'] : $o->name])->values()->toJson();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selectedId: '{{ $selectedId }}',
        selectedLabel: '{{ addslashes($selectedLabel) }}',
        placeholder: '{{ $placeholder }}',
        options: {{ $optionsJson }},
        get filtered() {
            if (!this.search) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => o.name.toLowerCase().includes(q));
        },
        select(opt) {
            this.selectedId    = opt.id;
            this.selectedLabel = opt.name;
            this.search = '';
            this.open   = false;
        },
        clear() {
            this.selectedId    = '';
            this.selectedLabel = '';
            this.search = '';
        }
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selectedId" @if($required) x-bind:required="!selectedId" @endif>

    <button
        type="button"
        @click="open = !open"
        class="form-input w-full text-left flex items-center justify-between cursor-pointer"
        :class="open ? 'border-primary ring-1 ring-primary' : ''"
    >
        <span :class="selectedLabel ? 'text-current' : 'text-gray-400'" x-text="selectedLabel || placeholder"></span>
        <span class="flex items-center gap-1 ml-2 shrink-0">
            <span
                x-show="selectedId"
                @click.stop="clear()"
                class="text-gray-400 hover:text-danger cursor-pointer"
                title="Clear"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </span>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-white dark:bg-[#1b2e4b] border border-[#e0e6ed] dark:border-[#253b5e] rounded-md shadow-lg"
        style="display:none;"
    >
        <div class="p-2 border-b border-[#e0e6ed] dark:border-[#253b5e]">
            <input
                type="text"
                x-model="search"
                x-ref="searchInput"
                x-on:click.stop
                @keydown.escape="open = false"
                placeholder="Search..."
                class="w-full px-3 py-1.5 text-sm border border-[#e0e6ed] dark:border-[#253b5e] rounded bg-white dark:bg-[#1b2e4b] focus:outline-none focus:border-primary"
                autocomplete="off"
            >
        </div>

        <ul class="max-h-52 overflow-y-auto py-1" x-ref="listbox">
            <li
                @click="clear(); open = false"
                class="px-3 py-2 text-sm cursor-pointer text-gray-400 hover:bg-primary/10"
            >{{ $placeholder }}</li>

            <template x-for="opt in filtered" :key="opt.id">
                <li
                    @click="select(opt)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-primary/10 dark:hover:bg-primary/20"
                    :class="opt.id === selectedId ? 'bg-primary/10 dark:bg-primary/20 font-semibold text-primary' : ''"
                    x-text="opt.name"
                ></li>
            </template>

            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">No results found</li>
        </ul>
    </div>
</div>
