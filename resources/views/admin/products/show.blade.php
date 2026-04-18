<x-layout.admin title="Product Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Products','url'=>route('admin.products.index')],['label'=>'Product Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Product Details</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back to List
                </a>
            </div>
        </div>

        {{-- Product Info Panel --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                @if($product->image)
                    <div class="md:col-span-3 mb-2">
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="max-w-xs max-h-48 rounded border border-gray-200 dark:border-gray-700" />
                    </div>
                @endif
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Code</label>
                    <p class="text-base dark:text-white-light">{{ $product->code ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-base dark:text-white-light">{{ $product->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Category</label>
                    <p class="text-base dark:text-white-light">{{ $product->category->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">HSN Code</label>
                    <p class="text-base dark:text-white-light">{{ $product->hsn_code ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Unit</label>
                    <p class="text-base dark:text-white-light">{{ strtoupper($product->unit) }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Purchase Price</label>
                    <p class="text-base dark:text-white-light">{{ number_format($product->purchase_price, 2) }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Selling Price</label>
                    <p class="text-base dark:text-white-light">{{ number_format($product->selling_price, 2) }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">MRP</label>
                    <p class="text-base dark:text-white-light">{{ $product->mrp ? number_format($product->mrp, 2) : '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tax %</label>
                    <p class="text-base dark:text-white-light">{{ $product->tax_percent }}%</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Reorder Level</label>
                    <p class="text-base dark:text-white-light">{{ $product->reorder_level ?? 0 }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p><span class="badge {{ $product->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($product->status) }}</span></p>
                </div>
            </div>
            @if($product->description)
                <div class="mt-4">
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Description</label>
                    <p class="text-base dark:text-white-light">{{ $product->description }}</p>
                </div>
            @endif
        </div>

        {{-- Stock Info Panel --}}
        <div class="panel mb-5">
            <h6 class="text-base font-semibold mb-4">Stock Across Warehouses</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2">Current Stock</th>
                            <th class="px-4 py-2">Reorder Level</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockByWarehouse ?? [] as $index => $stock)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $stock->warehouse->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $stock->quantity }}</td>
                                <td class="px-4 py-2">{{ $product->reorder_level ?? 0 }}</td>
                                <td class="px-4 py-2">
                                    @if($stock->quantity <= ($product->reorder_level ?? 0))
                                        <span class="badge bg-danger">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No stock records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Stock Movements --}}
        <div class="panel">
            <h6 class="text-base font-semibold mb-4">Recent Stock Movements</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Quantity</th>
                            <th class="px-4 py-2">Reference</th>
                            <th class="px-4 py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements ?? [] as $index => $movement)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $movement->created_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $movement->warehouse->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    @switch($movement->type)
                                        @case('in')
                                            <span class="badge bg-success">In</span>
                                            @break
                                        @case('out')
                                            <span class="badge bg-danger">Out</span>
                                            @break
                                        @case('adjustment')
                                            <span class="badge bg-warning">Adjustment</span>
                                            @break
                                        @case('transfer')
                                            <span class="badge bg-info">Transfer</span>
                                            @break
                                        @default
                                            <span class="badge bg-dark">{{ ucfirst($movement->type) }}</span>
                                    @endswitch
                                </td>
                                <td class="px-4 py-2">
                                    <span class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $movement->reference ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $movement->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No stock movements found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
