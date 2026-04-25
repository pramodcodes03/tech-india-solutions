@php $cat = $category ?? null; @endphp
<div class="panel grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Code <span class="text-danger">*</span></label>
        <input type="text" name="code" value="{{ old('code', $cat?->code) }}" class="form-input" required placeholder="e.g. LAP" />
        @error('code') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>
    <div>
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $cat?->name) }}" class="form-input" required placeholder="e.g. Laptop" />
        @error('name') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>
    <div>
        <label class="form-label">Default Depreciation Method <span class="text-danger">*</span></label>
        <select name="default_depreciation_method" class="form-select" required>
            @foreach(['straight_line' => 'Straight Line', 'declining_balance' => 'Declining Balance', 'sum_of_years_digits' => 'Sum of Years Digits', 'units_of_production' => 'Units of Production'] as $k => $v)
                <option value="{{ $k }}" @selected(old('default_depreciation_method', $cat?->default_depreciation_method ?? 'straight_line') === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Default Useful Life (years) <span class="text-danger">*</span></label>
        <input type="number" name="default_useful_life_years" min="1" max="60" value="{{ old('default_useful_life_years', $cat?->default_useful_life_years ?? 5) }}" class="form-input" required />
    </div>
    <div>
        <label class="form-label">Default Salvage % <span class="text-danger">*</span></label>
        <input type="number" name="default_salvage_percent" step="0.01" min="0" max="100" value="{{ old('default_salvage_percent', $cat?->default_salvage_percent ?? 5) }}" class="form-input" required />
    </div>
    <div>
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select">
            <option value="active" @selected(old('status', $cat?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $cat?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-input" rows="2">{{ old('description', $cat?->description) }}</textarea>
    </div>
</div>
