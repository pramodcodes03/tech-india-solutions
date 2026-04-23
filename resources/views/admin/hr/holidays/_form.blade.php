@props(['holiday' => null])
<div class="panel p-5 grid grid-cols-2 gap-4">
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Name *</label><input type="text" name="name" value="{{ old('name', $holiday?->name) }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Date *</label><input type="date" name="date" value="{{ old('date', $holiday?->date?->format('Y-m-d')) }}" required class="form-input mt-1" /></div>
    <div><label class="text-xs font-semibold text-gray-500 uppercase">Type *</label>
        <select name="type" required class="form-select mt-1">
            @foreach(['public','optional','restricted'] as $t)
                <option value="{{ $t }}" @selected(old('type', $holiday?->type ?? 'public') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">Description</label><input type="text" name="description" value="{{ old('description', $holiday?->description) }}" class="form-input mt-1" /></div>
</div>
