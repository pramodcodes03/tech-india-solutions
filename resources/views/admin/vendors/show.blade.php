<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Vendor Details</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back to List
                </a>
            </div>
        </div>

        {{-- Vendor Info Panel --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Code</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->code ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Company</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->company ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->email ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Phone</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">GST Number</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->gst_number ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Address</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->address ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">City / State / Pincode</label>
                    <p class="text-base dark:text-white-light">{{ collect([$vendor->city, $vendor->state, $vendor->pincode])->filter()->implode(', ') ?: '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Country</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->country ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p><span class="badge {{ $vendor->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($vendor->status) }}</span></p>
                </div>
            </div>
            @if($vendor->notes)
                <div class="mt-4">
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Notes</label>
                    <p class="text-base dark:text-white-light">{{ $vendor->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Purchase Orders --}}
        <div class="panel px-0">
            <div class="px-4 mb-4">
                <h6 class="text-base font-semibold">Purchase Orders</h6>
            </div>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">PO Number</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Expected Date</th>
                            <th class="px-4 py-2">Grand Total</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendor->purchaseOrders ?? [] as $index => $po)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $po->po_number }}</td>
                                <td class="px-4 py-2">{{ $po->po_date?->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $po->expected_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-2">{{ number_format($po->grand_total, 2) }}</td>
                                <td class="px-4 py-2">
                                    @switch($po->status)
                                        @case('draft')
                                            <span class="badge bg-dark">Draft</span>
                                            @break
                                        @case('sent')
                                            <span class="badge bg-info">Sent</span>
                                            @break
                                        @case('partial')
                                            <span class="badge bg-warning">Partial</span>
                                            @break
                                        @case('received')
                                            <span class="badge bg-success">Received</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge bg-dark">{{ ucfirst($po->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('admin.purchase-orders.show', $po->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
