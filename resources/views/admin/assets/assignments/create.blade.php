<x-layout.admin title="Assign Asset">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Assignments', 'url' => route('admin.assets.assignments.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Assignment</h1>

    <form method="POST" action="{{ route('admin.assets.assignments.store') }}">
        @csrf
        <div class="panel grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Asset <span class="text-danger">*</span></label>
                <select name="asset_id" class="form-select" required>
                    <option value="">— Select asset —</option>
                    @foreach($assets as $a)
                        <option value="{{ $a->id }}" @selected(($asset?->id ?? old('asset_id')) == $a->id)>{{ $a->asset_code }} · {{ $a->name }} ({{ $a->status_label }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select" required>
                    <option value="">—</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">To Location</label>
                <select name="to_location_id" class="form-select">
                    <option value="">—</option>
                    @foreach($locations as $l)<option value="{{ $l->id }}" @selected(old('to_location_id', $asset?->location_id) == $l->id)>{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Assigned Date <span class="text-danger">*</span></label>
                <input type="date" name="assigned_at" value="{{ old('assigned_at', now()->toDateString()) }}" class="form-input" required />
            </div>
            <div>
                <label class="form-label">Condition at Assign</label>
                <select name="condition_at_assign" class="form-select">
                    @foreach(['excellent','good','fair','poor','damaged'] as $c)<option value="{{ $c }}" @selected(old('condition_at_assign', $asset?->condition_rating ?? 'good') === $c)>{{ ucfirst($c) }}</option>@endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2"></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Assign</button>
            <a href="{{ route('admin.assets.assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
