{{--
    Inline searchable product dropdown for line-item rows.
    Works inside Alpine x-for loops.
    Usage: <x-admin.product-select />
    Parent scope must have: item.product_id, products[], selectProduct(index), index
--}}
<div
    x-data="{
        psOpen: false,
        psSearch: '',
        get psFiltered() {
            if (!this.psSearch) return products;
            const q = this.psSearch.toLowerCase();
            return products.filter(p => p.name.toLowerCase().includes(q));
        },
        get psLabel() {
            if (!item.product_id) return '';
            const p = products.find(p => String(p.id) === String(item.product_id));
            return p ? p.name : '';
        },
        psPick(product) {
            item.product_id = product.id;
            selectProduct(index);
            this.psSearch = '';
            this.psOpen = false;
        }
    }"
    x-on:click.outside="psOpen = false"
    class="relative"
>
    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id" />

    <button
        type="button"
        @click="psOpen = !psOpen"
        class="form-input w-full text-left flex items-center justify-between cursor-pointer text-sm"
        :class="psOpen ? 'border-primary ring-1 ring-primary' : ''"
    >
        <span :class="psLabel ? 'text-current' : 'text-gray-400 dark:text-gray-500'" x-text="psLabel || '-- Select Product --'"></span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform shrink-0 ml-1" :class="psOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div
        x-show="psOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full min-w-[220px] bg-white dark:bg-[#1b2e4b] border border-[#e0e6ed] dark:border-[#253b5e] rounded-md shadow-lg"
        style="display:none;"
    >
        <div class="p-2 border-b border-[#e0e6ed] dark:border-[#253b5e]">
            <input
                type="text"
                x-model="psSearch"
                x-on:click.stop
                @keydown.escape="psOpen = false"
                placeholder="Search product..."
                class="w-full px-3 py-1.5 text-sm border border-[#e0e6ed] dark:border-[#253b5e] rounded bg-white dark:bg-[#1b2e4b] focus:outline-none focus:border-primary"
                autocomplete="off"
            />
        </div>
        <ul class="max-h-52 overflow-y-auto py-1">
            <li @click="item.product_id = ''; selectProduct(index); psOpen = false"
                class="px-3 py-2 text-sm cursor-pointer text-gray-400 hover:bg-primary/10">
                -- Select Product --
            </li>
            <template x-for="product in psFiltered" :key="product.id">
                <li
                    @click="psPick(product)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-primary/10 dark:hover:bg-primary/20"
                    :class="String(product.id) === String(item.product_id) ? 'bg-primary/10 font-semibold text-primary' : ''"
                    x-text="product.name"
                ></li>
            </template>
            <li x-show="psFiltered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">No results</li>
        </ul>
    </div>
</div>
