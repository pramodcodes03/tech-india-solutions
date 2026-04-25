@php $l = $log ?? null; @endphp
<div class="panel grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Asset <span class="text-danger">*</span></label>
        <select name="asset_id" class="form-select" required>
            <option value="">—</option>
            @foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $l?->asset_id ?? $asset?->id) == $a->id)>{{ $a->asset_code }} · {{ $a->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
            @foreach(['corrective','preventive','inspection','audit'] as $t)<option value="{{ $t }}" @selected(old('type', $l?->type ?? 'corrective') === $t)>{{ ucfirst($t) }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            @foreach(['scheduled','in_progress','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status', $l?->status ?? 'completed') === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Scheduled Date</label>
        <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $l?->scheduled_date?->toDateString()) }}" class="form-input" />
    </div>
    <div>
        <label class="form-label">Performed Date</label>
        <input type="date" name="performed_date" value="{{ old('performed_date', $l?->performed_date?->toDateString() ?? now()->toDateString()) }}" class="form-input" />
    </div>
    <div>
        <label class="form-label">Performed by (Employee)</label>
        <select name="performed_by_employee_id" class="form-select">
            <option value="">—</option>
            @foreach($employees as $e)<option value="{{ $e->id }}" @selected(old('performed_by_employee_id', $l?->performed_by_employee_id) == $e->id)>{{ $e->full_name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Performed by (Other / Vendor name)</label>
        <input type="text" name="performed_by" value="{{ old('performed_by', $l?->performed_by) }}" class="form-input" placeholder="External technician" />
    </div>
    <div>
        <label class="form-label">Vendor name</label>
        <input type="text" name="vendor_name" value="{{ old('vendor_name', $l?->vendor_name) }}" class="form-input" />
    </div>
    <div>
        <label class="form-label">Parts Cost</label>
        <input type="number" step="0.01" min="0" name="parts_cost" value="{{ old('parts_cost', $l?->parts_cost ?? 0) }}" class="form-input" />
    </div>
    <div>
        <label class="form-label">Labour Cost</label>
        <input type="number" step="0.01" min="0" name="labour_cost" value="{{ old('labour_cost', $l?->labour_cost ?? 0) }}" class="form-input" />
    </div>
    <div>
        <label class="form-label">Downtime (hours)</label>
        <input type="number" step="0.01" min="0" name="downtime_hours" value="{{ old('downtime_hours', $l?->downtime_hours ?? 0) }}" class="form-input" />
    </div>
    <div></div>
    <div class="md:col-span-2">
        <label class="form-label">Description</label>
        <textarea name="description" rows="2" class="form-input">{{ old('description', $l?->description) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="form-label">Parts Used</label>
        <textarea name="parts_used" rows="2" class="form-input" placeholder="One per line — e.g. SSD 512GB ×1">{{ old('parts_used', $l?->parts_used) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="form-label">Resolution Notes</label>
        <textarea name="resolution_notes" rows="2" class="form-input">{{ old('resolution_notes', $l?->resolution_notes) }}</textarea>
    </div>
</div>
