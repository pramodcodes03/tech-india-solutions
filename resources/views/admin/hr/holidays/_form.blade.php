@props(['holiday' => null, 'employees' => collect()])

<div class="panel p-6 space-y-5">

    {{-- Name + Date row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Holiday Name *</label>
            <input type="text" name="name" value="{{ old('name', $holiday?->name) }}" required
                   placeholder="e.g. Independence Day"
                   class="form-input mt-1" />
            @error('name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Date *</label>
            <input type="date" name="date" value="{{ old('date', $holiday?->date?->format('Y-m-d')) }}" required
                   class="form-input mt-1" />
            @error('date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Type --}}
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Holiday Type *</label>
        <div class="grid grid-cols-3 gap-3 mt-1">
            @foreach(['public' => ['Public Holiday', 'Applies to all employees. No work expected.', 'primary'],
                       'optional' => ['Optional Holiday', 'Employees can choose to take or work.', 'warning'],
                       'restricted' => ['Restricted Holiday', 'Only specific employees may avail.', 'info']] as $val => [$label, $desc, $color])
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="{{ $val }}" class="sr-only"
                           @if(old('type', $holiday?->type ?? 'public') === $val) checked @endif>
                    <div @class([
                        'p-3 rounded-xl border-2 transition-all text-center',
                        "border-{$color} bg-{$color}/10" => (old('type', $holiday?->type ?? 'public') === $val),
                        'border-gray-200' => (old('type', $holiday?->type ?? 'public') !== $val),
                    ]) onclick="selectType('{{ $val }}', '{{ $color }}')">
                        <div class="font-semibold text-sm" id="type_label_{{ $val }}">{{ $label }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $desc }}</div>
                    </div>
                </label>
            @endforeach
        </div>
        @error('type')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Holiday Category --}}
    <div class="border border-gray-100 rounded-xl p-4 space-y-4 bg-gray-50">
        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Holiday Category</div>

        {{-- Yearly recurring --}}
        <label class="flex items-start gap-3 cursor-pointer group" id="yearlyToggleWrap">
            <div class="mt-0.5">
                <input type="hidden" name="is_yearly" value="0">
                <input type="checkbox" name="is_yearly" id="is_yearly" value="1"
                       @if(old('is_yearly', $holiday?->is_yearly)) checked @endif
                       class="w-4 h-4 rounded text-success" onchange="handleYearlyChange(this)" />
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm">Yearly Recurring</span>
                    <span class="px-1.5 py-0.5 bg-success/10 text-success text-xs rounded font-semibold">Auto</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">This holiday repeats every year on the same date (e.g. 15 Aug Independence Day, 26 Jan Republic Day). No need to re-add each year.</p>
            </div>
        </label>

        <div class="h-px bg-gray-200"></div>

        {{-- Dynamic week-off --}}
        <label class="flex items-start gap-3 cursor-pointer" id="dynamicToggleWrap">
            <div class="mt-0.5">
                <input type="hidden" name="is_dynamic" value="0">
                <input type="checkbox" name="is_dynamic" id="is_dynamic" value="1"
                       @if(old('is_dynamic', $holiday?->is_dynamic)) checked @endif
                       class="w-4 h-4 rounded text-info" onchange="handleDynamicChange(this)" />
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm">Dynamic Week-Off</span>
                    <span class="px-1.5 py-0.5 bg-info/10 text-info text-xs rounded font-semibold">Paid</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">Employee worked on a week-off day and is compensated with a different day off. No salary deduction — counts as paid day.</p>
            </div>
        </label>

        {{-- Employee selector for dynamic holiday --}}
        <div id="employeeField" class="{{ old('is_dynamic', $holiday?->is_dynamic) ? '' : 'hidden' }} pl-7">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Apply To Employee</label>
            <select name="employee_id" class="form-select mt-1">
                <option value="">— All Employees —</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(old('employee_id', $holiday?->employee_id) == $emp->id)>
                        {{ $emp->name }} ({{ $emp->employee_code }})
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Leave blank to apply to all employees.</p>
        </div>
    </div>

    {{-- Description --}}
    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</label>
        <input type="text" name="description" value="{{ old('description', $holiday?->description) }}"
               placeholder="Optional note..."
               class="form-input mt-1" />
    </div>
</div>

@push('scripts')
<script>
function handleYearlyChange(cb) {
    // yearly and dynamic are mutually exclusive
    if (cb.checked) {
        document.getElementById('is_dynamic').checked = false;
        document.getElementById('employeeField').classList.add('hidden');
    }
}

function handleDynamicChange(cb) {
    if (cb.checked) {
        document.getElementById('is_yearly').checked = false;
        document.getElementById('employeeField').classList.remove('hidden');
    } else {
        document.getElementById('employeeField').classList.add('hidden');
    }
}

function selectType(val, color) {
    document.querySelectorAll('input[name="type"]').forEach(r => {
        const card = r.closest('label').querySelector('div');
        if (r.value === val) {
            r.checked = true;
            card.className = `p-3 rounded-xl border-2 transition-all text-center border-${color} bg-${color}/10`;
        } else {
            card.className = 'p-3 rounded-xl border-2 transition-all text-center border-gray-200';
        }
    });
}
</script>
@endpush
