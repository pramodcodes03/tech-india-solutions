@php $a = $asset ?? null; @endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="panel lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Asset Code <span class="text-xs text-gray-400">(blank = auto)</span></label>
                <input type="text" name="asset_code" value="{{ old('asset_code', $a?->asset_code) }}" class="form-input font-mono" placeholder="Auto-generated if blank" />
            </div>
            <div>
                <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $a?->name) }}" class="form-input" required />
            </div>
        </div>

        <div>
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category_id" id="cat-select" class="form-select" required>
                <option value="">—</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $a?->category_id) == $c->id)
                        data-method="{{ $c->default_depreciation_method }}"
                        data-life="{{ $c->default_useful_life_years }}"
                        data-salvage="{{ $c->default_salvage_percent }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Model</label>
            <select name="asset_model_id" id="model-select" class="form-select">
                <option value="">— Select model —</option>
                @foreach($models as $m)
                    <option value="{{ $m->id }}" @selected(old('asset_model_id', $a?->asset_model_id ?? request('asset_model_id')) == $m->id)
                        data-category="{{ $m->category_id }}"
                        data-method="{{ $m->default_depreciation_method }}"
                        data-life="{{ $m->default_useful_life_years }}"
                        data-salvage-percent="{{ $m->default_salvage_percent }}"
                        data-warranty-months="{{ $m->manufacturer_warranty_months }}">
                        {{ $m->name }} @if($m->manufacturer)({{ $m->manufacturer }})@endif
                    </option>
                @endforeach
            </select>
            <div class="text-xs text-gray-400 mt-1">Selecting a model fills depreciation defaults.</div>
        </div>

        <div>
            <label class="form-label">Serial Number</label>
            <input type="text" name="serial_number" value="{{ old('serial_number', $a?->serial_number) }}" class="form-input" />
        </div>

        <div>
            <label class="form-label">Location</label>
            <select name="location_id" class="form-select">
                <option value="">—</option>
                @foreach($locations as $l)<option value="{{ $l->id }}" @selected(old('location_id', $a?->location_id) == $l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Custodian (Employee)</label>
            <select name="current_custodian_id" class="form-select">
                <option value="">—</option>
                @foreach($employees as $e)<option value="{{ $e->id }}" @selected(old('current_custodian_id', $a?->current_custodian_id) == $e->id)>{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select" required>
                @foreach(['draft','in_storage','assigned','in_maintenance','retired','disposed'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $a?->status ?? 'in_storage') === $s)>{{ ucwords(str_replace('_',' ', $s)) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Condition <span class="text-danger">*</span></label>
            <select name="condition_rating" class="form-select" required>
                @foreach(['excellent','good','fair','poor','damaged'] as $c)
                    <option value="{{ $c }}" @selected(old('condition_rating', $a?->condition_rating ?? 'good') === $c)>{{ ucfirst($c) }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="form-label">Image</label>
            <input type="file" name="image" accept="image/*" class="form-input" />
            @if($a?->image_path)<div class="mt-2"><img src="{{ asset('storage/'.$a->image_path) }}" class="w-32 h-32 object-cover rounded border" /></div>@endif
        </div>

        <div class="md:col-span-2">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-input" rows="2">{{ old('notes', $a?->notes) }}</textarea>
        </div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h3 class="font-semibold mb-3">Acquisition</h3>
            <div class="space-y-3">
                <div>
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">—</option>
                        @foreach($vendors as $v)<option value="{{ $v->id }}" @selected(old('vendor_id', $a?->vendor_id) == $v->id)>{{ $v->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Purchase Order</label>
                    <select name="purchase_order_id" class="form-select">
                        <option value="">—</option>
                        @foreach($purchaseOrders as $po)<option value="{{ $po->id }}" @selected(old('purchase_order_id', $a?->purchase_order_id) == $po->id)>{{ $po->po_number }} · {{ $po->vendor?->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', $a?->purchase_date?->toDateString()) }}" class="form-input" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label">Cost <span class="text-danger">*</span></label>
                        <input type="number" name="purchase_cost" step="0.01" min="0" value="{{ old('purchase_cost', $a?->purchase_cost ?? 0) }}" class="form-input" required />
                    </div>
                    <div>
                        <label class="form-label">Salvage Value</label>
                        <input type="number" name="salvage_value" step="0.01" min="0" value="{{ old('salvage_value', $a?->salvage_value ?? 0) }}" class="form-input" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label">Warranty Expires</label>
                        <input type="date" name="warranty_expiry_date" value="{{ old('warranty_expiry_date', $a?->warranty_expiry_date?->toDateString()) }}" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Insurance Expires</label>
                        <input type="date" name="insurance_expiry_date" value="{{ old('insurance_expiry_date', $a?->insurance_expiry_date?->toDateString()) }}" class="form-input" />
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold mb-3">Depreciation</h3>
            <div class="space-y-3">
                <div>
                    <label class="form-label">Method <span class="text-danger">*</span></label>
                    <select name="depreciation_method" id="dep-method" class="form-select">
                        <option value="straight_line">Straight Line</option>
                        <option value="declining_balance">Declining Balance</option>
                        <option value="sum_of_years_digits">Sum of Years Digits</option>
                        <option value="units_of_production">Units of Production</option>
                        <option value="none">None (no depreciation)</option>
                    </select>
                    <script>
                        document.querySelector('#dep-method').value = "{{ old('depreciation_method', $a?->depreciation_method ?? 'straight_line') }}";
                    </script>
                </div>
                <div>
                    <label class="form-label">Useful Life (years) <span class="text-danger">*</span></label>
                    <input type="number" name="useful_life_years" id="dep-life" min="0" max="60" value="{{ old('useful_life_years', $a?->useful_life_years ?? 5) }}" class="form-input" required />
                </div>
                <div>
                    <label class="form-label">Depreciation Start Date</label>
                    <input type="date" name="depreciation_start_date" value="{{ old('depreciation_start_date', $a?->depreciation_start_date?->toDateString()) }}" class="form-input" />
                </div>
                @if($a)
                    <div class="border-t pt-3 text-xs text-gray-500 space-y-1">
                        <div class="flex justify-between"><span>Accumulated</span><span class="font-semibold">&#8377;{{ number_format($a->accumulated_depreciation, 2) }}</span></div>
                        <div class="flex justify-between"><span>Book Value</span><span class="font-semibold text-success">&#8377;{{ number_format($a->current_book_value, 2) }}</span></div>
                        <div class="flex justify-between"><span>Last Posted</span><span>{{ $a->last_depreciation_posted_on?->format('d M Y') ?? 'Never' }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modelSel = document.querySelector('#model-select');
    const catSel = document.querySelector('#cat-select');
    const method = document.querySelector('#dep-method');
    const life = document.querySelector('#dep-life');
    const salvage = document.querySelector('input[name="salvage_value"]');
    const cost = document.querySelector('input[name="purchase_cost"]');
    const warrantyDate = document.querySelector('input[name="warranty_expiry_date"]');
    const purchaseDate = document.querySelector('input[name="purchase_date"]');

    if (modelSel) {
        modelSel.addEventListener('change', () => {
            const opt = modelSel.selectedOptions[0]; if (!opt || !opt.value) return;
            if (opt.dataset.category) catSel.value = opt.dataset.category;
            if (opt.dataset.method) method.value = opt.dataset.method;
            if (opt.dataset.life) life.value = opt.dataset.life;
            // Salvage = cost × salvage_percent / 100
            if (opt.dataset.salvagePercent && cost.value) {
                salvage.value = (parseFloat(cost.value) * parseFloat(opt.dataset.salvagePercent) / 100).toFixed(2);
            }
            // Warranty expiry = purchase date + warranty months
            if (opt.dataset.warrantyMonths && purchaseDate.value && !warrantyDate.value) {
                const d = new Date(purchaseDate.value);
                d.setMonth(d.getMonth() + parseInt(opt.dataset.warrantyMonths));
                warrantyDate.value = d.toISOString().slice(0, 10);
            }
        });
    }
});
</script>
