<x-layout.admin title="Asset Statuses">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Statuses']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Asset Statuses</h1>
            <p class="text-sm text-gray-500 mt-1">Manage the options shown in the Asset "Status" dropdown. Disable defaults you don't use; add your own custom statuses.</p>
        </div>
    </div>

    @if(session('success'))<div class="alert bg-success/15 text-success mb-3 p-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert bg-danger/15 text-danger mb-3 p-3 rounded">{{ session('error') }}</div>@endif

    @can('asset_statuses.create')
    <div class="panel mb-4">
        <h3 class="font-bold mb-3">Add new status</h3>
        <form method="POST" action="{{ route('admin.assets.statuses.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="form-label">Label *</label>
                <input type="text" name="label" required maxlength="100" placeholder="e.g. Sold Out To Advisors" class="form-input" value="{{ old('label') }}" />
                @error('label')<div class="text-xs text-danger mt-1">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label">Badge colour</label>
                <select name="color" class="form-select">
                    @foreach(['secondary'=>'Gray','primary'=>'Blue','info'=>'Cyan','success'=>'Green','warning'=>'Orange','danger'=>'Red'] as $v=>$l)
                        <option value="{{ $v }}" @selected(old('color')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <button class="btn btn-primary">+ Add Status</button>
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
                    <th>Badge</th>
                    <th>Type</th>
                    <th>Active</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statuses as $s)
                    <tr>
                        <td class="font-semibold">{{ $s->label }}</td>
                        <td class="font-mono text-xs text-gray-500">{{ $s->key }}</td>
                        <td><span class="px-2 py-0.5 rounded text-xs font-semibold bg-{{ $s->color ?? 'secondary' }}/15 text-{{ $s->color ?? 'secondary' }}">{{ $s->label }}</span></td>
                        <td><span class="text-xs {{ $s->is_system ? 'text-primary font-semibold' : 'text-gray-500' }}">{{ $s->is_system ? 'System default' : 'Custom' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.assets.statuses.toggle', $s) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded text-xs font-semibold {{ $s->is_active ? 'bg-success/15 text-success' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $s->is_active ? 'Active' : 'Disabled' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-right space-x-2">
                            @can('asset_statuses.edit')
                                <button type="button" class="text-info text-xs"
                                        x-data
                                        @click="$dispatch('open-status-edit', {{ json_encode(['id'=>$s->id,'label'=>$s->label,'color'=>$s->color,'is_active'=>$s->is_active]) }})">
                                    Edit
                                </button>
                            @endcan
                            @can('asset_statuses.delete')
                                @if(! $s->is_system)
                                    <form method="POST" action="{{ route('admin.assets.statuses.destroy', $s) }}" class="inline" onsubmit="return confirm('Delete this status?')">
                                        @csrf @method('DELETE')
                                        <button class="text-danger text-xs">Delete</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-6">No statuses configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $statuses->links() }}</div>

    {{-- Edit modal --}}
    <div x-data="{ open: false, payload: { id: null, label: '', color: 'secondary', is_active: true } }"
         x-on:open-status-edit.window="payload = { ...$event.detail }; open = true"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-[#1b2e4b] rounded-lg p-6 w-full max-w-md" @click.outside="open = false">
            <h3 class="font-bold text-lg mb-4">Edit status</h3>
            <form :action="`{{ url('admin/assets/statuses') }}/${payload.id}`" method="POST">
                @csrf @method('PATCH')
                <div class="mb-3">
                    <label class="form-label">Label *</label>
                    <input type="text" name="label" required maxlength="100" class="form-input" x-model="payload.label" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Badge colour</label>
                    <select name="color" class="form-select" x-model="payload.color">
                        @foreach(['secondary'=>'Gray','primary'=>'Blue','info'=>'Cyan','success'=>'Green','warning'=>'Orange','danger'=>'Red'] as $v=>$l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
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
