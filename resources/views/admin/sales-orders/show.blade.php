<x-layout.admin>
    <div x-data="salesOrderShow">
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Sales Order Details</h5>
            <div class="flex items-center gap-2">
                @if(!in_array($salesOrder->status, ['delivered', 'cancelled']))
                    <a href="{{ route('admin.sales-orders.edit', $salesOrder->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
                <button type="button" class="btn btn-outline-success btn-sm" @click="generateInvoice()">Generate Invoice</button>
                <form action="{{ route('admin.sales-orders.destroy', $salesOrder->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
                <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Header Info --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Order Number</p>
                    <p class="font-semibold text-lg">{{ $salesOrder->order_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                    <p class="font-semibold">{{ $salesOrder->customer->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $statusColors = ['pending' => 'bg-warning', 'confirmed' => 'bg-info', 'processing' => 'bg-primary', 'shipped' => 'bg-secondary', 'delivered' => 'bg-success', 'cancelled' => 'bg-danger'];
                    @endphp
                    <span class="badge {{ $statusColors[$salesOrder->status] ?? 'bg-dark' }}">{{ ucfirst($salesOrder->status) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Order Date</p>
                    <p class="font-semibold">{{ $salesOrder->order_date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Grand Total</p>
                    <p class="font-bold text-lg text-primary">{{ number_format($salesOrder->grand_total, 2) }}</p>
                </div>
                @if($salesOrder->quotation)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">From Quotation</p>
                        <a href="{{ route('admin.quotations.show', $salesOrder->quotation_id) }}" class="font-semibold text-primary hover:underline">{{ $salesOrder->quotation->quotation_number }}</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Status Update --}}
        @if(!in_array($salesOrder->status, ['delivered', 'cancelled']))
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Update Status</h6>
                <form action="{{ route('admin.sales-orders.update-status', $salesOrder->id) }}" method="POST" class="flex items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="form-select w-48">
                        @php
                            $transitions = [
                                'pending' => ['confirmed', 'cancelled'],
                                'confirmed' => ['processing', 'cancelled'],
                                'processing' => ['shipped', 'cancelled'],
                                'shipped' => ['delivered'],
                            ];
                            $available = $transitions[$salesOrder->status] ?? [];
                        @endphp
                        @foreach($available as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                </form>
            </div>
        @endif

        {{-- Items Table --}}
        <div class="panel mb-5">
            <h6 class="text-base font-semibold mb-4">Line Items</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Description</th>
                            <th class="px-4 py-2">HSN Code</th>
                            <th class="px-4 py-2 text-right">Qty</th>
                            <th class="px-4 py-2">Unit</th>
                            <th class="px-4 py-2 text-right">Rate</th>
                            <th class="px-4 py-2 text-right">Disc%</th>
                            <th class="px-4 py-2 text-right">Tax%</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesOrder->items as $index => $item)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $item->description }}</td>
                                <td class="px-4 py-2">{{ $item->hsn_code ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                                <td class="px-4 py-2">{{ ucfirst($item->unit) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($item->rate, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->discount_percent ?? 0 }}%</td>
                                <td class="px-4 py-2 text-right">{{ $item->tax_percent ?? 0 }}%</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-4 text-center text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div></div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-semibold">{{ number_format($salesOrder->subtotal, 2) }}</span>
                    </div>
                    @if($salesOrder->discount_value > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                Discount
                                @if($salesOrder->discount_type === 'percent')
                                    ({{ $salesOrder->discount_value }}%)
                                @endif
                            </span>
                            <span class="font-semibold text-danger">
                                - {{ number_format($salesOrder->discount_type === 'percent' ? ($salesOrder->subtotal * $salesOrder->discount_value / 100) : $salesOrder->discount_value, 2) }}
                            </span>
                        </div>
                    @endif
                    @if($salesOrder->tax_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tax ({{ $salesOrder->tax_percent ?? 0 }}%)</span>
                            <span class="font-semibold">+ {{ number_format($salesOrder->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold">Grand Total</span>
                        <span class="font-bold text-primary">{{ number_format($salesOrder->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Related Invoices --}}
        @if($salesOrder->invoices && $salesOrder->invoices->count() > 0)
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Related Invoices</h6>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Invoice #</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrder->invoices as $index => $invoice)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 font-semibold">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ $invoice->invoice_date }}</td>
                                    <td class="px-4 py-2">
                                        @php
                                            $invStatusColors = ['unpaid' => 'bg-danger', 'partial' => 'bg-warning', 'paid' => 'bg-success', 'overdue' => 'bg-danger', 'cancelled' => 'bg-dark'];
                                        @endphp
                                        <span class="badge {{ $invStatusColors[$invoice->status] ?? 'bg-dark' }}">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ number_format($invoice->grand_total, 2) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($salesOrder->terms || $salesOrder->notes)
            <div class="panel mb-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @if($salesOrder->terms)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Terms & Conditions</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $salesOrder->terms }}</p>
                        </div>
                    @endif
                    @if($salesOrder->notes)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Notes</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $salesOrder->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('salesOrderShow', () => ({
                generateInvoice() {
                    const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-success', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
                    swalWithButtons.fire({ title: 'Generate Invoice?', text: 'This will create a new invoice from this sales order.', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, generate!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ route('admin.sales-orders.generate-invoice', $salesOrder->id) }}`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    this.showMessage(data.message);
                                    if (data.redirect) window.location.href = data.redirect;
                                    else window.location.reload();
                                } else {
                                    this.showMessage(data.message || 'Failed to generate invoice.', 'error');
                                }
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

        function confirmDelete(form) {
            const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
            swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    </script>
</x-layout.admin>
