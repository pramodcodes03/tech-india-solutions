<x-layout.admin title="Edit Product">
    <div x-data="{ imagePreview: null }">
        <x-admin.breadcrumb :items="[['label'=>'Products','url'=>route('admin.products.index')],['label'=>'Edit Product']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Product</h5>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
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

            <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $product->name) }}" required />
                    </div>
                    <div>
                        <label for="category_id">Category <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="category_id" :options="$categories" :selected="$product->category_id" placeholder="-- Select Category --" required />
                    </div>
                    <div>
                        <label for="hsn_code">HSN Code</label>
                        <input id="hsn_code" name="hsn_code" type="text" class="form-input" value="{{ old('hsn_code', $product->hsn_code) }}" />
                    </div>
                    <div>
                        <label for="unit">Unit <span class="text-danger">*</span></label>
                        <select id="unit" name="unit" class="form-select" required>
                            <option value="pcs" {{ old('unit', $product->unit) === 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="kg" {{ old('unit', $product->unit) === 'kg' ? 'selected' : '' }}>Kg</option>
                            <option value="mtr" {{ old('unit', $product->unit) === 'mtr' ? 'selected' : '' }}>Mtr</option>
                            <option value="box" {{ old('unit', $product->unit) === 'box' ? 'selected' : '' }}>Box</option>
                            <option value="ltr" {{ old('unit', $product->unit) === 'ltr' ? 'selected' : '' }}>Ltr</option>
                            <option value="set" {{ old('unit', $product->unit) === 'set' ? 'selected' : '' }}>Set</option>
                            <option value="nos" {{ old('unit', $product->unit) === 'nos' ? 'selected' : '' }}>Nos</option>
                        </select>
                    </div>
                    <div>
                        <label for="purchase_price">Purchase Price <span class="text-danger">*</span></label>
                        <input id="purchase_price" name="purchase_price" type="number" step="0.01" min="0" class="form-input" value="{{ old('purchase_price', $product->purchase_price) }}" required />
                    </div>
                    <div>
                        <label for="selling_price">Selling Price <span class="text-danger">*</span></label>
                        <input id="selling_price" name="selling_price" type="number" step="0.01" min="0" class="form-input" value="{{ old('selling_price', $product->selling_price) }}" required />
                    </div>
                    <div>
                        <label for="mrp">MRP</label>
                        <input id="mrp" name="mrp" type="number" step="0.01" min="0" class="form-input" value="{{ old('mrp', $product->mrp) }}" />
                    </div>
                    <div>
                        <label for="tax_percent">Tax % <span class="text-danger">*</span></label>
                        <input id="tax_percent" name="tax_percent" type="number" step="0.01" min="0" max="100" class="form-input" value="{{ old('tax_percent', $product->tax_percent) }}" required />
                    </div>
                    <div>
                        <label for="reorder_level">Reorder Level</label>
                        <input id="reorder_level" name="reorder_level" type="number" min="0" class="form-input" value="{{ old('reorder_level', $product->reorder_level) }}" />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3" placeholder="Product description...">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="image">Product Image</label>
                        @if($product->image)
                            <div class="mb-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Current Image:</p>
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="max-w-xs max-h-48 rounded border border-gray-200 dark:border-gray-700" />
                            </div>
                        @endif
                        <input id="image" name="image" type="file" class="form-input p-1" accept="image/*"
                            @change="if ($event.target.files[0]) { const reader = new FileReader(); reader.onload = e => imagePreview = e.target.result; reader.readAsDataURL($event.target.files[0]); } else { imagePreview = null; }" />
                        <template x-if="imagePreview">
                            <div class="mt-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">New Image Preview:</p>
                                <img :src="imagePreview" alt="Preview" class="max-w-xs max-h-48 rounded border border-gray-200 dark:border-gray-700" />
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
