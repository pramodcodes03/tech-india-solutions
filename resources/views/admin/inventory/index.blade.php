<x-layout.admin title="Inventory">
    <div>
        <x-admin.breadcrumb :items="[['label' => 'Inventory']]" />

        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Current Stock Overview</h5>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('admin.inventory.low-stock') }}" class="btn btn-outline-warning gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Low Stock Alerts
                </a>
                <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-info gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    Stock Movements
                </a>
                <a href="{{ route('admin.inventory.adjust') }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Adjust Stock
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="flex items-end gap-4 flex-wrap">
                <div>
                    <label for="warehouse_id" class="text-sm">Warehouse</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select w-48" onchange="this.form.submit()">
                        <option value="">-- All Warehouses --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category_id" class="text-sm">Category</label>
                    <select id="category_id" name="category_id" class="form-select w-48" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(request('warehouse_id') || request('category_id'))
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-danger btn-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Product Code</th>
                            <th class="px-4 py-2">Product Name</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2">Current Stock</th>
                            <th class="px-4 py-2">Reorder Level</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventories as $index => $inventory)
                            <tr class="{{ ($inventory->quantity <= ($inventory->product->reorder_level ?? 0)) ? 'bg-danger/10 dark:bg-danger/5' : '' }}">
                                <td class="px-4 py-2">{{ $inventories->firstItem() + $index }}</td>
                                <td class="px-4 py-2">{{ $inventory->product->code ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $inventory->product->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $inventory->product->category->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $inventory->warehouse->name ?? '-' }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $inventory->quantity }}</td>
                                <td class="px-4 py-2">{{ $inventory->product->reorder_level ?? 0 }}</td>
                                <td class="px-4 py-2">
                                    @if($inventory->quantity <= ($inventory->product->reorder_level ?? 0))
                                        <span class="badge bg-danger">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-4 text-center text-gray-500">No inventory records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($inventories->hasPages())
                <div class="px-5 py-3">
                    {{ $inventories->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
