<x-layout.admin title="Bulk Asset Operations">
    <x-admin.breadcrumb :items="[['label' => 'Assets', 'url' => route('admin.assets.assets.index')], ['label' => 'Bulk Operations']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Bulk Operations</h1>
            <p class="text-sm text-gray-500">Select multiple assets and apply one action to all of them.</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    {{-- Filters --}}
    <form method="GET" class="panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code / name / serial" class="form-input md:col-span-2">
        <select name="category_id" class="form-select"><option value="">All Categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="location_id" class="form-select"><option value="">All Locations</option>@foreach($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id')==$l->id)>{{ $l->name }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <form method="POST" action="{{ route('admin.assets.bulk.apply') }}" id="bulkForm">
        @csrf
        {{-- Action bar --}}
        <div class="panel p-4 mb-4 sticky top-0 z-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-3">
                    <label class="text-xs text-gray-500">Action *</label>
                    <select name="action" id="actionSelect" class="form-select" onchange="toggleActionFields()" required>
                        <option value="">— Select action —</option>
                        <option value="assign">Assign to employee</option>
                        <option value="change_category">Change category</option>
                        <option value="change_status">Change status</option>
                        <option value="change_condition">Change condition</option>
                        <option value="change_location">Change location</option>
                        <option value="transfer_location">Transfer location (logged)</option>
                        <option value="edit">Multi-field edit</option>
                        <option value="delete">Delete</option>
                    </select>
                </div>
                <div class="md:col-span-2 hidden" data-field="employee_id"><label class="text-xs text-gray-500">Employee</label><select name="employee_id" class="form-select"><option value="">—</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
                <div class="md:col-span-2 hidden" data-field="category_id"><label class="text-xs text-gray-500">Category</label><select name="category_id" class="form-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="md:col-span-2 hidden" data-field="location_id"><label class="text-xs text-gray-500">Location</label><select name="location_id" class="form-select"><option value="">—</option>@foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
                <div class="md:col-span-2 hidden" data-field="status"><label class="text-xs text-gray-500">Status</label><select name="status" class="form-select"><option value="">—</option>@foreach($assetStatuses as $s)<option value="{{ $s->key }}">{{ $s->label }}</option>@endforeach</select></div>
                <div class="md:col-span-2 hidden" data-field="condition_rating"><label class="text-xs text-gray-500">Condition</label><select name="condition_rating" class="form-select"><option value="">—</option>@foreach(['excellent','good','fair','poor','damaged'] as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select></div>
                <div class="md:col-span-3 hidden" data-field="notes"><label class="text-xs text-gray-500">Notes</label><input name="notes" class="form-input" placeholder="Optional"></div>
                <div class="md:col-span-2">
                    <button class="btn btn-primary w-full" onclick="return confirmBulk()"><span id="selCount">0</span> selected — Apply</button>
                </div>
            </div>
        </div>

        <div class="panel overflow-x-auto">
            <table class="table-striped w-full">
                <thead><tr>
                    <th><input type="checkbox" id="checkAll" onclick="toggleAll(this)"></th>
                    <th>Code</th><th>Name</th><th>Category</th><th>Location</th><th>Custodian</th><th>Status</th><th>Condition</th>
                </tr></thead>
                <tbody>
                    @forelse($assets as $a)
                        <tr>
                            <td><input type="checkbox" name="asset_ids[]" value="{{ $a->id }}" class="assetCheck" onchange="updateCount()"></td>
                            <td class="font-mono text-xs">{{ $a->asset_code }}</td>
                            <td>{{ $a->name }}</td>
                            <td>{{ $a->category?->name ?? '—' }}</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td>{{ $a->custodian?->full_name ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$a->status)) }}</td>
                            <td>{{ ucfirst($a->condition_rating) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-gray-400 py-8">No assets match the filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    <div class="mt-4">{{ $assets->links() }}</div>

    <script>
        const fieldMap = {
            assign: ['employee_id','notes'], change_category: ['category_id'], change_status: ['status'],
            change_condition: ['condition_rating'], change_location: ['location_id'],
            transfer_location: ['location_id','notes'], edit: ['category_id','location_id','status','condition_rating'], delete: []
        };
        function toggleActionFields() {
            const act = document.getElementById('actionSelect').value;
            const show = fieldMap[act] || [];
            document.querySelectorAll('[data-field]').forEach(el => {
                el.classList.toggle('hidden', !show.includes(el.getAttribute('data-field')));
            });
        }
        function toggleAll(cb) { document.querySelectorAll('.assetCheck').forEach(c => c.checked = cb.checked); updateCount(); }
        function updateCount() { document.getElementById('selCount').textContent = document.querySelectorAll('.assetCheck:checked').length; }
        function confirmBulk() {
            const n = document.querySelectorAll('.assetCheck:checked').length;
            const act = document.getElementById('actionSelect').value;
            if (!n) { alert('Select at least one asset.'); return false; }
            if (!act) { alert('Select an action.'); return false; }
            return confirm(`Apply "${act}" to ${n} asset(s)?`);
        }
    </script>
</x-layout.admin>
