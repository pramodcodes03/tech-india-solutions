@php $category ??= null; @endphp

@if ($errors->any())
    <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">
        <ul class="list-disc ml-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-input" value="{{ old('name', $category?->name) }}" required>
    </div>
    <div>
        <label class="form-label">Slug <span class="text-danger">*</span></label>
        <input type="text" name="slug" class="form-input" value="{{ old('slug', $category?->slug) }}" pattern="[a-z0-9\-_]+" required>
        <p class="text-xs text-gray-500 mt-1">Lowercase, no spaces. e.g. office-rent</p>
    </div>
    <div class="md:col-span-2">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-textarea" rows="2">{{ old('description', $category?->description) }}</textarea>
    </div>
    <div>
        <label class="form-label">Color tag</label>
        <input type="color" name="color" class="form-input h-10" value="{{ old('color', $category?->color ?? '#3b82f6') }}">
    </div>
    <div class="flex items-end">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ old('is_active', $category?->is_active ?? true) ? 'checked' : '' }}>
            <span>Active</span>
        </label>
    </div>
</div>
