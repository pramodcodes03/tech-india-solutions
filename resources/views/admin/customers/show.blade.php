<x-layout.admin>
    <div x-data="{ activeTab: 'quotations' }">
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Customer Details</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back to List
                </a>
            </div>
        </div>

        {{-- Customer Info Panel --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Code</label>
                    <p class="text-base dark:text-white-light">{{ $customer->code ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-base dark:text-white-light">{{ $customer->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Company</label>
                    <p class="text-base dark:text-white-light">{{ $customer->company ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-base dark:text-white-light">{{ $customer->email ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Phone</label>
                    <p class="text-base dark:text-white-light">{{ $customer->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">GST Number</label>
                    <p class="text-base dark:text-white-light">{{ $customer->gst_number ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Billing Address</label>
                    <p class="text-base dark:text-white-light">{{ $customer->billing_address ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Shipping Address</label>
                    <p class="text-base dark:text-white-light">{{ $customer->shipping_address ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">City / State / Pincode</label>
                    <p class="text-base dark:text-white-light">{{ collect([$customer->city, $customer->state, $customer->pincode])->filter()->implode(', ') ?: '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Country</label>
                    <p class="text-base dark:text-white-light">{{ $customer->country ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Credit Limit</label>
                    <p class="text-base dark:text-white-light">{{ $customer->credit_limit ? number_format($customer->credit_limit, 2) : '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p><span class="badge {{ $customer->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($customer->status) }}</span></p>
                </div>
            </div>
            @if($customer->notes)
                <div class="mt-4">
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Notes</label>
                    <p class="text-base dark:text-white-light">{{ $customer->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="panel px-0">
            <div class="flex border-b border-[#e0e6ed] dark:border-[#1b2e4b] px-4">
                <button type="button" class="px-4 py-2 -mb-px text-sm font-semibold border-b-2 transition-colors"
                    :class="activeTab === 'quotations' ? 'border-primary text-primary' : 'border-transparent hover:text-primary'"
                    @click="activeTab = 'quotations'">Quotations</button>
                <button type="button" class="px-4 py-2 -mb-px text-sm font-semibold border-b-2 transition-colors"
                    :class="activeTab === 'sales_orders' ? 'border-primary text-primary' : 'border-transparent hover:text-primary'"
                    @click="activeTab = 'sales_orders'">Sales Orders</button>
                <button type="button" class="px-4 py-2 -mb-px text-sm font-semibold border-b-2 transition-colors"
                    :class="activeTab === 'invoices' ? 'border-primary text-primary' : 'border-transparent hover:text-primary'"
                    @click="activeTab = 'invoices'">Invoices</button>
                <button type="button" class="px-4 py-2 -mb-px text-sm font-semibold border-b-2 transition-colors"
                    :class="activeTab === 'payments' ? 'border-primary text-primary' : 'border-transparent hover:text-primary'"
                    @click="activeTab = 'payments'">Payments</button>
                <button type="button" class="px-4 py-2 -mb-px text-sm font-semibold border-b-2 transition-colors"
                    :class="activeTab === 'service_tickets' ? 'border-primary text-primary' : 'border-transparent hover:text-primary'"
                    @click="activeTab = 'service_tickets'">Service Tickets</button>
            </div>

            {{-- Quotations Tab --}}
            <div x-show="activeTab === 'quotations'" class="p-4">
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Quotation No</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->quotations ?? [] as $index => $quotation)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $quotation->quotation_number }}</td>
                                    <td class="px-4 py-2">{{ $quotation->date?->format('d M Y') }}</td>
                                    <td class="px-4 py-2">{{ number_format($quotation->total_amount, 2) }}</td>
                                    <td class="px-4 py-2"><span class="badge bg-primary">{{ ucfirst($quotation->status) }}</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.quotations.show', $quotation->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No quotations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sales Orders Tab --}}
            <div x-show="activeTab === 'sales_orders'" class="p-4">
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Order No</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->salesOrders ?? [] as $index => $order)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $order->order_number }}</td>
                                    <td class="px-4 py-2">{{ $order->date?->format('d M Y') }}</td>
                                    <td class="px-4 py-2">{{ number_format($order->total_amount, 2) }}</td>
                                    <td class="px-4 py-2"><span class="badge bg-primary">{{ ucfirst($order->status) }}</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.sales-orders.show', $order->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No sales orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Invoices Tab --}}
            <div x-show="activeTab === 'invoices'" class="p-4">
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Invoice No</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->invoices ?? [] as $index => $invoice)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ $invoice->date?->format('d M Y') }}</td>
                                    <td class="px-4 py-2">{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td class="px-4 py-2"><span class="badge bg-primary">{{ ucfirst($invoice->status) }}</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payments Tab --}}
            <div x-show="activeTab === 'payments'" class="p-4">
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Payment No</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Method</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->payments ?? [] as $index => $payment)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $payment->payment_number }}</td>
                                    <td class="px-4 py-2">{{ $payment->date?->format('d M Y') }}</td>
                                    <td class="px-4 py-2">{{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($payment->method ?? '-') }}</td>
                                    <td class="px-4 py-2"><span class="badge bg-success">{{ ucfirst($payment->status) }}</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">No payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Service Tickets Tab --}}
            <div x-show="activeTab === 'service_tickets'" class="p-4">
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Ticket No</th>
                                <th class="px-4 py-2">Subject</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Priority</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 !text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->serviceTickets ?? [] as $index => $ticket)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $ticket->ticket_number }}</td>
                                    <td class="px-4 py-2">{{ $ticket->subject }}</td>
                                    <td class="px-4 py-2">{{ $ticket->created_at?->format('d M Y') }}</td>
                                    <td class="px-4 py-2"><span class="badge bg-warning">{{ ucfirst($ticket->priority) }}</span></td>
                                    <td class="px-4 py-2"><span class="badge bg-info">{{ ucfirst($ticket->status) }}</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('admin.service-tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">No service tickets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layout.admin>
