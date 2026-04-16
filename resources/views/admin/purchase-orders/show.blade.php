<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Purchase Order Details</h5>
            <div class="flex items-center gap-2">
                @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                    <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
                <form action="{{ route('admin.purchase-orders.destroy', $purchaseOrder->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 mb-5 border-l-4 border-success rounded bg-success-light dark:bg-success dark:bg-opacity-20">
                <p class="text-sm text-success">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-danger">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Header Info --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">PO Number</p>
                    <p class="font-semibold text-lg">{{ $purchaseOrder->po_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vendor</p>
                    <p class="font-semibold">{{ $purchaseOrder->vendor->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $statusColors = ['draft' => 'bg-dark', 'sent' => 'bg-info', 'partial' => 'bg-warning', 'received' => 'bg-success', 'cancelled' => 'bg-danger'];
                    @endphp
                    <span class="badge {{ $statusColors[$purchaseOrder->status] ?? 'bg-dark' }}">{{ ucfirst($purchaseOrder->status) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">PO Date</p>
                    <p class="font-semibold">{{ $purchaseOrder->po_date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Expected Date</p>
                    <p class="font-semibold">{{ $purchaseOrder->expected_date ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Grand Total</p>
                    <p class="font-bold text-lg text-primary">{{ number_format($purchaseOrder->grand_total, 2) }}</p>
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
                        @forelse($purchaseOrder->items as $index => $item)
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
                        <span class="font-semibold">{{ number_format($purchaseOrder->subtotal, 2) }}</span>
                    </div>
                    @if($purchaseOrder->discount_value > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                Discount
                                @if($purchaseOrder->discount_type === 'percent')
                                    ({{ $purchaseOrder->discount_value }}%)
                                @endif
                            </span>
                            <span class="font-semibold text-danger">
                                - {{ number_format($purchaseOrder->discount_type === 'percent' ? ($purchaseOrder->subtotal * $purchaseOrder->discount_value / 100) : $purchaseOrder->discount_value, 2) }}
                            </span>
                        </div>
                    @endif
                    @if($purchaseOrder->tax_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tax ({{ $purchaseOrder->tax_percent ?? 0 }}%)</span>
                            <span class="font-semibold">+ {{ number_format($purchaseOrder->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold">Grand Total</span>
                        <span class="font-bold text-primary">{{ number_format($purchaseOrder->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Terms & Notes --}}
        @if($purchaseOrder->terms || $purchaseOrder->notes)
            <div class="panel mb-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @if($purchaseOrder->terms)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Terms & Conditions</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $purchaseOrder->terms }}</p>
                        </div>
                    @endif
                    @if($purchaseOrder->notes)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Notes</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $purchaseOrder->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Goods Receipts Section --}}
        <div class="panel mb-5">
            <h6 class="text-base font-semibold mb-4">Goods Receipts (GRN)</h6>
            @if($purchaseOrder->goodsReceipts && $purchaseOrder->goodsReceipts->count() > 0)
                <div class="space-y-4">
                    @foreach($purchaseOrder->goodsReceipts as $grn)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-4">
                                    <span class="font-semibold text-primary">{{ $grn->grn_number ?? 'GRN-' . $grn->id }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Received: {{ $grn->received_date ?? $grn->created_at->format('Y-m-d') }}</span>
                                </div>
                            </div>
                            @if($grn->notes)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $grn->notes }}</p>
                            @endif
                            <div class="table-responsive">
                                <table class="table-hover">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2">Description</th>
                                            <th class="px-4 py-2 text-right">Qty Received</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grn->items as $grnItem)
                                            <tr>
                                                <td class="px-4 py-2">{{ $grnItem->purchaseOrderItem->description ?? $grnItem->description ?? '-' }}</td>
                                                <td class="px-4 py-2 text-right">{{ $grnItem->quantity_received ?? $grnItem->quantity ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">No goods receipts recorded yet.</p>
            @endif
        </div>

        {{-- Receive Goods Section --}}
        @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Receive Goods</h6>
                <form action="{{ route('admin.purchase-orders.receive', $purchaseOrder->id) }}" method="POST">
                    @csrf
                    <div class="table-responsive mb-5">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2">#</th>
                                    <th class="px-4 py-2">Description</th>
                                    <th class="px-4 py-2 text-right">Ordered Qty</th>
                                    <th class="px-4 py-2 text-right">Already Received</th>
                                    <th class="px-4 py-2 text-right">Remaining</th>
                                    <th class="px-4 py-2 w-40">Qty to Receive</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $index => $item)
                                    @php
                                        $alreadyReceived = $item->quantity_received ?? 0;
                                        $remaining = $item->quantity - $alreadyReceived;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-2">{{ $index + 1 }}</td>
                                        <td class="px-4 py-2">{{ $item->description }}</td>
                                        <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                                        <td class="px-4 py-2 text-right">{{ $alreadyReceived }}</td>
                                        <td class="px-4 py-2 text-right">{{ $remaining }}</td>
                                        <td class="px-4 py-2">
                                            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->id }}" />
                                            <input type="number" name="items[{{ $index }}][quantity_received]" class="form-input" min="0" max="{{ $remaining }}" step="any" value="0" {{ $remaining <= 0 ? 'disabled' : '' }} />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mb-5">
                        <div>
                            <label for="received_date">Received Date <span class="text-danger">*</span></label>
                            <input id="received_date" name="received_date" type="date" class="form-input" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div>
                            <label for="grn_notes">Notes</label>
                            <textarea id="grn_notes" name="notes" class="form-input" rows="2" placeholder="Notes about this receipt..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-success">Receive Goods</button>
                    </div>
                </form>
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
