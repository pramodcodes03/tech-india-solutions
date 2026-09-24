@php
    use App\Models\Kpi;
    $editing = $kpi->exists;
@endphp

<x-layout.admin :title="($editing ? 'Edit' : 'New').' KPI'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'KRA Master', 'url' => route('admin.hr.performance.kras.index')],
        ['label' => $editing ? $kpi->code : 'New KPI'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-1">{{ $editing ? 'Edit KPI' : 'New KPI' }}</h1>
    <p class="text-sm text-gray-500 mb-5">One measurable indicator under a KRA. The score comes from target vs achieved.</p>

    <form method="POST"
          action="{{ $editing ? route('admin.hr.performance.kpis.update', $kpi) : route('admin.hr.performance.kpis.store') }}"
          class="panel p-6 max-w-4xl"
          x-data="{ unit: '{{ old('measurement_unit', $kpi->measurement_unit ?? 'number') }}', formula: '{{ old('score_formula', $kpi->score_formula ?? 'higher_better') }}', target: {{ (float) old('target_value', $kpi->target_value ?? 0) }} }">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Parent KRA <span class="text-danger">*</span></label>
                <select name="kra_id" required class="form-select">
                    <option value="">Select a KRA…</option>
                    @foreach($kras as $k)
                        <option value="{{ $k->id }}" @selected(old('kra_id', $kpi->kra_id) == $k->id)>{{ $k->code }} — {{ $k->name }}</option>
                    @endforeach
                </select>
                @error('kra_id')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" required maxlength="30" value="{{ old('code', $kpi->code) }}" class="form-input font-mono" />
                @error('code')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">KPI Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required maxlength="150" value="{{ old('name', $kpi->name) }}" class="form-input" placeholder="Monthly Revenue" />
                @error('name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Description</label>
                <textarea name="description" rows="2" maxlength="2000" class="form-input">{{ old('description', $kpi->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Measurement Unit <span class="text-danger">*</span></label>
                <select name="measurement_unit" x-model="unit" required class="form-select">
                    @foreach(Kpi::UNITS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Target Value <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="target_value" x-model.number="target" required class="form-input" />
                @error('target_value')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Weightage (%) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="weightage" required value="{{ old('weightage', $kpi->weightage ?? 0) }}" class="form-input" />
                <p class="text-[11px] text-gray-400 mt-1">Share within its KRA.</p>
                @error('weightage')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Score Formula <span class="text-danger">*</span></label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    @foreach(Kpi::FORMULAS as $key => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="score_formula" value="{{ $key }}" x-model="formula" class="sr-only" />
                            <div class="p-3 rounded-lg border text-sm transition-colors"
                                 :class="formula === '{{ $key }}' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-[#253b5c]'">
                                <div class="font-semibold">{{ \Illuminate\Support\Str::before($label, ' (') }}</div>
                                <div class="text-[11px] text-gray-500 mt-0.5">{{ trim(\Illuminate\Support\Str::after($label, '('), ')') }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('score_formula')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- A worked example, so whoever sets the formula can see what it does. --}}
            <div class="md:col-span-3">
                <div class="rounded-xl bg-gray-50 dark:bg-[#1b2e4b] p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">How this scores</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300" x-show="formula === 'higher_better'">
                        Target <b x-text="target || 0"></b>, achieved <b x-text="Math.round((target || 0) * 0.8)"></b> → <b class="text-primary">80.00</b>.
                        Over-achievement is capped at 100 so one runaway KPI cannot inflate the whole score.
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300" x-show="formula === 'lower_better'" x-cloak>
                        Target <b x-text="target || 0"></b>, achieved <b x-text="Math.round((target || 0) * 1.25)"></b> → <b class="text-primary">80.00</b>.
                        Coming in under target scores full marks. Use for defects, downtime, escalations.
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300" x-show="formula === 'exact_match'" x-cloak>
                        Exactly <b x-text="target || 0"></b> scores <b class="text-primary">100</b>; anything else scores <b>0</b>.
                        Use sparingly — it is all-or-nothing.
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status <span class="text-danger">*</span></label>
                <select name="status" required class="form-select">
                    <option value="active" @selected(old('status', $kpi->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $kpi->status) === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ $kpi->kra_id ? route('admin.hr.performance.kras.show', $kpi->kra_id) : route('admin.hr.performance.kpis.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create KPI' }}</button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:initialized', () => {
            const unit = document.querySelector('select[name="measurement_unit"]');
            if (unit) unit.value = '{{ old('measurement_unit', $kpi->measurement_unit ?? 'number') }}';
        });
    </script>
    @endpush
</x-layout.admin>
