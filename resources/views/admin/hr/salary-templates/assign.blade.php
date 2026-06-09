<x-layout.admin title="Apply Template">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Salary Templates', 'url' => route('admin.hr.salary-templates.index')], ['label' => 'Apply']]" />
    <h1 class="text-2xl font-extrabold mb-1">Apply Template — {{ $template->name }}</h1>
    <p class="text-sm text-gray-500 mb-5">Creates a new approved salary structure for each selected employee from this template. Existing current structures are end-dated.</p>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <form method="POST" action="{{ route('admin.hr.salary-templates.assign', $template) }}" class="panel p-6">
        @csrf
        <div class="mb-4">
            <label class="text-xs font-semibold text-gray-500 uppercase">Effective From *</label>
            <input type="date" name="effective_from" value="{{ date('Y-m-d') }}" class="form-input mt-1 w-auto" required>
        </div>
        <div class="mb-3 flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" onclick="document.querySelectorAll('.empChk').forEach(c=>c.checked=this.checked)"> Select all ({{ $employees->count() }})</label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto mb-4">
            @forelse($employees as $e)
                <label class="flex items-center gap-2 text-sm p-2 rounded border border-gray-100">
                    <input type="checkbox" name="employee_ids[]" value="{{ $e->id }}" class="empChk">
                    {{ $e->full_name }} <span class="text-gray-400 text-xs">({{ $e->employee_code }})</span>
                </label>
            @empty
                <p class="text-gray-400 col-span-3">No employees match this template's scope.</p>
            @endforelse
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary" onclick="return confirm('Apply this template to the selected employees?')">Apply Template</button>
            <a href="{{ route('admin.hr.salary-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
