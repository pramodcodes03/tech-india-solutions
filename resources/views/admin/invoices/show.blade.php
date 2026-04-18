<x-layout.admin title="Invoice Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Invoices','url'=>route('admin.invoices.index')],['label'=>'Invoice Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Invoice Details</h5>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                <a href="{{ route('admin.invoices.pdf', $invoice->id) }}" class="btn btn-outline-secondary btn-sm" target="_blank" data-tippy-content="Download PDF">PDF Download</a>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm" data-tippy-content="Print this invoice">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    <a href="{{ route('admin.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline-success btn-sm">Record Payment</a>
                @endif
                <form action="{{ route('admin.invoices.destroy', $invoice->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Header Info --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Number</p>
                    <p class="font-semibold text-lg">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                    <p class="font-semibold">{{ $invoice->customer->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $statusColors = ['unpaid' => 'bg-danger', 'partial' => 'bg-warning', 'paid' => 'bg-success', 'overdue' => 'bg-danger', 'cancelled' => 'bg-dark'];
                    @endphp
                    <span class="badge {{ $statusColors[$invoice->status] ?? 'bg-dark' }}">{{ ucfirst($invoice->status) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Date</p>
                    <p class="font-semibold">@formatDate($invoice->invoice_date)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Due Date</p>
                    <p class="font-semibold">@formatDate($invoice->due_date)</p>
                </div>
                @if($invoice->salesOrder)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">From Sales Order</p>
                        <a href="{{ route('admin.sales-orders.show', $invoice->sales_order_id) }}" class="font-semibold text-primary hover:underline">{{ $invoice->salesOrder->order_number }}</a>
                    </div>
                @endif
            </div>

            {{-- Payment Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Grand Total</p>
                    <p class="font-bold text-lg">{{ number_format($invoice->grand_total, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount Paid</p>
                    <p class="font-bold text-lg text-success">{{ number_format($invoice->amount_paid, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Balance Due</p>
                    <p class="font-bold text-lg text-danger">{{ number_format(($invoice->grand_total - $invoice->amount_paid), 2) }}</p>
                </div>
            </div>
        </div>

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
                        @forelse($invoice->items as $index => $item)
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
                        <span class="font-semibold">{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->discount_value > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                Discount
                                @if($invoice->discount_type === 'percent')
                                    ({{ $invoice->discount_value }}%)
                                @endif
                            </span>
                            <span class="font-semibold text-danger">
                                - {{ number_format($invoice->discount_type === 'percent' ? ($invoice->subtotal * $invoice->discount_value / 100) : $invoice->discount_value, 2) }}
                            </span>
                        </div>
                    @endif
                    @if($invoice->tax_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tax ({{ $invoice->tax_percent ?? 0 }}%)</span>
                            <span class="font-semibold">+ {{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold">Grand Total</span>
                        <span class="font-bold text-primary">{{ number_format($invoice->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        <div class="panel mb-5">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-base font-semibold">Payment History</h6>
                @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    <a href="{{ route('admin.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-success btn-sm gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Record Payment
                    </a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Payment #</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Mode</th>
                            <th class="px-4 py-2">Reference</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments ?? [] as $index => $payment)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $payment->payment_number }}</td>
                                <td class="px-4 py-2">@formatDate($payment->payment_date)</td>
                                <td class="px-4 py-2">{{ ucfirst(str_replace('_', ' ', $payment->mode)) }}</td>
                                <td class="px-4 py-2">{{ $payment->reference_number ?? '-' }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes --}}
        @if($invoice->terms || $invoice->notes)
            <div class="panel mb-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @if($invoice->terms)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Terms & Conditions</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $invoice->terms }}</p>
                        </div>
                    @endif
                    @if($invoice->notes)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Notes</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script>
        function confirmDelete(form) {
            const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
            swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    </script>
</x-layout.admin>
