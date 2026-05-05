<x-layout.admin title="{{ $category->name }}">
    <x-admin.breadcrumb :items="[['label' => 'Routine Payment Tracker', 'url' => route('admin.expenses.index')], ['label' => 'Categories', 'url' => route('admin.expense-categories.index')], ['label' => $category->name]]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold dark:text-white-light flex items-center gap-2">
            @if($category->color)<span class="inline-block w-4 h-4 rounded-full" style="background:{{ $category->color }}"></span>@endif
            {{ $category->name }}
        </h5>
        <div class="flex items-center gap-2">
            @can('expense_categories.edit')<a href="{{ route('admin.expense-categories.edit', $category) }}" class="btn btn-outline-warning">Edit Category</a>@endcan
            <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4"><ul class="list-disc ml-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="panel mb-4">
        @if($category->description)
            <p class="text-gray-600 dark:text-gray-300">{{ $category->description }}</p>
        @endif
        <div class="text-xs text-gray-500 mt-2">Slug: <code>{{ $category->slug }}</code> · Status: {{ $category->is_active ? 'Active' : 'Inactive' }}</div>
    </div>

    <div class="panel" x-data="{ openId: null, addOpen: false }">
        <div class="flex items-center justify-between mb-3">
            <h6 class="font-semibold">Subcategories ({{ $category->subcategories->count() }})</h6>
            @can('expense_categories.edit')
                <button type="button" class="btn btn-sm btn-primary" @click="addOpen = !addOpen">
                    <span x-show="!addOpen">+ Add Subcategory</span>
                    <span x-show="addOpen">Cancel</span>
                </button>
            @endcan
        </div>

        <div x-show="addOpen" x-cloak x-transition class="mb-4 border rounded p-4 bg-gray-50 dark:bg-dark/30">
            <form method="POST" action="{{ route('admin.expense-categories.subcategories.store', $category) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label text-xs">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-input form-input-sm" required>
                    </div>
                    <div>
                        <label class="form-label text-xs">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-input form-input-sm" pattern="[a-z0-9\-_]+" required>
                    </div>
                    <div>
                        <label class="form-label text-xs">Description</label>
                        <input type="text" name="description" class="form-input form-input-sm">
                    </div>
                </div>
                <div class="flex justify-end mt-3">
                    <button type="submit" class="btn btn-success btn-sm">Add</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 !text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($category->subcategories as $sub)
                        <tr>
                            <td class="px-4 py-2 font-semibold">{{ $sub->name }}</td>
                            <td class="px-4 py-2"><code>{{ $sub->slug }}</code></td>
                            <td class="px-4 py-2">{{ $sub->description ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if($sub->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-warning">Inactive</span>@endif
                            </td>
                            <td class="px-4 py-2 !text-center">
                                @can('expense_categories.edit')
                                    <button type="button" class="btn btn-sm btn-outline-warning" @click="openId = openId === {{ $sub->id }} ? null : {{ $sub->id }}">
                                        <span x-show="openId !== {{ $sub->id }}">Edit</span>
                                        <span x-show="openId === {{ $sub->id }}">Close</span>
                                    </button>
                                @endcan
                                @can('expense_categories.delete')
                                    <form method="POST" action="{{ route('admin.expense-categories.subcategories.destroy', [$category, $sub]) }}" class="inline" onsubmit="return confirm('Remove subcategory?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                        <tr x-show="openId === {{ $sub->id }}" x-cloak>
                            <td colspan="5" class="px-4 py-3 bg-gray-50 dark:bg-dark/30">
                                <form method="POST" action="{{ route('admin.expense-categories.subcategories.update', [$category, $sub]) }}">
                                    @csrf @method('PUT')
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="form-label text-xs">Name</label>
                                            <input type="text" name="name" class="form-input form-input-sm" value="{{ $sub->name }}" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="form-label text-xs">Description</label>
                                            <input type="text" name="description" class="form-input form-input-sm" value="{{ $sub->description }}">
                                        </div>
                                        <div>
                                            <label class="inline-flex items-center gap-2 mt-2">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ $sub->is_active ? 'checked' : '' }}>
                                                <span>Active</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-3 gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="openId = null">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-gray-500">No subcategories yet — they're optional.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
