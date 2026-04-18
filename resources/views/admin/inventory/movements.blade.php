<x-layout.admin title="Stock Movements">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Inventory','url'=>route('admin.inventory.index')],['label'=>'Stock Movements']]" />

        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Stock Movement Ledger</h5>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Inventory
            </a>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <form action="{{ route('admin.inventory.movements') }}" method="GET" class="flex items-end gap-4 flex-wrap">
                <div>
                    <label for="product_id" class="text-sm">Product</label>
                    <select id="product_id" name="product_id" class="form-select w-48">
                        <option value="">-- All Products --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="warehouse_id" class="text-sm">Warehouse</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select w-48">
                        <option value="">-- All Warehouses --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type" class="text-sm">Type</label>
                    <select id="type" name="type" class="form-select w-40">
                        <option value="">-- All Types --</option>
                        <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>In</option>
                        <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Out</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="text-sm">Date From</label>
                    <input id="date_from" name="date_from" type="date" class="form-input w-40" value="{{ request('date_from') }}" />
                </div>
                <div>
                    <label for="date_to" class="text-sm">Date To</label>
                    <input id="date_to" name="date_to" type="date" class="form-input w-40" value="{{ request('date_to') }}" />
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    @if(request()->hasAny(['product_id', 'warehouse_id', 'type', 'date_from', 'date_to']))
                        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-danger btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Quantity</th>
                            <th class="px-4 py-2">Reference</th>
                            <th class="px-4 py-2">Notes</th>
                            <th class="px-4 py-2">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $index => $movement)
                            <tr>
                                <td class="px-4 py-2">{{ $movements->firstItem() + $index }}</td>
                                <td class="px-4 py-2">{{ $movement->created_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $movement->product->name ?? '-' }}</td>
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
                                    <span class="font-semibold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $movement->reference ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $movement->notes ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $movement->createdBy->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-4 text-center text-gray-500">No stock movements found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-5 py-3">
                    {{ $movements->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
