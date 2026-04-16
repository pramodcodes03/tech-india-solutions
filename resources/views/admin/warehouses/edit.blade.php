<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Warehouse</h5>
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.warehouses.update', $warehouse->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="code">Code <span class="text-danger">*</span></label>
                        <input id="code" name="code" type="text" class="form-input" value="{{ old('code', $warehouse->code) }}" required />
                    </div>
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $warehouse->name) }}" required />
                    </div>
                    <div class="md:col-span-2">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-input" rows="3" placeholder="Warehouse address...">{{ old('address', $warehouse->address) }}</textarea>
                    </div>
                    <div>
                        <label for="is_default" class="flex items-center gap-3 cursor-pointer">
                            <input id="is_default" name="is_default" type="checkbox" class="form-checkbox" value="1" {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }} />
                            <span>Set as Default Warehouse</span>
                        </label>
                    </div>
                    <div>
                        <label for="is_active" class="flex items-center gap-3 cursor-pointer">
                            <input id="is_active" name="is_active" type="checkbox" class="form-checkbox" value="1" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }} />
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
