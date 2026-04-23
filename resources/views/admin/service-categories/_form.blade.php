@props(['category' => null])
<div class="panel p-5 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
            <label class="text-xs font-semibold text-gray-500 uppercase">Name *</label>
            <input type="text" name="name" value="{{ old('name', $category?->name) }}" required maxlength="100" class="form-input mt-1" placeholder="e.g. Electrician, Plumber, Carpenter" />
        </div>
        <div class="col-span-2">
            <label class="text-xs font-semibold text-gray-500 uppercase">Description</label>
            <textarea name="description" rows="2" class="form-input mt-1" placeholder="Brief description of the category">{{ old('description', $category?->description) }}</textarea>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Icon (emoji, optional)</label>
            <input type="text" name="icon" value="{{ old('icon', $category?->icon) }}" maxlength="10" class="form-input mt-1" placeholder="⚡ 🔧 🪚 ❄️ 🎨" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Color (hex)</label>
            <input type="color" name="color" value="{{ old('color', $category?->color ?? '#3b82f6') }}" class="form-input mt-1 h-[42px]" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" min="0" class="form-input mt-1" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
            <select name="status" required class="form-select mt-1">
                <option value="active" @selected(old('status', $category?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $category?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>
