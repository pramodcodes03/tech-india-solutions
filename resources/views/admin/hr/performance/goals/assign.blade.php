<x-layout.admin title="Assign Goals">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Goals', 'url' => route('admin.hr.performance.goals.index')],
        ['label' => 'Assign'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-1">Assign Goals</h1>
    <p class="text-sm text-gray-500 mb-5">Four ways in, all landing on the same place — pick whichever suits what you are doing.</p>

    <form method="POST" action="{{ route('admin.hr.performance.goals.store') }}"
          x-data="{
              mode: 'direct',
              kras: [],
              employees: [],
              department: '',
              designation: '',
              get canSubmit() {
                  if (this.mode === 'direct') return this.employees.length > 0 && this.kras.length > 0;
                  if (this.mode === 'bulk') return this.kras.length > 0 && (this.department || this.designation);
                  if (this.mode === 'copy_forward') return !!this.$refs.fromCycle?.value;
                  return true;
              },
          }"
          class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @csrf
        <input type="hidden" name="performance_cycle_id" value="{{ $cycle->id }}" />
        <input type="hidden" name="mode" :value="mode" />

        <div class="xl:col-span-2 space-y-4">
            {{-- Mode picker --}}
            <div class="panel p-5">
                <h3 class="font-bold mb-1">How do you want to assign?</h3>
                <p class="text-xs text-gray-500 mb-4">Assigning the same goal twice is safe — it updates rather than duplicating.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @php
                        $modes = [
                            'direct' => ['Direct', 'Pick the exact people and the exact KRAs.'],
                            'bulk' => ['Bulk', 'A whole department or designation in one action.'],
                            'cascade' => ['Cascade', 'Admin → Department → Designation → Employee, matching each person\'s own KRAs.'],
                            'copy_forward' => ['Copy Forward', 'Bring everything across from a previous cycle, weightages included.'],
                        ];
                    @endphp
                    @foreach($modes as $key => [$title, $desc])
                        <label class="cursor-pointer">
                            <input type="radio" x-model="mode" value="{{ $key }}" class="sr-only" />
                            <div class="p-4 rounded-xl border transition-colors h-full"
                                 :class="mode === '{{ $key }}' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-[#253b5c]'">
                                <div class="font-bold text-sm">{{ $title }}</div>
                                <div class="text-[11px] text-gray-500 mt-1">{{ $desc }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Copy forward --}}
            <div class="panel p-5" x-show="mode === 'copy_forward'" x-cloak>
                <h3 class="font-bold mb-1">Copy from</h3>
                <p class="text-xs text-gray-500 mb-3">Every goal an employee held in that cycle is recreated here, with the same weightages and no scores.</p>
                <select name="from_cycle_id" x-ref="fromCycle" class="form-select max-w-md">
                    <option value="">Select the cycle to copy from…</option>
                    @foreach($cycles as $c)
                        @continue($c->id === $cycle->id)
                        <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->period_label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- KRA picker --}}
            <div class="panel p-5" x-show="mode !== 'copy_forward'" x-cloak>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold">Which KRAs?</h3>
                    <span class="text-xs text-gray-500"><span x-text="kras.length"></span> selected</span>
                </div>
                <p class="text-xs text-gray-500 mb-3">
                    <span x-show="mode === 'cascade'">Leave everything unticked and cascade gives each person the KRAs that match their own department and designation.</span>
                    <span x-show="mode !== 'cascade'">Pick the KRAs to assign.</span>
                </p>

                <div class="max-h-[340px] overflow-y-auto rounded-lg border border-gray-100 dark:border-[#1b2e4b] divide-y divide-gray-50 dark:divide-[#1b2e4b]">
                    @forelse($kras as $kra)
                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#1b2e4b]">
                            <input type="checkbox" name="kra_ids[]" value="{{ $kra->id }}" x-model.number="kras" class="form-checkbox" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm">
                                    <span class="font-mono text-[11px] text-gray-400">{{ $kra->code }}</span> {{ $kra->name }}
                                </div>
                                <div class="text-[11px] text-gray-400">{{ $kra->scope_label }}</div>
                            </div>
                            <span class="text-xs font-bold text-gray-500 shrink-0">{{ rtrim(rtrim(number_format((float) $kra->weightage, 2, '.', ''), '0'), '.') }}%</span>
                        </label>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">
                            No active KRAs yet.
                            @can('performance_kra.create')<a href="{{ route('admin.hr.performance.kras.create') }}" class="text-primary font-semibold">Create one</a>.@endcan
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Targets --}}
            <div class="panel p-5" x-show="mode === 'bulk' || mode === 'cascade'" x-cloak>
                <h3 class="font-bold mb-1">Who gets them?</h3>
                <p class="text-xs text-gray-500 mb-3">
                    <span x-show="mode === 'cascade'">Leave both blank to cascade across everyone.</span>
                    <span x-show="mode === 'bulk'">Choose at least one.</span>
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                        <select name="department_id" x-model="department" class="form-select">
                            <option value="">All departments</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Designation</label>
                        <select name="designation_id" x-model="designation" class="form-select">
                            <option value="">All designations</option>
                            @foreach($designations as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel p-5" x-show="mode === 'direct' || mode === 'copy_forward'" x-cloak>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold">Which employees?</h3>
                    <span class="text-xs text-gray-500"><span x-text="employees.length"></span> selected</span>
                </div>
                <p class="text-xs text-gray-500 mb-3">
                    <span x-show="mode === 'copy_forward'">Leave everyone unticked to copy the whole cycle across.</span>
                    <span x-show="mode === 'direct'">Pick the people to assign to.</span>
                </p>

                <input type="text" x-model="search" placeholder="Filter by name or ID…" class="form-input mb-2"
                       x-data="{ search: '' }" x-ref="empSearch" oninput="filterEmployeeList(this.value)" />

                <div class="max-h-[300px] overflow-y-auto rounded-lg border border-gray-100 dark:border-[#1b2e4b] divide-y divide-gray-50 dark:divide-[#1b2e4b]" id="employeeList">
                    @foreach($employees as $employee)
                        <label class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#1b2e4b] employee-row"
                               data-name="{{ strtolower($employee->full_name.' '.$employee->employee_code) }}">
                            <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" x-model.number="employees" class="form-checkbox" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm truncate">{{ $employee->full_name }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $employee->employee_code }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Summary rail --}}
        <div class="space-y-4">
            <div class="panel p-5 sticky top-4">
                <h3 class="font-bold mb-3">Ready to assign</h3>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cycle</span>
                        <span class="font-semibold">{{ $cycle->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-semibold">{{ $cycle->status_label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mode</span>
                        <span class="font-semibold capitalize" x-text="mode.replace('_', ' ')"></span>
                    </div>
                    <div class="flex justify-between" x-show="mode !== 'copy_forward'">
                        <span class="text-gray-500">KRAs</span>
                        <span class="font-semibold" x-text="kras.length"></span>
                    </div>
                    <div class="flex justify-between" x-show="mode === 'direct'">
                        <span class="text-gray-500">Employees</span>
                        <span class="font-semibold" x-text="employees.length"></span>
                    </div>
                </div>

                @unless($cycle->isEditable())
                    <div class="mt-4 p-3 rounded-lg bg-danger/10 text-danger text-xs font-semibold">
                        This cycle is {{ $cycle->status_label }} — goals can only be assigned to a Draft or Open cycle.
                    </div>
                @endunless

                <button type="submit" class="btn btn-primary w-full mt-4"
                        :disabled="!canSubmit || {{ $cycle->isEditable() ? 'false' : 'true' }}"
                        :class="(!canSubmit || {{ $cycle->isEditable() ? 'false' : 'true' }}) ? 'opacity-50 cursor-not-allowed' : ''">
                    Assign Goals
                </button>
                <a href="{{ route('admin.hr.performance.goals.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-secondary w-full mt-2">Cancel</a>

                <p class="text-[11px] text-gray-400 mt-4">
                    Weightages come from each KRA's default. Fine-tune them per employee on the
                    <a href="{{ route('admin.hr.performance.goals.weightages', ['cycle' => $cycle->id]) }}" class="text-primary font-semibold">weightage screen</a>
                    — every employee must total 100%.
                </p>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function filterEmployeeList(term) {
            const needle = (term || '').toLowerCase();
            document.querySelectorAll('#employeeList .employee-row').forEach(row => {
                row.style.display = row.dataset.name.includes(needle) ? '' : 'none';
            });
        }
    </script>
    @endpush
</x-layout.admin>
