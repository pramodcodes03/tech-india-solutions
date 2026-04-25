@php
    $m = $model ?? null;
    $specsRaw = old('specifications_raw', $m && $m->specifications
        ? collect($m->specifications)->map(fn ($v, $k) => "$k: $v")->implode("\n")
        : '');
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="panel lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" value="{{ old('code', $m?->code) }}" class="form-input" required placeholder="e.g. LAP-DL5420" />
            @error('code') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select" required>
                <option value="">—</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $m?->category_id) == $c->id)
                        data-method="{{ $c->default_depreciation_method }}"
                        data-life="{{ $c->default_useful_life_years }}"
                        data-salvage="{{ $c->default_salvage_percent }}">
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            <div class="text-xs text-gray-400 mt-1">Picking a category prefills depreciation defaults below.</div>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Model Name <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $m?->name) }}" class="form-input" required placeholder='e.g. "Latitude 5420"' />
            @error('name') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="form-label">Manufacturer</label>
            <input type="text" name="manufacturer" value="{{ old('manufacturer', $m?->manufacturer) }}" class="form-input" placeholder='e.g. "Dell"' />
        </div>
        <div>
            <label class="form-label">Model Number</label>
            <input type="text" name="model_number" value="{{ old('model_number', $m?->model_number) }}" class="form-input" placeholder='e.g. "5420"' />
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Specifications</label>
            <textarea name="specifications_raw" class="form-input font-mono text-xs" rows="5" placeholder="key: value (one per line)
RAM: 16GB
CPU: i7-1165G7
Screen: 14 inch">{{ $specsRaw }}</textarea>
            <div class="text-xs text-gray-400 mt-1">One spec per line, in <code>key: value</code> format.</div>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input" rows="2">{{ old('description', $m?->description) }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">Image</label>
            <input type="file" name="image" accept="image/*" class="form-input" />
            @if($m?->image)
                <div class="mt-2"><img src="{{ asset('storage/'.$m->image) }}" class="w-24 h-24 object-cover rounded border" /></div>
            @endif
        </div>
    </div>

    <div class="panel">
        <h3 class="font-semibold mb-3">Defaults <span class="text-xs text-gray-400">(applied when creating new asset units)</span></h3>
        <div class="space-y-3">
            <div>
                <label class="form-label">Depreciation Method <span class="text-danger">*</span></label>
                <select name="default_depreciation_method" class="form-select" required>
                    @foreach(['straight_line' => 'Straight Line', 'declining_balance' => 'Declining Balance', 'sum_of_years_digits' => 'Sum of Years Digits', 'units_of_production' => 'Units of Production'] as $k => $v)
                        <option value="{{ $k }}" @selected(old('default_depreciation_method', $m?->default_depreciation_method ?? 'straight_line') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Useful Life (years) <span class="text-danger">*</span></label>
                <input type="number" name="default_useful_life_years" min="1" max="60" value="{{ old('default_useful_life_years', $m?->default_useful_life_years ?? 5) }}" class="form-input" required />
            </div>
            <div>
                <label class="form-label">Salvage % <span class="text-danger">*</span></label>
                <input type="number" name="default_salvage_percent" step="0.01" min="0" max="100" value="{{ old('default_salvage_percent', $m?->default_salvage_percent ?? 5) }}" class="form-input" required />
            </div>
            <div>
                <label class="form-label">Manufacturer Warranty (months) <span class="text-danger">*</span></label>
                <input type="number" name="manufacturer_warranty_months" min="0" max="240" value="{{ old('manufacturer_warranty_months', $m?->manufacturer_warranty_months ?? 12) }}" class="form-input" required />
            </div>
            <div>
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $m?->status ?? 'active') === 'active')>Active</option>
                    <option value="discontinued" @selected(old('status', $m?->status) === 'discontinued')>Discontinued</option>
                </select>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cat = document.querySelector('select[name="category_id"]');
    const m = document.querySelector('select[name="default_depreciation_method"]');
    const life = document.querySelector('input[name="default_useful_life_years"]');
    const sal = document.querySelector('input[name="default_salvage_percent"]');
    if (!cat) return;
    cat.addEventListener('change', () => {
        const opt = cat.selectedOptions[0]; if (!opt) return;
        if (opt.dataset.method) m.value = opt.dataset.method;
        if (opt.dataset.life) life.value = opt.dataset.life;
        if (opt.dataset.salvage) sal.value = opt.dataset.salvage;
    });
});
</script>
