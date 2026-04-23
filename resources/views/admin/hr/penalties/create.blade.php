<x-layout.admin title="Add Penalty">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Penalties', 'url' => route('admin.hr.penalties.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">Add Penalty</h1>

    <form method="POST" action="{{ route('admin.hr.penalties.store') }}"
          x-data="{
              types: { {{ $types->map(fn($t) => $t->id.': '.$t->default_amount)->implode(',') }} },
              amount: '',
              setAmount(id) { this.amount = this.types[id] ?? 0; }
          }"
          class="panel p-6 max-w-3xl space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Employee *</label>
                <select name="employee_id" required class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}" @selected($preselect == $e->id)>{{ $e->employee_code }} · {{ $e->full_name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Penalty Type *</label>
                <select name="penalty_type_id" required class="form-select mt-1" @change="setAmount($event.target.value)">
                    <option value="">Select</option>
                    @foreach($types as $t)<option value="{{ $t->id }}">{{ $t->name }} (default ₹{{ number_format($t->default_amount, 2) }})</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Amount *</label>
                <input type="number" step="0.01" name="amount" x-model="amount" required min="0" class="form-input mt-1 text-lg font-bold" />
                <p class="text-[11px] text-gray-500 mt-1">Admin can override the default amount.</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Incident Date *</label>
                <input type="date" name="incident_date" value="{{ date('Y-m-d') }}" required class="form-input mt-1" />
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Remarks</label>
                <textarea name="remarks" rows="3" class="form-input mt-1" placeholder="Context of the incident..."></textarea>
            </div>
        </div>

        <div class="text-sm bg-info/10 text-info border border-info/30 p-3 rounded">
            <strong>PIP Note:</strong> The penalty can be reduced or waived after <strong>5 months</strong> from the incident date, provided the employee hasn't received another penalty in between. Pending penalties are auto-deducted on the next payslip.
        </div>

        <div class="flex gap-3">
            <button class="btn btn-primary">Record Penalty</button>
            <a href="{{ route('admin.hr.penalties.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
