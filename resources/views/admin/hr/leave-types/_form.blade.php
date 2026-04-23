@props(['leaveType' => null])
<div class="panel p-5 grid grid-cols-2 md:grid-cols-3 gap-4">
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Code *</label><input type="text" name="code" value="{{ old('code', $leaveType?->code) }}" required maxlength="10" class="form-input mt-1" /></div>
    <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">Name *</label><input type="text" name="name" value="{{ old('name', $leaveType?->name) }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Annual Quota (days)</label><input type="number" step="0.5" name="annual_quota" value="{{ old('annual_quota', $leaveType?->annual_quota ?? 0) }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Max Carry Forward</label><input type="number" step="0.5" name="max_carry_forward" value="{{ old('max_carry_forward', $leaveType?->max_carry_forward ?? 0) }}" class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Color</label><input type="color" name="color" value="{{ old('color', $leaveType?->color ?? '#3b82f6') }}" class="form-input mt-1 h-10" /></div>
    <div class="md:col-span-3 flex gap-6 pt-2">
        <label class="flex items-center gap-2"><input type="hidden" name="is_paid" value="0" /><input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $leaveType?->is_paid ?? true))> Paid</label>
        <label class="flex items-center gap-2"><input type="hidden" name="carry_forward" value="0" /><input type="checkbox" name="carry_forward" value="1" @checked(old('carry_forward', $leaveType?->carry_forward ?? false))> Carry forward</label>
        <label class="flex items-center gap-2"><input type="hidden" name="encashable" value="0" /><input type="checkbox" name="encashable" value="1" @checked(old('encashable', $leaveType?->encashable ?? false))> Encashable</label>
    </div>
    <div class="md:col-span-3"><label class="text-xs font-semibold text-gray-500 uppercase">Description</label><textarea name="description" rows="2" class="form-input mt-1">{{ old('description', $leaveType?->description) }}</textarea></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
        <select name="status" required class="form-select mt-1">
            <option value="active" @selected(old('status', $leaveType?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $leaveType?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
