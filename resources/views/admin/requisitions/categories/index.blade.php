<x-layout.admin title="Requisition Categories">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Requisitions', 'url' => route('admin.requisitions.index')], ['label' => 'Categories']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Requisition Categories</h1>
            <p class="text-sm text-gray-500 mt-1">Manage the options shown in the Purchase Requisition "Category" dropdown. Add your own, rename them, or disable defaults you don't use.</p>
        </div>
        <a href="{{ route('admin.requisitions.index') }}" class="btn btn-outline-secondary">Back to Requisitions</a>
    </div>

    @if(session('success'))<div class="alert bg-success/15 text-success mb-3 p-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert bg-danger/15 text-danger mb-3 p-3 rounded">{{ session('error') }}</div>@endif

    @can('requisition_categories.create')
    <div class="panel mb-4">
        <h3 class="font-bold mb-3">Add new category</h3>
        <form method="POST" action="{{ route('admin.requisition-categories.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="form-label">Label *</label>
                <input type="text" name="label" required maxlength="100" placeholder="e.g. Software Licenses" class="form-input" value="{{ old('label') }}" />
                @error('label')<div class="text-xs text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div>
                <button class="btn btn-primary">+ Add Category</button>
            </div>
        </form>
    </div>
    @endcan

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Key (slug)</th>
                    <th>Type</th>
                    <th>Active</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $c)
                    <tr>
                        <td class="font-semibold">{{ $c->label }}</td>
                        <td class="font-mono text-xs text-gray-500">{{ $c->key }}</td>
                        <td><span class="text-xs {{ $c->is_system ? 'text-primary font-semibold' : 'text-gray-500' }}">{{ $c->is_system ? 'System default' : 'Custom' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.requisition-categories.toggle', $c) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded text-xs font-semibold {{ $c->is_active ? 'bg-success/15 text-success' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $c->is_active ? 'Active' : 'Disabled' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-right space-x-2">
                            @can('requisition_categories.edit')
                                <button type="button" class="text-info text-xs"
                                        x-data
                                        @click="$dispatch('open-category-edit', {{ json_encode(['id'=>$c->id,'label'=>$c->label,'is_active'=>$c->is_active]) }})">
                                    Edit
                                </button>
                            @endcan
                            @can('requisition_categories.delete')
                                @if(! $c->is_system)
                                    <form method="POST" action="{{ route('admin.requisition-categories.destroy', $c) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <button class="text-danger text-xs">Delete</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-500 py-6">No categories configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $categories->links() }}</div>

    {{-- Edit modal --}}
    <div x-data="{ open: false, payload: { id: null, label: '', is_active: true } }"
         x-on:open-category-edit.window="payload = { ...$event.detail }; open = true"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-[#1b2e4b] rounded-lg p-6 w-full max-w-md" @click.outside="open = false">
            <h3 class="font-bold text-lg mb-4">Edit category</h3>
            <form :action="`{{ url('admin/requisition-categories') }}/${payload.id}`" method="POST">
                @csrf @method('PATCH')
                <div class="mb-3">
                    <label class="form-label">Label *</label>
                    <input type="text" name="label" required maxlength="100" class="form-input" x-model="payload.label" />
                </div>
                <label class="flex items-center gap-2 mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="payload.is_active">
                    <span class="text-sm">Active (visible in the dropdown)</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
