<x-layout.admin title="Issue Warning">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Warnings', 'url' => route('admin.hr.warnings.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">Issue Warning</h1>

    <form method="POST" action="{{ route('admin.hr.warnings.store') }}" class="panel p-6 max-w-3xl space-y-4">
        @csrf
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Employee *</label>
                <select name="employee_id" required class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected($preselect == $e->id)>{{ $e->employee_code }} · {{ $e->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Warning Level *</label>
                <select name="level" required class="form-select mt-1">
                    <option value="1">Level 1 — HR Warning</option>
                    <option value="2">Level 2 — Manager Warning</option>
                    <option value="3">Level 3 — Director / Termination-track</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Title *</label>
                <input type="text" name="title" required maxlength="200" class="form-input mt-1" placeholder="E.g. Repeated late arrivals" />
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Reason *</label>
                <textarea name="reason" rows="5" required class="form-input mt-1" placeholder="Describe the incident(s), impact, and prior discussions..."></textarea>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Action Required</label>
                <textarea name="action_required" rows="3" class="form-input mt-1" placeholder="E.g. Improvement expected within 30 days..."></textarea>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Issued On *</label>
                <input type="date" name="issued_on" value="{{ date('Y-m-d') }}" required class="form-input mt-1" />
            </div>
        </div>

        <div class="text-sm bg-warning/10 text-warning border border-warning/30 p-3 rounded">
            <strong>Note:</strong> Issuing a Level-3 warning will automatically move the employee's status to <code>on_notice</code>.
        </div>

        <div class="flex gap-3">
            <button class="btn btn-primary">Issue Warning</button>
            <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
