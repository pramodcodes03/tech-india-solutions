<x-layout.admin title="Salary Structure">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees', 'url' => route('admin.hr.employees.index')], ['label' => $employee->employee_code, 'url' => route('admin.hr.employees.show', $employee)], ['label' => 'Salary']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Salary Structure · {{ $employee->full_name }}</h1>
        <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <form method="POST" action="{{ route('admin.hr.salary.store', $employee) }}"
          x-data="{
              ctc: '{{ old('ctc_annual', $current?->ctc_annual ?? 0) }}',
              basic: '{{ old('basic', $current?->basic ?? 0) }}',
              hra: '{{ old('hra', $current?->hra ?? 0) }}',
              conveyance: '{{ old('conveyance', $current?->conveyance ?? 1600) }}',
              medical: '{{ old('medical', $current?->medical ?? 1250) }}',
              special: '{{ old('special', $current?->special ?? 0) }}',
              other_allowance: '{{ old('other_allowance', $current?->other_allowance ?? 0) }}',
              get gross() { return (+this.basic) + (+this.hra) + (+this.conveyance) + (+this.medical) + (+this.special) + (+this.other_allowance); },
              rebuild() {
                  const c = +this.ctc;
                  if (c <= 0) return;
                  const m = c / 12;
                  this.basic = (Math.round(m * 0.5 * 100)/100).toFixed(2);
                  this.hra = (Math.round(+this.basic * 0.4 * 100)/100).toFixed(2);
                  this.conveyance = '1600.00';
                  this.medical = '1250.00';
                  const rem = m - (+this.basic) - (+this.hra) - 1600 - 1250;
                  this.special = (Math.round(Math.max(0, rem) * 100)/100).toFixed(2);
                  this.other_allowance = '0.00';
              }
          }"
          class="grid grid-cols-12 gap-4">
        @csrf

        <div class="col-span-12 lg:col-span-8 panel p-6 space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">Annual CTC *</label>
                    <input type="number" step="0.01" name="ctc_annual" x-model="ctc" required min="0" class="form-input mt-1 text-lg font-bold" />
                </div>
                <div class="flex items-end">
                    <button type="button" @click="rebuild()" class="btn btn-outline-primary w-full">Auto-calculate</button>
                </div>

                <div><label class="text-xs text-gray-500 uppercase font-semibold">Basic</label><input type="number" step="0.01" name="basic" x-model="basic" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">HRA</label><input type="number" step="0.01" name="hra" x-model="hra" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Conveyance</label><input type="number" step="0.01" name="conveyance" x-model="conveyance" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Medical</label><input type="number" step="0.01" name="medical" x-model="medical" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Special</label><input type="number" step="0.01" name="special" x-model="special" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Other Allowance</label><input type="number" step="0.01" name="other_allowance" x-model="other_allowance" class="form-input mt-1" /></div>

                <div><label class="text-xs text-gray-500 uppercase font-semibold">PF %</label><input type="number" step="0.01" name="pf_percent" value="{{ old('pf_percent', $current?->pf_percent ?? 12) }}" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">ESI %</label><input type="number" step="0.01" name="esi_percent" value="{{ old('esi_percent', $current?->esi_percent ?? 0.75) }}" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Professional Tax</label><input type="number" step="0.01" name="professional_tax" value="{{ old('professional_tax', $current?->professional_tax ?? 200) }}" required class="form-input mt-1" /></div>
                <div class="md:col-span-2"><label class="text-xs text-gray-500 uppercase font-semibold">Monthly TDS</label><input type="number" step="0.01" name="monthly_tds" value="{{ old('monthly_tds', $current?->monthly_tds ?? 0) }}" required class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 uppercase font-semibold">Effective From *</label><input type="date" name="effective_from" value="{{ old('effective_from', date('Y-m-d')) }}" required class="form-input mt-1" /></div>
                <div class="md:col-span-3"><label class="text-xs text-gray-500 uppercase font-semibold">Notes</label><textarea name="notes" rows="2" class="form-input mt-1">{{ old('notes') }}</textarea></div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button class="btn btn-primary">Save Structure</button>
                <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 panel p-6">
            <h3 class="font-bold mb-3">Summary</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2"><span>CTC (Annual)</span><span class="font-bold text-primary" x-text="'₹' + Number(ctc).toLocaleString('en-IN')"></span></div>
                <div class="flex justify-between"><span>Gross (Monthly)</span><span class="font-bold" x-text="'₹' + gross.toFixed(2)"></span></div>
                <div class="flex justify-between"><span>Expected CTC (Monthly)</span><span x-text="'₹' + (ctc/12).toFixed(2)"></span></div>
                <div class="mt-4 text-[11px] text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-3">
                    Click <strong>Auto-calculate</strong> to rebuild components from CTC using a standard India break-up
                    (Basic 50% of gross, HRA 40% of Basic, Conveyance ₹1,600, Medical ₹1,250, Special = balance).
                </div>
            </div>
        </div>
    </form>
</x-layout.admin>
