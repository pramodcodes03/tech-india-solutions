<x-layout.admin title="Add Category">
    <div x-data="{ name: '{{ old('name') }}', slug: '{{ old('slug') }}', isActive: {{ old('is_active', '1') ? 'true' : 'false' }} }">
        <x-admin.breadcrumb :items="[['label'=>'Categories','url'=>route('admin.categories.index')],['label'=>'Add Category']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Add Category</h5>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <div class="panel">
            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" x-model="name"
                            @input="slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')" required />
                    </div>
                    <div>
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" type="text" class="form-input" x-model="slug" />
                    </div>
                    <div>
                        <label for="parent_id">Parent Category</label>
                        <x-admin.searchable-select name="parent_id" :options="$parentCategories" placeholder="-- None --" />
                    </div>
                    <div>
                        <label for="sort_order">Sort Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" class="form-input" value="{{ old('sort_order', 0) }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3" placeholder="Category description...">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label for="is_active" class="flex items-center gap-3 cursor-pointer">
                            <input id="is_active" name="is_active" type="checkbox" class="form-checkbox" value="1" x-model="isActive" />
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
