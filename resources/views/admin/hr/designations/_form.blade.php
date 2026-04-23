@props(['designation' => null, 'departments'])
<div class="panel p-5 grid grid-cols-2 gap-4">
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">Name *</label>
        <input type="text" name="name" value="{{ old('name', $designation?->name) }}" required class="form-input mt-1" />
    </div>
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">Department</label>
        <select name="department_id" class="form-select mt-1">
            <option value="">None</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $designation?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">Level</label>
        <select name="level" class="form-select mt-1">
            <option value="">—</option>
            @foreach(['Junior','Mid','Senior','Lead','Principal'] as $l)
                <option value="{{ $l }}" @selected(old('level', $designation?->level) === $l)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
        <select name="status" required class="form-select mt-1">
            <option value="active" @selected(old('status', $designation?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $designation?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
