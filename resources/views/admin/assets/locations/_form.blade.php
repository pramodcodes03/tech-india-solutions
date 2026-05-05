@php $loc = $location ?? null; @endphp
<div class="panel grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Code <span class="text-danger">*</span></label>
        <input type="text" name="code" value="{{ old('code', $loc?->code) }}" class="form-input" required />
        @error('code') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>
    <div>
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $loc?->name) }}" class="form-input" required />
        @error('name') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>
    <div>
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select">
            @foreach(['office','warehouse','site','branch','other'] as $t)
                <option value="{{ $t }}" @selected(old('type', $loc?->type ?? 'office') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Manager</label>
        <select name="manager_id" class="form-select">
            <option value="">—</option>
            @foreach($managers as $m)
                <option value="{{ $m->id }}" @selected(old('manager_id', $loc?->manager_id) == $m->id)>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    <x-admin.india-location :state="$loc?->state" :city="$loc?->city" />
    <div class="md:col-span-2">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-input" rows="2">{{ old('address', $loc?->address) }}</textarea>
    </div>
    <div>
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select">
            <option value="active" @selected(old('status', $loc?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $loc?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
