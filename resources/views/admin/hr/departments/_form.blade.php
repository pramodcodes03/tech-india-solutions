@props(['department' => null, 'employees'])
<div class="panel p-5 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Code *</label>
            <input type="text" name="code" value="{{ old('code', $department?->code) }}" required maxlength="20" class="form-input mt-1" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Name *</label>
            <input type="text" name="name" value="{{ old('name', $department?->name) }}" required class="form-input mt-1" />
        </div>
        <div class="col-span-2">
            <label class="text-xs font-semibold text-gray-500 uppercase">Description</label>
            <textarea name="description" rows="2" class="form-input mt-1">{{ old('description', $department?->description) }}</textarea>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Head of Department</label>
            <select name="head_id" class="form-select mt-1">
                <option value="">None</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(old('head_id', $department?->head_id) == $e->id)>{{ $e->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">CL &amp; SL Working-days</label>
            <input type="number" min="0" max="1000" name="cl_sl_working_days" value="{{ old('cl_sl_working_days', $department?->cl_sl_working_days) }}" placeholder="Inherit (global)" class="form-input mt-1" />
            <div class="text-[10px] text-gray-400 mt-1">Days since joining before Casual &amp; Sick Leave unlock for this department. Blank = inherit the global Leave Settings value.</div>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">EL Working-days Required</label>
            <input type="number" min="0" max="1000" name="el_working_days_required" value="{{ old('el_working_days_required', $department?->el_working_days_required) }}" placeholder="Inherit (global)" class="form-input mt-1" />
            <div class="text-[10px] text-gray-400 mt-1">Days since joining before Earned Leave unlocks. Blank = inherit the global Leave Settings value. An employee's own override wins over this.</div>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
            <select name="status" required class="form-select mt-1">
                <option value="active" @selected(old('status', $department?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $department?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>
