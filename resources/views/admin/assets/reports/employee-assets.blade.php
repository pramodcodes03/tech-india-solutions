<x-layout.admin title="Employee Asset Report">
    <x-admin.breadcrumb :items="[['label' => 'Assets', 'url' => route('admin.assets.assets.index')], ['label' => 'Employee Assets']]" />
    <h1 class="text-2xl font-extrabold mb-4">Assets Held by Employee</h1>

    <form method="GET" class="panel p-4 mb-5 flex gap-3 flex-wrap items-end">
        <div>
            <label class="text-xs text-gray-500">Employee</label>
            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Select Employee —</option>
                @foreach($employees as $e)<option value="{{ $e->id }}" @selected(request('employee_id')==$e->id)>{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
            </select>
        </div>
    </form>

    @if($employee)
        <div class="panel overflow-x-auto">
            <div class="p-4 border-b font-semibold">{{ $employee->full_name }} — {{ $assets->count() }} asset(s)</div>
            <table class="table-striped w-full">
                <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Location</th><th>Status</th><th>Condition</th><th>Book Value</th></tr></thead>
                <tbody>
                    @forelse($assets as $a)
                        <tr>
                            <td class="font-mono text-xs">{{ $a->asset_code }}</td>
                            <td><a href="{{ route('admin.assets.reports.asset-history', $a) }}" class="text-primary">{{ $a->name }}</a></td>
                            <td>{{ $a->category?->name ?? '—' }}</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$a->status)) }}</td>
                            <td>{{ ucfirst($a->condition_rating) }}</td>
                            <td>{{ number_format($a->current_book_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-8">No assets currently assigned to this employee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-layout.admin>
