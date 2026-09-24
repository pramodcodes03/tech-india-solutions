<x-layout.admin title="Issue Warning">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Warnings', 'url' => route('admin.hr.warnings.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">Issue Warning</h1>

    <form method="POST" action="{{ route('admin.hr.warnings.store') }}" class="panel p-6 max-w-3xl space-y-4"
        x-data="{ level: '{{ array_key_first(\App\Models\Warning::LEVELS) }}' }"
        @submit="if (level == '{{ \App\Models\Warning::TERMINATION_LEVELS[0] }}' && ! confirm('ZTP will TERMINATE this employee immediately. Continue?')) $event.preventDefault()">
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
                <select name="level" required class="form-select mt-1" x-model="level">
                    @foreach(\App\Models\Warning::LEVELS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
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

        {{-- Consequence note. Only ZTP changes the employee's status. --}}
        <div class="text-sm bg-danger/10 text-danger border border-danger/30 p-3 rounded"
            x-show="level == '{{ \App\Models\Warning::TERMINATION_LEVELS[0] }}'" x-cloak>
            <strong>Warning:</strong> Issuing a <strong>ZTP — Zero Tolerance Policy</strong> will set the employee's status to
            <code>terminated</code> and record the issue date as their last working date. They will be excluded from payroll,
            attendance import and biometric sync.
        </div>

        <div class="flex gap-3">
            <button class="btn btn-primary">Issue Warning</button>
            <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
