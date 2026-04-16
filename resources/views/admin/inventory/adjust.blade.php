<x-layout.admin>
    <div x-data="stockAdjustment()">
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Stock Adjustment</h5>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Inventory
            </a>
        </div>

        <div class="panel">
            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.inventory.adjust.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="product_id">Product <span class="text-danger">*</span></label>
                        <select id="product_id" name="product_id" class="form-select" x-model="selectedProduct" @change="fetchProductStock()" required>
                            <option value="">-- Select Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->code ? $product->code . ' - ' : '' }}{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="warehouse_id">Warehouse <span class="text-danger">*</span></label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select" x-model="selectedWarehouse" @change="fetchProductStock()" required>
                            <option value="">-- Select Warehouse --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Current Stock Info --}}
                    <div class="md:col-span-2" x-show="currentStock !== null" x-cloak>
                        <div class="p-4 rounded bg-info-light dark:bg-info dark:bg-opacity-20 border-l-4 border-info">
                            <p class="text-sm">Current stock for selected product/warehouse: <span class="font-bold text-lg" x-text="currentStock"></span></p>
                        </div>
                    </div>

                    <div>
                        <label for="type">Adjustment Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="adjustment" {{ old('type', 'adjustment') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        </select>
                    </div>
                    <div>
                        <label for="quantity">Quantity <span class="text-danger">*</span> <span class="text-gray-400">(negative to reduce)</span></label>
                        <input id="quantity" name="quantity" type="number" step="any" class="form-input" value="{{ old('quantity') }}" required />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="Reason for adjustment...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('stockAdjustment', () => ({
                selectedProduct: '{{ old('product_id') }}',
                selectedWarehouse: '{{ old('warehouse_id') }}',
                currentStock: null,

                fetchProductStock() {
                    if (!this.selectedProduct || !this.selectedWarehouse) {
                        this.currentStock = null;
                        return;
                    }
                    fetch(`{{ url('admin/inventory/stock') }}?product_id=${this.selectedProduct}&warehouse_id=${this.selectedWarehouse}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => { this.currentStock = data.quantity ?? 0; });
                }
            }));
        });
    </script>
</x-layout.admin>
