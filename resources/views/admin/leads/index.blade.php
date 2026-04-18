<x-layout.admin title="Leads">
    <div x-data="leadList">
        <x-admin.breadcrumb :items="[['label' => 'Leads']]" />

        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Leads</h5>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative">
                    <input type="text" placeholder="Search by name/company/code..."
                        class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                        x-model="searchText"
                        @keyup.debounce.300ms="fetchData(1)" />
                    <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                        <svg class="mx-auto" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11.5" cy="11.5" r="9.5" stroke="currentColor" stroke-width="1.5" opacity="0.5"></circle>
                            <path d="M18.5 18.5L22 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>
                <select class="form-select py-2 w-40" x-model="filterStatus" @change="fetchData(1)">
                    <option value="">-- All Status --</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal">Proposal</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                </select>
                <select class="form-select py-2 w-40" x-model="filterSource" @change="fetchData(1)">
                    <option value="">-- All Sources --</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}">{{ ucfirst($source) }}</option>
                    @endforeach
                </select>
                <select class="form-select py-2 w-48" x-model="filterAssignedTo" @change="fetchData(1)">
                    <option value="">-- Assigned To --</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-danger btn-sm" x-show="searchText || filterStatus || filterSource || filterAssignedTo" @click="clearFilters()">Clear</button>
                <a href="{{ route('admin.leads.kanban') }}" class="btn btn-outline-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Kanban View
                </a>
                <a href="{{ route('admin.leads.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Lead
                </a>
            </div>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Code</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Company</th>
                            <th class="px-4 py-2">Source</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Assigned To</th>
                            <th class="px-4 py-2">Expected Value</th>
                            <th class="px-4 py-2">Next Follow-up</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-2" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                <td class="px-4 py-2" x-text="item.code || '-'"></td>
                                <td class="px-4 py-2" x-text="item.name"></td>
                                <td class="px-4 py-2" x-text="item.company || '-'"></td>
                                <td class="px-4 py-2">
                                    <span class="badge bg-secondary" x-text="item.source"></span>
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
                                <td class="px-4 py-2" x-text="formatDate(item.next_follow_up)"></td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="`{{ url('admin/leads') }}/${item.id}`" class="btn btn-sm btn-outline-info p-1.5" data-tippy-content="View Details"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                        <a :href="`{{ url('admin/leads') }}/${item.id}/edit`" class="btn btn-sm btn-outline-primary p-1.5" data-tippy-content="Edit"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                        <template x-if="item.status !== 'won' && item.status !== 'lost'">
                                            <button type="button" class="btn btn-sm btn-outline-success p-1.5" data-tippy-content="Convert to Customer" @click="convertToCustomer(item.id)"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></button>
                                        </template>
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1.5" @click="deleteItem(item.id)" data-tippy-content="Delete"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
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
                                :colspan="10"
                            />
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3" x-show="pagination.last_page > 1">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> entries
                </div>
                <div class="flex flex-wrap gap-1">
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
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('leadList', () => ({
                items: @json($leads->items()),
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

                fetchData(page = 1) {
                    let url = `{{ route('admin.leads.index') }}?page=${page}`;
                    if (this.searchText) url += `&search=${encodeURIComponent(this.searchText)}`;
                    if (this.filterStatus) url += `&status=${this.filterStatus}`;
                    if (this.filterSource) url += `&source=${encodeURIComponent(this.filterSource)}`;
                    if (this.filterAssignedTo) url += `&assigned_to=${this.filterAssignedTo}`;
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.json())
                    .then(data => { this.items = data.data; this.pagination = data.pagination; });
                },

                changePage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) this.fetchData(page);
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
