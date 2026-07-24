<x-layout.admin title="Salary Templates">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Salary Templates']]" />
    <h1 class="text-2xl font-extrabold mb-1">Salary Templates</h1>
    <p class="text-sm text-gray-500 mb-5">Define department / category templates and apply them to many employees at once.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel overflow-x-auto">
            <table class="table-striped w-full">
                <thead><tr><th>Name</th><th>Scope</th><th>Gross/mo</th><th></th></tr></thead>
                <tbody>
                    @forelse($templates as $t)
                        <tr>
                            <td class="font-semibold">{{ $t->name }}</td>
                            <td class="text-sm">{{ ucfirst($t->level) }}{{ $t->department ? ' · '.$t->department->name : ($t->employee_category ? ' · '.$t->employee_category : '') }}</td>
                            <td>{{ number_format($t->gross_monthly, 2) }}</td>
                            <td class="text-right">
                                @can('salary_templates.manage')
                                    <a href="{{ route('admin.hr.salary-templates.assign-form', $t) }}" class="text-primary text-sm font-semibold">Apply</a>
                                    <form method="POST" action="{{ route('admin.hr.salary-templates.destroy', $t) }}" class="inline ml-2" onsubmit="return confirm('Delete template?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-400 py-8">No templates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">New Template</div>
            @can('salary_templates.manage')
            <form method="POST" action="{{ route('admin.hr.salary-templates.store') }}" class="space-y-2">
                @csrf
                <input name="name" placeholder="Template name" class="form-input" required>
                <select name="level" class="form-select" onchange="document.getElementById('deptBox').style.display=this.value==='department'?'block':'none';document.getElementById('catBox').style.display=this.value==='category'?'block':'none';">
                    <option value="generic">Generic</option>
                    <option value="department">Department</option>
                    <option value="category">Employee Category</option>
                </select>
                <div id="deptBox" style="display:none"><select name="department_id" class="form-select"><option value="">— Department —</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div id="catBox" style="display:none"><input name="employee_category" placeholder="e.g. permanent / contract" class="form-input"></div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.01" name="basic" placeholder="Basic" class="form-input" required>
                    <input type="number" step="0.01" name="hra" placeholder="HRA" class="form-input">
                    <input type="number" step="0.01" name="conveyance" placeholder="Conveyance" class="form-input">
                    <input type="number" step="0.01" name="medical" placeholder="Medical" class="form-input">
                    <input type="number" step="0.01" name="special" placeholder="Special" class="form-input">
                    <input type="number" step="0.01" name="other_allowance" placeholder="Other" class="form-input">
                    <input type="number" step="0.01" name="pf_percent" value="12" placeholder="PF %" class="form-input">
                    <input type="number" step="0.01" name="esi_percent" value="0.75" placeholder="ESI %" class="form-input">
                    <input type="number" step="0.01" name="professional_tax" placeholder="PT" class="form-input">
                </div>
                <button class="btn btn-primary w-full">Create</button>
            </form>
            @endcan
        </div>
    </div>
</x-layout.admin>
