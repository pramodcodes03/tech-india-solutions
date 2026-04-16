<x-layout.admin>
    <div x-data="quotationList">
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Quotations</h5>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative">
                    <input type="text" placeholder="Search..."
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
                <select class="form-select py-2 w-48" x-model="filterStatus" @change="fetchData(1)">
                    <option value="">-- All Statuses --</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </select>
                <input type="date" class="form-input py-2 w-40" x-model="filterDateFrom" @change="fetchData(1)" placeholder="From" />
                <input type="date" class="form-input py-2 w-40" x-model="filterDateTo" @change="fetchData(1)" placeholder="To" />
                <button type="button" class="btn btn-outline-danger btn-sm" x-show="searchText || filterStatus || filterDateFrom || filterDateTo" @click="clearFilters()">Clear</button>
                <a href="{{ route('admin.quotations.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Quotation
                </a>
            </div>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Quotation #</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Valid Until</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 text-right">Grand Total</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-2" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                <td class="px-4 py-2 font-semibold" x-text="item.quotation_number"></td>
                                <td class="px-4 py-2" x-text="item.customer ? item.customer.name : '-'"></td>
                                <td class="px-4 py-2" x-text="item.quotation_date"></td>
                                <td class="px-4 py-2" x-text="item.valid_until || '-'"></td>
                                <td class="px-4 py-2">
                                    <span class="badge" :class="statusBadge(item.status)" x-text="item.status"></span>
                                </td>
                                <td class="px-4 py-2 text-right" x-text="formatCurrency(item.grand_total)"></td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="`{{ url('admin/quotations') }}/${item.id}`" class="btn btn-sm btn-outline-info">View</a>
                                        <a :href="`{{ url('admin/quotations') }}/${item.id}/edit`" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a :href="`{{ url('admin/quotations') }}/${item.id}/pdf`" class="btn btn-sm btn-outline-secondary" target="_blank">PDF</a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" @click="cloneItem(item.id)">Clone</button>
                                        <button type="button" class="btn btn-sm btn-outline-success" x-show="item.status === 'draft' || item.status === 'sent'" @click="convertToOrder(item.id)">Convert to Order</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteItem(item.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="8" class="px-4 py-4 text-center text-gray-500">No records found.</td>
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
            Alpine.data('quotationList', () => ({
                items: @json($quotations->items()),
                pagination: {
                    total: {{ $quotations->total() }},
                    per_page: {{ $quotations->perPage() }},
                    current_page: {{ $quotations->currentPage() }},
                    last_page: {{ $quotations->lastPage() }},
                    from: {{ $quotations->firstItem() ?? 0 }},
                    to: {{ $quotations->lastItem() ?? 0 }}
                },
                searchText: '',
                filterStatus: '',
                filterDateFrom: '',
                filterDateTo: '',

                fetchData(page = 1) {
                    let url = `{{ route('admin.quotations.index') }}?page=${page}`;
                    if (this.searchText) url += `&search=${encodeURIComponent(this.searchText)}`;
                    if (this.filterStatus) url += `&status=${this.filterStatus}`;
                    if (this.filterDateFrom) url += `&date_from=${this.filterDateFrom}`;
                    if (this.filterDateTo) url += `&date_to=${this.filterDateTo}`;
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
                    this.filterDateFrom = '';
                    this.filterDateTo = '';
                    this.fetchData(1);
                },

                statusBadge(status) {
                    const map = { draft: 'bg-dark', sent: 'bg-info', accepted: 'bg-success', rejected: 'bg-danger', expired: 'bg-warning' };
                    return map[status] || 'bg-dark';
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                },

                cloneItem(id) {
                    fetch(`{{ url('admin/quotations') }}/${id}/clone`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) { this.showMessage(data.message); this.fetchData(this.pagination.current_page); }
                        else { this.showMessage(data.message || 'Failed to clone.', 'error'); }
                    });
                },

                convertToOrder(id) {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-success', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Convert to Sales Order?', text: 'This will create a new sales order from this quotation.', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, convert!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('admin/quotations') }}/${id}/convert-to-order`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) { this.showMessage(data.message); this.fetchData(this.pagination.current_page); }
                                else { this.showMessage(data.message || 'Conversion failed.', 'error'); }
                            });
                        }
                    });
                },

                deleteItem(id) {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('admin/quotations') }}/${id}`, {
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
