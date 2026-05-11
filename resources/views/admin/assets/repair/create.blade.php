<x-layout.admin title="New Repair Request">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Repair Requests', 'url' => route('admin.assets.repair.index')], ['label' => 'New Request']]" />

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-extrabold mb-6">New Asset Repair Approval Request</h1>

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="list-disc pl-4 text-sm">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="panel p-6">
            <form method="POST" action="{{ route('admin.assets.repair.store') }}">
                @csrf

                {{-- Asset --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Asset <span class="text-danger">*</span></label>
                    <select name="asset_id" class="form-select" required
                        x-data
                        x-on:change="
                            const opt = $event.target.selectedOptions[0];
                            document.getElementById('asset_type_field').value = opt.dataset.category ?? '';
                        ">
                        <option value="">— Select Asset —</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}"
                                data-category="{{ $a->category?->name ?? '' }}"
                                @selected(old('asset_id', $preselectedAsset?->id) == $a->id)>
                                [{{ $a->asset_code }}] {{ $a->name }}
                                ({{ ucwords(str_replace('_', ' ', $a->status)) }})
                            </option>
                        @endforeach
                    </select>
                    @error('asset_id')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                    @if($assets->isEmpty())
                        <p class="text-xs text-warning mt-1">No repairable assets available. All assets may be marked as non-repairable, disposed, or retired.</p>
                    @endif
                </div>

                {{-- Asset Type --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Asset Type</label>
                    <input type="text" id="asset_type_field" name="asset_type"
                        value="{{ old('asset_type', $preselectedAsset?->category?->name) }}"
                        placeholder="e.g. Laptop, Generator, Vehicle"
                        class="form-input" maxlength="100" />
                    <p class="text-[11px] text-gray-500 mt-0.5">Auto-filled from asset category. You may override.</p>
                    @error('asset_type')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Vendor Name --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Vendor Name <span class="text-danger">*</span></label>
                    <input type="text" name="vendor_name" value="{{ old('vendor_name') }}"
                        placeholder="Enter repair vendor / service centre name"
                        class="form-input" maxlength="200" required />
                    @error('vendor_name')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Repair Delivery Date --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Expected Repair Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="repair_delivery_date"
                        value="{{ old('repair_delivery_date') }}"
                        min="{{ now()->toDateString() }}"
                        class="form-input" required />
                    @error('repair_delivery_date')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Estimated Cost --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Estimated Repair Cost <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">₹</span>
                        <input type="number" name="estimated_cost" value="{{ old('estimated_cost') }}"
                            placeholder="0.00" step="0.01" min="0"
                            class="form-input pl-7" />
                    </div>
                    @error('estimated_cost')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Description --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1">Repair Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="4"
                        placeholder="Describe the issue requiring repair, parts to be replaced, scope of work..."
                        class="form-textarea w-full" maxlength="2000" required>{{ old('description') }}</textarea>
                    @error('description')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">Submit for Approval</button>
                    <a href="{{ route('admin.assets.repair.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
