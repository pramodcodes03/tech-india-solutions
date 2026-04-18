<x-layout.admin title="Inventory Report">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Reports','url'=>route('admin.reports.index')],['label'=>'Inventory Report']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Inventory Report</h5>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Reports
            </a>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <h5 class="text-lg font-semibold mb-4">Filters</h5>
            <form method="GET" action="{{ route('admin.reports.inventory') }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="form-select">
                            <option value="">-- All Products --</option>
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">-- All Categories --</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="warehouse_id">Warehouse</label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                            <option value="">-- All Warehouses --</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.reports.export-excel', 'inventory') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success">Export Excel</a>
                    <a href="{{ route('admin.reports.export-pdf', 'inventory') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-danger">Export PDF</a>
                </div>
            </form>
        </div>

        {{-- Results Table --}}
        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Product Code</th>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2">Warehouse</th>
                            <th class="px-4 py-2 text-right">Current Stock</th>
                            <th class="px-4 py-2 text-right">Reorder Level</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2 font-semibold">{{ $row->product_code }}</td>
                                <td class="px-4 py-2">{{ $row->product_name }}</td>
                                <td class="px-4 py-2">{{ $row->category_name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $row->warehouse_name ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->current_stock }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->reorder_level }}</td>
                                <td class="px-4 py-2">
                                    @if($row->current_stock <= 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif($row->current_stock <= $row->reorder_level)
                                        <span class="badge bg-warning">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
