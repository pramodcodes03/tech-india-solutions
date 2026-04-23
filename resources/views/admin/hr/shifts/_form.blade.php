@props(['shift' => null])
<div class="panel p-5 grid grid-cols-2 md:grid-cols-3 gap-4">
    <div class="md:col-span-3"><label class="text-xs font-semibold text-gray-500 uppercase">Name *</label><input type="text" name="name" value="{{ old('name', $shift?->name) }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Start *</label><input type="time" name="start_time" value="{{ old('start_time', $shift?->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '09:30') }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">End *</label><input type="time" name="end_time" value="{{ old('end_time', $shift?->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '18:30') }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Grace (minutes) *</label><input type="number" name="grace_minutes" value="{{ old('grace_minutes', $shift?->grace_minutes ?? 10) }}" required min="0" max="120" class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Half-day after (min) *</label><input type="number" name="half_day_after_minutes" value="{{ old('half_day_after_minutes', $shift?->half_day_after_minutes ?? 120) }}" required min="30" class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
        <select name="status" required class="form-select mt-1">
            <option value="active" @selected(old('status', $shift?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $shift?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
