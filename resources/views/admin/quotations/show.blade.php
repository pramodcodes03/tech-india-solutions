<x-layout.admin title="Quotation Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Quotations','url'=>route('admin.quotations.index')],['label'=>'Quotation Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Quotation Details</h5>
            <div class="flex items-center gap-2">
                {{-- Status Action Buttons --}}
                @if($quotation->status === 'draft')
                    <form action="{{ route('admin.quotations.update-status', $quotation->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit" class="btn btn-info btn-sm gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Mark as Sent
                        </button>
                    </form>
                @endif
                @if($quotation->status === 'sent')
                    <form action="{{ route('admin.quotations.update-status', $quotation->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="btn btn-success btn-sm gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Mark Accepted
                        </button>
                    </form>
                    <form action="{{ route('admin.quotations.update-status', $quotation->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-danger btn-sm gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Mark Rejected
                        </button>
                    </form>
                    <form action="{{ route('admin.quotations.update-status', $quotation->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="expired">
                        <button type="submit" class="btn btn-warning btn-sm gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Mark Expired
                        </button>
                    </form>
                @endif
                @if(in_array($quotation->status, ['rejected', 'expired']))
                    <form action="{{ route('admin.quotations.update-status', $quotation->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="draft">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Reopen as Draft</button>
                    </form>
                @endif
                @if($quotation->status === 'accepted')
                    <form action="{{ route('admin.quotations.convert-to-order', $quotation->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Convert to Sales Order
                        </button>
                    </form>
                @endif

                {{-- Other actions --}}
                @if(in_array($quotation->status, ['draft', 'sent']))
                    <a href="{{ route('admin.quotations.edit', $quotation->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
                <a href="{{ route('admin.quotations.pdf', $quotation->id) }}" class="btn btn-outline-secondary btn-sm" target="_blank">PDF</a>
                <form action="{{ route('admin.quotations.clone', $quotation->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">Clone</button>
                </form>
                <form action="{{ route('admin.quotations.destroy', $quotation->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
                <a href="{{ route('admin.quotations.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Status Flow Indicator --}}
        @php
            $steps = [
                ['key'=>'draft',    'label'=>'Draft',    'icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'color'=>'#6b7280'],
                ['key'=>'sent',     'label'=>'Sent',     'icon'=>'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color'=>'#0891b2'],
                ['key'=>'accepted', 'label'=>'Accepted', 'icon'=>'M5 13l4 4L19 7', 'color'=>'#16a34a'],
            ];
            $mainFlow = ['draft','sent','accepted'];
            $currentInMain = in_array($quotation->status, $mainFlow);
        @endphp
        <div class="panel mb-5 py-4">
            <div class="flex items-center justify-center gap-0">
                @foreach($steps as $i => $step)
                    @php
                        $isActive   = $quotation->status === $step['key'];
                        $isPast     = $currentInMain && array_search($quotation->status, $mainFlow) > $i;
                        $isRejected = $quotation->status === 'rejected';
                        $isExpired  = $quotation->status === 'expired';
                    @endphp
                    <div class="flex items-center gap-0">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all"
                                style="
                                    border-color: {{ ($isActive || $isPast) ? $step['color'] : '#e5e7eb' }};
                                    background: {{ $isActive ? $step['color'] : ($isPast ? $step['color'].'22' : '#f9fafb') }};
                                    color: {{ ($isActive || $isPast) ? $step['color'] : '#9ca3af' }};
                                ">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" {{ $isActive ? 'style=color:#fff' : '' }}>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}"/>
                                </svg>
                            </div>
                            <span class="text-xs mt-1 font-medium" style="color: {{ ($isActive || $isPast) ? $step['color'] : '#9ca3af' }}">{{ $step['label'] }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="w-16 h-0.5 mx-1 mb-4" style="background: {{ $isPast ? '#16a34a' : '#e5e7eb' }}"></div>
                        @endif
                    </div>
                @endforeach

                @if($quotation->status === 'rejected')
                    <div class="ml-6 flex flex-col items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-red-500 bg-red-500">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <span class="text-xs mt-1 font-medium text-red-500">Rejected</span>
                    </div>
                @elseif($quotation->status === 'expired')
                    <div class="ml-6 flex flex-col items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-yellow-500 bg-yellow-500">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-xs mt-1 font-medium text-yellow-500">Expired</span>
                    </div>
                @elseif($quotation->status === 'accepted')
                    <div class="ml-4 flex flex-col items-center">
                        <div class="w-16 h-0.5 mx-1 mb-4" style="background:#16a34a"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-green-600 bg-green-600">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        </div>
                        <span class="text-xs mt-1 font-medium text-green-600">Sales Order</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Header Info --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Quotation Number</p>
                    <p class="font-semibold text-lg">{{ $quotation->quotation_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                    <p class="font-semibold">{{ $quotation->customer->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $statusColors = ['draft' => 'bg-dark', 'sent' => 'bg-info', 'accepted' => 'bg-success', 'rejected' => 'bg-danger', 'expired' => 'bg-warning'];
                    @endphp
                    <span class="badge {{ $statusColors[$quotation->status] ?? 'bg-dark' }}">{{ ucfirst($quotation->status) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Quotation Date</p>
                    <p class="font-semibold">@formatDate($quotation->quotation_date)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Valid Until</p>
                    <p class="font-semibold">@formatDate($quotation->valid_until)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Grand Total</p>
                    <p class="font-bold text-lg text-primary">{{ number_format($quotation->grand_total, 2) }}</p>
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
                        @forelse($quotation->items as $index => $item)
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
                        <span class="font-semibold">{{ number_format($quotation->subtotal, 2) }}</span>
                    </div>
                    @if($quotation->discount_value > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                Discount
                                @if($quotation->discount_type === 'percent')
                                    ({{ $quotation->discount_value }}%)
                                @endif
                            </span>
                            <span class="font-semibold text-danger">
                                - {{ number_format($quotation->discount_type === 'percent' ? ($quotation->subtotal * $quotation->discount_value / 100) : $quotation->discount_value, 2) }}
                            </span>
                        </div>
                    @endif
                    @if($quotation->tax_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tax ({{ $quotation->tax_percent ?? 0 }}%)</span>
                            <span class="font-semibold">+ {{ number_format($quotation->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold">Grand Total</span>
                        <span class="font-bold text-primary">{{ number_format($quotation->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Terms & Notes --}}
        @if($quotation->terms || $quotation->notes)
            <div class="panel mb-5">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @if($quotation->terms)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Terms & Conditions</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $quotation->terms }}</p>
                        </div>
                    @endif
                    @if($quotation->notes)
                        <div>
                            <h6 class="text-base font-semibold mb-2">Notes</h6>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $quotation->notes }}</p>
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
