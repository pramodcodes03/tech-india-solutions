<x-layout.admin title="Penalty Types">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Penalties', 'url' => route('admin.hr.penalties.index')], ['label' => 'Types']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Penalty Types</h1>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-5 panel p-5">
            <h3 class="font-bold mb-3">Add New Type</h3>
            <form method="POST" action="{{ route('admin.hr.penalty-types.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-xs font-semibold text-gray-500 uppercase">Name *</label><input type="text" name="name" required class="form-input mt-1" /></div>
                <div><label class="text-xs font-semibold text-gray-500 uppercase">Default Amount *</label><input type="number" step="0.01" name="default_amount" required min="0" class="form-input mt-1" /></div>
                <div><label class="text-xs font-semibold text-gray-500 uppercase">Description</label><textarea name="description" rows="2" class="form-input mt-1"></textarea></div>
                <div><label class="text-xs font-semibold text-gray-500 uppercase">Status *</label><select name="status" required class="form-select mt-1"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                <button class="btn btn-primary">Add</button>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-7 panel p-0 overflow-x-auto">
            <table class="table-striped"><thead><tr><th>Name</th><th>Default</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($types as $t)
                        <tr>
                            <td><strong>{{ $t->name }}</strong>@if($t->description)<div class="text-xs text-gray-500">{{ $t->description }}</div>@endif</td>
                            <td>₹{{ number_format($t->default_amount, 2) }}</td>
                            <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $t->status === 'active'])>{{ ucfirst($t->status) }}</span></td>
                            <td><button class="text-info text-xs" onclick="document.getElementById('edit-{{ $t->id }}').classList.toggle('hidden')">Edit</button></td>
                        </tr>
                        <tr id="edit-{{ $t->id }}" class="hidden">
                            <td colspan="4" class="bg-gray-50 dark:bg-dark-light/20 p-4">
                                <form method="POST" action="{{ route('admin.hr.penalty-types.update', $t) }}" class="grid grid-cols-4 gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $t->name }}" required class="form-input col-span-2" />
                                    <input type="number" step="0.01" name="default_amount" value="{{ $t->default_amount }}" required class="form-input" />
                                    <select name="status" class="form-select">
                                        <option value="active" @selected($t->status === 'active')>Active</option>
                                        <option value="inactive" @selected($t->status === 'inactive')>Inactive</option>
                                    </select>
                                    <input type="text" name="description" value="{{ $t->description }}" placeholder="Description (optional)" class="form-input col-span-3" />
                                    <button class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-500 py-6">No penalty types.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
