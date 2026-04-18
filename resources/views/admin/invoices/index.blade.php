<x-layout.admin title="Invoices">
    <div x-data="invoiceList">
        <x-admin.breadcrumb :items="[['label' => 'Invoices']]" />

        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Invoices</h5>
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
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input type="date" class="form-input py-2 w-40" x-model="filterDateFrom" @change="fetchData(1)" placeholder="From" />
                <input type="date" class="form-input py-2 w-40" x-model="filterDateTo" @change="fetchData(1)" placeholder="To" />
                <button type="button" class="btn btn-outline-danger btn-sm" x-show="searchText || filterStatus || filterDateFrom || filterDateTo" @click="clearFilters()">Clear</button>
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Invoice
                </a>
            </div>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Invoice #</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Due Date</th>
                            <th class="px-4 py-2 text-right">Grand Total</th>
                            <th class="px-4 py-2 text-right">Amount Paid</th>
                            <th class="px-4 py-2 text-right">Balance</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-2" x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"></td>
                                <td class="px-4 py-2 font-semibold" x-text="item.invoice_number"></td>
                                <td class="px-4 py-2" x-text="item.customer ? item.customer.name : '-'"></td>
                                <td class="px-4 py-2" x-text="formatDate(item.invoice_date)"></td>
                                <td class="px-4 py-2" x-text="formatDate(item.due_date)"></td>
                                <td class="px-4 py-2 text-right" x-text="formatCurrency(item.grand_total)"></td>
                                <td class="px-4 py-2 text-right" x-text="formatCurrency(item.amount_paid)"></td>
                                <td class="px-4 py-2 text-right font-semibold" x-text="formatCurrency((item.grand_total || 0) - (item.amount_paid || 0))"></td>
                                <td class="px-4 py-2">
                                    <span class="badge" :class="statusBadge(item.status)" x-text="item.status"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="`{{ url('admin/invoices') }}/${item.id}`" class="btn btn-sm btn-outline-info p-1.5" data-tippy-content="View Details"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                        <a :href="`{{ url('admin/invoices') }}/${item.id}/edit`" class="btn btn-sm btn-outline-primary p-1.5" data-tippy-content="Edit"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                        <a :href="`{{ url('admin/invoices') }}/${item.id}/pdf`" class="btn btn-sm btn-outline-secondary p-1.5" target="_blank" data-tippy-content="Download PDF"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1.5" @click="deleteItem(item.id)" data-tippy-content="Delete"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <x-admin.empty-state
                                icon="invoices"
                                title="No invoices yet"
                                description="Generate an invoice from a sales order or create one manually."
                                action-url="{{ route('admin.invoices.create') }}"
                                action-label="Create Invoice"
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
            Alpine.data('invoiceList', () => ({
                items: @json($invoices->items()),
                pagination: {
                    total: {{ $invoices->total() }},
                    per_page: {{ $invoices->perPage() }},
                    current_page: {{ $invoices->currentPage() }},
                    last_page: {{ $invoices->lastPage() }},
                    from: {{ $invoices->firstItem() ?? 0 }},
                    to: {{ $invoices->lastItem() ?? 0 }}
                },
                searchText: '',
                filterStatus: '',
                filterDateFrom: '',
                filterDateTo: '',

                fetchData(page = 1) {
                    let url = `{{ route('admin.invoices.index') }}?page=${page}`;
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
                    const map = { unpaid: 'bg-danger', partial: 'bg-warning', paid: 'bg-success', overdue: 'bg-danger', cancelled: 'bg-dark' };
                    return map[status] || 'bg-dark';
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                },

                deleteItem(id) {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('admin/invoices') }}/${id}`, {
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
