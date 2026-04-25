<x-layout.admin title="Proforma Invoice Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Proforma Invoices','url'=>route('admin.proforma-invoices.index')],['label'=>'Proforma Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Proforma Invoice Details</h5>
            <div class="flex items-center gap-2 flex-wrap">
                @if($proforma->status === 'draft')
                    <form action="{{ route('admin.proforma-invoices.update-status', $proforma->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit" class="btn btn-info btn-sm">Mark as Sent</button>
                    </form>
                @endif
                @if($proforma->status === 'sent')
                    <form action="{{ route('admin.proforma-invoices.update-status', $proforma->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="btn btn-success btn-sm">Mark Accepted</button>
                    </form>
                    <form action="{{ route('admin.proforma-invoices.update-status', $proforma->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-danger btn-sm">Mark Rejected</button>
                    </form>
                @endif

                @if(!$proforma->invoice_id && $proforma->status !== 'converted')
                    <form action="{{ route('admin.proforma-invoices.convert-to-invoice', $proforma->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">Convert to Tax Invoice</button>
                    </form>
                @endif

                @if($proforma->invoice_id)
                    <a href="{{ route('admin.invoices.show', $proforma->invoice_id) }}" class="btn btn-success btn-sm">View Invoice #{{ $proforma->invoice?->invoice_number }}</a>
                @endif

                @if(!in_array($proforma->status, ['converted']))
                    <a href="{{ route('admin.proforma-invoices.edit', $proforma->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
                <a href="{{ route('admin.proforma-invoices.pdf', $proforma->id) }}" class="btn btn-outline-secondary btn-sm" target="_blank">PDF</a>
                <form action="{{ route('admin.proforma-invoices.clone', $proforma->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">Clone</button>
                </form>
                @if($proforma->status !== 'converted')
                    <form action="{{ route('admin.proforma-invoices.destroy', $proforma->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                @endif
                <a href="{{ route('admin.proforma-invoices.index') }}" class="btn btn-outline-primary btn-sm">Back</a>
            </div>
        </div>

        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500">Proforma Number</p>
                    <p class="font-semibold text-lg">{{ $proforma->proforma_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Customer</p>
                    <p class="font-semibold">{{ $proforma->customer->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    @php
                        $statusColors = ['draft' => 'bg-dark', 'sent' => 'bg-info', 'accepted' => 'bg-success', 'rejected' => 'bg-danger', 'expired' => 'bg-warning', 'converted' => 'bg-primary'];
                    @endphp
                    <span class="badge {{ $statusColors[$proforma->status] ?? 'bg-dark' }}">{{ ucfirst($proforma->status) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Proforma Date</p>
                    <p class="font-semibold">@formatDate($proforma->proforma_date)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Valid Until</p>
                    <p class="font-semibold">@formatDate($proforma->valid_until)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Grand Total</p>
                    <p class="font-bold text-lg text-primary">{{ number_format($proforma->grand_total, 2) }}</p>
                </div>
            </div>
        </div>

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
                        @forelse($proforma->items as $index => $item)
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
                            <tr><td colspan="9" class="px-4 py-4 text-center text-gray-500">No items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div></div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold">{{ number_format($proforma->subtotal, 2) }}</span>
                    </div>
                    @if($proforma->discount_value > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">
                                Discount @if($proforma->discount_type === 'percent')({{ $proforma->discount_value }}%)@endif
                            </span>
                            <span class="font-semibold text-danger">
                                - {{ number_format($proforma->discount_type === 'percent' ? ($proforma->subtotal * $proforma->discount_value / 100) : $proforma->discount_value, 2) }}
                            </span>
                        </div>
                    @endif
                    @if($proforma->tax_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Tax ({{ $proforma->tax_percent ?? 0 }}%)</span>
                            <span class="font-semibold">+ {{ number_format($proforma->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold">Grand Total</span>
                        <span class="font-bold text-primary">{{ number_format($proforma->grand_total, 2) }}</span>
                    </div>
                    @if($proforma->advance_received > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Advance Received</span>
                            <span class="font-semibold text-success">{{ number_format($proforma->advance_received, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($proforma->terms || $proforma->notes)
            <div class="panel mb-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @if($proforma->terms)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Terms & Conditions</h6>
                            <p class="text-gray-600 whitespace-pre-line">{{ $proforma->terms }}</p>
                        </div>
                    @endif
                    @if($proforma->notes)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Notes</h6>
                            <p class="text-gray-600 whitespace-pre-line">{{ $proforma->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script>
        function confirmDelete(form) {
            const swal = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
            swal.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    </script>
</x-layout.admin>
