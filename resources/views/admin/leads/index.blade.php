<x-layout.admin title="Leads">
    @php $leadColspan = (auth('admin')->user()?->canany(['leads.edit','leads.delete'])) ? 16 : 15; @endphp
    <div x-data="leadList">
        <x-admin.breadcrumb :items="[['label' => 'Leads']]" />

        {{-- Header row: title + primary actions --}}
        <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
            <h5 class="text-lg font-semibold dark:text-white-light">Leads</h5>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.leads.kanban') }}" class="btn btn-outline-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Leads Board
                </a>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="btn btn-outline-success gap-2 whitespace-nowrap" @click="open = !open">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-10 mt-1 ltr:right-0 rtl:left-0 w-44 rounded-md bg-white dark:bg-[#1b2e4b] shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                        <a href="#" @click.prevent="open = false; window.location = exportUrl('xlsx')" class="block px-4 py-2 text-sm text-gray-700 dark:text-white-light hover:bg-gray-100 dark:hover:bg-gray-700">Excel (.xlsx)</a>
                        <a href="#" @click.prevent="open = false; window.location = exportUrl('csv')" class="block px-4 py-2 text-sm text-gray-700 dark:text-white-light hover:bg-gray-100 dark:hover:bg-gray-700">CSV (.csv)</a>
                    </div>
                </div>
                @can('leads.create')
                <a href="{{ route('admin.leads.import.form') }}" class="btn btn-outline-secondary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L7 8m4-4v12"/></svg>
                    Bulk Import
                </a>
                <a href="{{ route('admin.leads.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Lead
                </a>
                @endcan
            </div>
        </div>

        {{-- Filters row --}}
        <div class="panel px-4 py-3 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-3 items-end">
                <div class="relative xl:col-span-1">
                    <input type="text" placeholder="Search name/company/code/mobile..."
                        class="form-input py-2 w-full ltr:pr-11 rtl:pl-11 peer"
                        x-model="searchText"
                        @keyup.debounce.300ms="fetchData(1)" />
                    <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                        <svg class="mx-auto" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11.5" cy="11.5" r="9.5" stroke="currentColor" stroke-width="1.5" opacity="0.5"></circle>
                            <path d="M18.5 18.5L22 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
                <select class="form-select py-2 w-full" x-model="filterStatus" @change="fetchData(1)">
                    <option value="">-- All Status --</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal">Proposal</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                </select>
                <select class="form-select py-2 w-full" x-model="filterSource" @change="fetchData(1)">
                    <option value="">-- All Sources --</option>
                    @foreach($sources as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-select py-2 w-full" x-model="filterAssignedTo" @change="fetchData(1)">
                    <option value="">-- Assigned To --</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
                <select class="form-select py-2 w-full" x-model="filterCity" @change="fetchData(1)">
                    <option value="">-- All Cities --</option>
                    @foreach($cities as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
                {{-- Lead Received Date range. Empty fields = unbounded on that side. --}}
                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">Received from</label>
                    <input type="date" class="form-input py-2 w-full" x-model="filterFromDate" @change="fetchData(1)" />
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase block mb-1">Received to</label>
                    <input type="date" class="form-input py-2 w-full" x-model="filterToDate" @change="fetchData(1)" />
                </div>
            </div>
            <div class="mt-3" x-show="searchText || filterStatus || filterSource || filterAssignedTo || filterCity || filterFromDate || filterToDate" x-cloak>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="clearFilters()">Clear Filters</button>
            </div>
        </div>

        {{-- Bulk action bar — visible when at least one row is selected --}}
        @canany(['leads.edit','leads.delete'])
        <div class="panel px-4 py-3 mb-3 flex items-center gap-3 flex-wrap" x-show="selected.length > 0" x-cloak>
            <span class="text-sm font-semibold"><span x-text="selected.length"></span> selected</span>
            @can('leads.edit')
                <button type="button" class="btn btn-sm btn-primary" @click="openBulkEdit()">Bulk Edit</button>
            @endcan
            @can('leads.delete')
                <button type="button" class="btn btn-sm btn-outline-danger" @click="bulkDelete()">Delete Selected</button>
            @endcan
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearSelection()">Clear</button>
        </div>
        @endcanany

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            @canany(['leads.edit','leads.delete'])
                            <th class="px-4 py-2 w-8"><input type="checkbox" class="form-checkbox" @change="toggleAll($event)" :checked="allChecked" /></th>
                            @endcanany
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Code</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Mobile</th>
                            <th class="px-4 py-2">Company</th>
                            <th class="px-4 py-2">City / State</th>
                            <th class="px-4 py-2">Bid No.</th>
                            <th class="px-4 py-2">RA/EMD</th>
                            <th class="px-4 py-2">Source</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Assigned To</th>
                            <th class="px-4 py-2">Expected Value</th>
                            <th class="px-4 py-2">Lead Received Date</th>
                            <th class="px-4 py-2">Next Follow-up</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                @canany(['leads.edit','leads.delete'])
                                <td class="px-4 py-2"><input type="checkbox" class="form-checkbox" :value="item.id" x-model.number="selected" /></td>
                                @endcanany
                                <td class="px-4 py-2" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                <td class="px-4 py-2" x-text="item.code ? item.code.replace('LEAD-', '') : '-'"></td>
                                <td class="px-4 py-2" x-text="item.name"></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <template x-if="item.phone">
                                        <a :href="`tel:${item.phone}`" class="text-primary hover:underline" x-text="item.phone"></a>
                                    </template>
                                    <template x-if="!item.phone"><span>-</span></template>
                                </td>
                                <td class="px-4 py-2" x-text="item.company || '-'"></td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span x-text="item.city || '-'"></span>
                                    <template x-if="item.state">
                                        <span class="block text-xs text-gray-400" x-text="item.state"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap" x-text="item.bid_number || '-'"></td>
                                <td class="px-4 py-2 whitespace-nowrap" x-text="item.ra_emd || '-'"></td>
                                <td class="px-4 py-2">
                                    <span class="badge bg-secondary" x-text="sourceLabel(item.source)"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="relative" x-data="{ open: false }">
                                        <span class="badge cursor-pointer select-none" :class="getStatusClass(item.status)" x-text="item.status" @click="open = !open"></span>
                                        <div x-show="open" @click.outside="open = false" x-cloak
                                            class="absolute z-50 mt-1 bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded shadow-lg min-w-[120px]">
                                            <template x-for="s in ['new','contacted','qualified','proposal','won','lost']" :key="s">
                                                <button type="button"
                                                    class="block w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    :class="s === item.status ? 'font-bold' : ''"
                                                    @click="open = false; updateStatus(item.id, s)"
                                                    x-text="s.charAt(0).toUpperCase() + s.slice(1)">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2" x-text="item.assigned_to_name || '-'"></td>
                                <td class="px-4 py-2" x-text="item.expected_value ? '₹' + parseFloat(item.expected_value).toLocaleString('en-IN') : '-'"></td>
                                <td class="px-4 py-2 whitespace-nowrap" x-text="formatDate(item.received_date)"></td>
                                <td class="px-4 py-2" x-text="formatDate(item.next_follow_up)"></td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="`{{ url('admin/leads') }}/${item.id}`" class="btn btn-sm btn-outline-info p-1.5" data-tippy-content="View Details"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                        @can('leads.edit')<a :href="`{{ url('admin/leads') }}/${item.id}/edit`" class="btn btn-sm btn-outline-primary p-1.5" data-tippy-content="Edit"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>@endcan
                                        @can('leads.convert')
                                        <template x-if="item.status !== 'won' && item.status !== 'lost'">
                                            <button type="button" class="btn btn-sm btn-outline-success p-1.5" data-tippy-content="Convert to Customer" @click="convertToCustomer(item.id)"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></button>
                                        </template>
                                        @endcan
                                        @can('leads.delete')<button type="button" class="btn btn-sm btn-outline-danger p-1.5" @click="deleteItem(item.id)" data-tippy-content="Delete"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>@endcan
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <x-admin.empty-state
                                icon="leads"
                                title="No leads yet"
                                description="Start tracking your sales pipeline by adding your first lead."
                                action-url="{{ route('admin.leads.create') }}"
                                action-label="Add Lead"
                                :colspan="$leadColspan"
                            />
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span>
                        Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> entries
                    </span>
                    <span class="flex items-center gap-1">
                        <span>Show</span>
                        <select class="form-select form-select-sm w-auto py-1" x-model.number="perPage" @change="fetchData(1)">
                            @foreach($pageSizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </span>
                </div>
                <div class="flex flex-wrap gap-1" x-show="pagination.last_page > 1">
                    <button type="button" class="btn btn-sm btn-outline-primary px-2.5" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" :class="pagination.current_page === 1 && 'opacity-40 cursor-not-allowed'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm px-3 min-w-[2rem]" :class="1 === pagination.current_page ? 'btn-primary' : 'btn-outline-primary'" @click="changePage(1)">1</button>
                    <span x-show="pagination.current_page > 3" class="flex items-center px-2 text-gray-400">...</span>
                    <template x-for="page in getVisiblePages()" :key="page">
                        <button type="button" class="btn btn-sm px-3 min-w-[2rem]" :class="page === pagination.current_page ? 'btn-primary' : 'btn-outline-primary'" @click="changePage(page)" x-text="page"></button>
                    </template>
                    <span x-show="pagination.current_page < pagination.last_page - 2" class="flex items-center px-2 text-gray-400">...</span>
                    <button type="button" class="btn btn-sm px-3 min-w-[2rem]" x-show="pagination.last_page > 1" :class="pagination.last_page === pagination.current_page ? 'btn-primary' : 'btn-outline-primary'" @click="changePage(pagination.last_page)" x-text="pagination.last_page"></button>
                    <button type="button" class="btn btn-sm btn-outline-primary px-2.5" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" :class="pagination.current_page === pagination.last_page && 'opacity-40 cursor-not-allowed'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ───────────── Bulk Edit Modal (dropdowns only) ───────────── --}}
        @can('leads.edit')
        <div x-show="bulkEditOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden" @click.outside="bulkEditOpen = false">
                <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b dark:border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold leading-tight">Bulk Edit</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Editing <span class="font-semibold text-primary" x-text="selected.length"></span> selected lead(s). Only the dropdowns you change are applied.</p>
                    </div>
                    <button type="button" @click="bulkEditOpen = false" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-5 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</label>
                        <select class="form-select mt-1" x-model="bulkEdit.status">
                            <option value="">— No change —</option>
                            @foreach(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','won'=>'Won','lost'=>'Lost'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Source</label>
                        <select class="form-select mt-1" x-model="bulkEdit.source">
                            <option value="">— No change —</option>
                            @foreach($sources as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Assigned To</label>
                        <select class="form-select mt-1" x-model="bulkEdit.assigned_to">
                            <option value="">— No change —</option>
                            @foreach($admins as $admin)<option value="{{ $admin->id }}">{{ $admin->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Product</label>
                        <select class="form-select mt-1" x-model="bulkEdit.product_id">
                            <option value="">— No change —</option>
                            @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                    <span class="text-xs text-gray-400"><span x-text="bulkEditChangedCount()"></span> field(s) will change</span>
                    <div class="flex gap-2">
                        <button type="button" @click="bulkEditOpen = false" class="btn btn-outline-secondary">Cancel</button>
                        <button type="button" class="btn btn-primary" :disabled="bulkEditChangedCount() === 0" @click="applyBulkEdit()">Apply Changes</button>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('leadList', () => ({
                items: @json($items),
                pagination: {
                    total: {{ $leads->total() }},
                    per_page: {{ $leads->perPage() }},
                    current_page: {{ $leads->currentPage() }},
                    last_page: {{ $leads->lastPage() }},
                    from: {{ $leads->firstItem() ?? 0 }},
                    to: {{ $leads->lastItem() ?? 0 }}
                },
                searchText: '',
                filterStatus: '',
                filterSource: '',
                filterAssignedTo: '',
                filterCity: '',
                filterFromDate: '',
                filterToDate: '',
                perPage: {{ $leads->perPage() }},
                sourceMap: @json($sources),

                // ── Bulk selection / edit state ──
                selected: [],
                bulkEditOpen: false,
                bulkEdit: { status: '', source: '', assigned_to: '', product_id: '' },

                get allChecked() {
                    return this.items.length > 0 && this.selected.length === this.items.length;
                },
                toggleAll(e) {
                    this.selected = e.target.checked ? this.items.map(i => i.id) : [];
                },
                clearSelection() {
                    this.selected = [];
                },
                bulkEditChangedCount() {
                    return Object.values(this.bulkEdit).filter(v => v !== '' && v !== null).length;
                },
                openBulkEdit() {
                    if (!this.selected.length) return;
                    this.bulkEdit = { status: '', source: '', assigned_to: '', product_id: '' };
                    this.bulkEditOpen = true;
                },
                applyBulkEdit() {
                    if (this.bulkEditChangedCount() === 0) return;
                    const payload = { ids: this.selected };
                    Object.keys(this.bulkEdit).forEach(k => { if (this.bulkEdit[k] !== '') payload[k] = this.bulkEdit[k]; });
                    fetch(`{{ route('admin.leads.bulk-update') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.showMessage(data.message);
                            this.bulkEditOpen = false;
                            this.clearSelection();
                            this.fetchData(this.pagination.current_page);
                        } else { this.showMessage(data.message || 'Update failed', 'error'); }
                    });
                },
                bulkDelete() {
                    if (!this.selected.length) return;
                    const count = this.selected.length;
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Delete ' + count + ' lead(s)?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ route('admin.leads.bulk-delete') }}`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                body: JSON.stringify({ ids: this.selected })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) { this.showMessage(data.message); this.clearSelection(); this.fetchData(this.pagination.current_page); }
                                else { this.showMessage(data.message || 'Delete failed', 'error'); }
                            });
                        }
                    });
                },
                sourceLabel(value) {
                    if (!value) return '—';
                    return this.sourceMap[value] || value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                },

                fetchData(page = 1) {
                    let url = `{{ route('admin.leads.index') }}?page=${page}&per_page=${this.perPage}`;
                    if (this.searchText) url += `&search=${encodeURIComponent(this.searchText)}`;
                    if (this.filterStatus) url += `&status=${this.filterStatus}`;
                    if (this.filterSource) url += `&source=${encodeURIComponent(this.filterSource)}`;
                    if (this.filterAssignedTo) url += `&assigned_to=${this.filterAssignedTo}`;
                    if (this.filterCity) url += `&city=${encodeURIComponent(this.filterCity)}`;
                    if (this.filterFromDate) url += `&from_date=${this.filterFromDate}`;
                    if (this.filterToDate)   url += `&to_date=${this.filterToDate}`;
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                    .then(res => res.json())
                    .then(data => { this.items = data.data; this.pagination = data.pagination; this.selected = []; });
                },

                changePage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) this.fetchData(page);
                },

                // Build the Export URL carrying the CURRENT filters so the
                // downloaded file matches what's on screen. format = xlsx | csv.
                exportUrl(format) {
                    let url = `{{ route('admin.leads.export') }}?format=${format}`;
                    if (this.searchText) url += `&search=${encodeURIComponent(this.searchText)}`;
                    if (this.filterStatus) url += `&status=${this.filterStatus}`;
                    if (this.filterSource) url += `&source=${encodeURIComponent(this.filterSource)}`;
                    if (this.filterAssignedTo) url += `&assigned_to=${this.filterAssignedTo}`;
                    if (this.filterCity) url += `&city=${encodeURIComponent(this.filterCity)}`;
                    if (this.filterFromDate) url += `&from_date=${this.filterFromDate}`;
                    if (this.filterToDate)   url += `&to_date=${this.filterToDate}`;
                    return url;
                },

                getVisiblePages() {
                    const current = this.pagination.current_page, last = this.pagination.last_page, pages = [];
                    let start = Math.max(2, current - 1), end = Math.min(last - 1, current + 1);
                    if (current <= 3) end = Math.min(4, last - 1);
                    if (current >= last - 2) start = Math.max(2, last - 3);
                    for (let i = start; i <= end; i++) pages.push(i);
                    return pages;
                },

                clearFilters() {
                    this.searchText = '';
                    this.filterStatus = '';
                    this.filterSource = '';
                    this.filterAssignedTo = '';
                    this.filterCity = '';
                    this.filterFromDate = '';
                    this.filterToDate = '';
                    this.fetchData(1);
                },

                getStatusClass(status) {
                    const classes = {
                        'new': 'bg-info',
                        'contacted': 'bg-warning',
                        'qualified': 'bg-primary',
                        'proposal': 'bg-secondary',
                        'won': 'bg-success',
                        'lost': 'bg-danger'
                    };
                    return classes[status] || 'bg-primary';
                },

                updateStatus(id, status) {
                    fetch(`{{ url('admin/leads') }}/${id}/status`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ status })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { this.showMessage(data.message); this.fetchData(this.pagination.current_page); }
                        else { this.showMessage(data.message || 'Failed to update status', 'error'); }
                    });
                },

                convertToCustomer(id) {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-success', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Convert to Customer?', text: 'This lead will be marked as Won and a customer record will be created.', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, convert!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('admin/leads') }}/${id}/convert`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => { if (res.redirected) { window.location.href = res.url; } else { return res.json(); } })
                            .then(data => { if (data && !data.success) { this.showMessage(data.message || 'Conversion failed', 'error'); } });
                        }
                    });
                },

                deleteItem(id) {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('admin/leads') }}/${id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) { this.showMessage(data.message); this.fetchData(this.pagination.current_page); }
                                else { this.showMessage(data.message, 'error'); }
                            });
                        }
                    });
                },

                showMessage(msg = '', type = 'success') {
                    const toast = window.Swal.mixin({ toast: true, position: 'top', showConfirmButton: false, timer: 3000 });
                    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
                }
            }));
        });
    </script>
</x-layout.admin>
