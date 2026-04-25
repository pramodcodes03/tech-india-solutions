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

                {{-- End of Life --}}
                <div class="rounded-lg p-3 bg-gradient-to-br from-primary/5 to-info/5 border border-primary/20">
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label !mb-0 flex items-center gap-1.5 text-primary font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3M16 7V3M3 11h18M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2zM12 14v3M10.5 17h3"/></svg>
                            End of Life
                        </label>
                        <span id="eol-badge" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">No EOL set</span>
                    </div>
                    <div class="relative">
                        <input type="date" id="eol-input" name="end_of_life_date"
                               value="{{ old('end_of_life_date', $a?->end_of_life_date?->toDateString()) }}"
                               class="form-input pl-9" />
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3M16 7V3M3 11h18M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2 text-[10px]">
                        <button type="button" data-eol-add="3" class="px-2 py-0.5 rounded border border-primary/30 text-primary hover:bg-primary hover:text-white transition">+3 yrs</button>
                        <button type="button" data-eol-add="5" class="px-2 py-0.5 rounded border border-primary/30 text-primary hover:bg-primary hover:text-white transition">+5 yrs</button>
                        <button type="button" data-eol-add="7" class="px-2 py-0.5 rounded border border-primary/30 text-primary hover:bg-primary hover:text-white transition">+7 yrs</button>
                        <button type="button" data-eol-add="10" class="px-2 py-0.5 rounded border border-primary/30 text-primary hover:bg-primary hover:text-white transition">+10 yrs</button>
                        <button type="button" data-eol-useful-life class="px-2 py-0.5 rounded border border-success/40 text-success hover:bg-success hover:text-white transition">Use Useful Life</button>
                        <button type="button" data-eol-clear class="px-2 py-0.5 rounded border border-gray-300 text-gray-500 hover:bg-gray-100 transition">Clear</button>
                    </div>
                    <div class="text-[10px] text-gray-500 mt-1">Based on purchase date. Affects retirement & disposal alerts.</div>
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

    // ── End of Life logic ────────────────────────────────────────────
    const eolInput = document.querySelector('#eol-input');
    const eolBadge = document.querySelector('#eol-badge');
    if (eolInput) {
        const updateBadge = () => {
            if (!eolInput.value) {
                eolBadge.textContent = 'No EOL set';
                eolBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500';
                return;
            }
            const eol = new Date(eolInput.value);
            const today = new Date(); today.setHours(0,0,0,0);
            const diffDays = Math.ceil((eol - today) / 86400000);
            const diffYears = (diffDays / 365.25).toFixed(1);
            let label, cls;
            if (diffDays < 0) {
                label = 'Past EOL by ' + Math.abs(diffDays) + 'd';
                cls = 'bg-danger/15 text-danger';
            } else if (diffDays < 90) {
                label = diffDays + 'd remaining';
                cls = 'bg-danger/15 text-danger';
            } else if (diffDays < 365) {
                label = diffDays + 'd remaining';
                cls = 'bg-warning/15 text-warning';
            } else {
                label = diffYears + ' yrs remaining';
                cls = 'bg-success/15 text-success';
            }
            eolBadge.textContent = label;
            eolBadge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full ' + cls;
        };
        eolInput.addEventListener('input', updateBadge);
        eolInput.addEventListener('change', updateBadge);
        updateBadge();

        const baseDate = () => {
            return purchaseDate?.value ? new Date(purchaseDate.value) : new Date();
        };

        document.querySelectorAll('[data-eol-add]').forEach(btn => {
            btn.addEventListener('click', () => {
                const yrs = parseInt(btn.dataset.eolAdd);
                const d = baseDate();
                d.setFullYear(d.getFullYear() + yrs);
                eolInput.value = d.toISOString().slice(0, 10);
                updateBadge();
            });
        });
        document.querySelector('[data-eol-useful-life]')?.addEventListener('click', () => {
            const yrs = parseInt(life?.value || 5);
            const d = baseDate();
            d.setFullYear(d.getFullYear() + yrs);
            eolInput.value = d.toISOString().slice(0, 10);
            updateBadge();
        });
        document.querySelector('[data-eol-clear]')?.addEventListener('click', () => {
            eolInput.value = '';
            updateBadge();
        });
    }
});
</script>
