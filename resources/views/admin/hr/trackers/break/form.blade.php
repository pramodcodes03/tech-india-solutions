@php $editing = $entry->exists; @endphp

<x-layout.admin :title="($editing ? 'Edit' : 'Add').' Break Entry'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Break Sheet', 'url' => route('admin.hr.trackers.break.index')],
        ['label' => $editing ? 'Edit Entry' : 'Add Entry'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-5">{{ $editing ? 'Edit Break Entry' : 'Add Break Entry' }}</h1>

    {{-- `employees` carries the code for each option so choosing a name fills
         the Employee ID box without a round trip — the "dropdown that
         auto-fetches the Employee ID" from the spec. --}}
    <form method="POST"
          action="{{ $editing ? route('admin.hr.trackers.break.update', $entry) : route('admin.hr.trackers.break.store') }}"
          x-data="breakForm({
              employees: {{ Illuminate\Support\Js::from($employees->map(fn($e) => ['id' => $e->id, 'code' => $e->employee_code, 'name' => $e->full_name])) }},
              employeeId: '{{ old('employee_id', $entry->employee_id) }}',
              out: '{{ old('out_time', substr((string) $entry->out_time, 0, 5)) }}',
              in: '{{ old('in_time', substr((string) $entry->in_time, 0, 5)) }}',
          })"
          class="panel p-6 max-w-4xl">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" x-model="employeeId" required class="form-select">
                    <option value="">Select employee…</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Employee ID</label>
                <input type="text" readonly x-model="employeeCode" placeholder="Fills automatically"
                       class="form-input bg-gray-50 dark:bg-[#1b2e4b] font-mono text-gray-500" />
                <p class="text-[11px] text-gray-400 mt-1">Fetched from the selected employee — not editable.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Break Date <span class="text-danger">*</span></label>
                <input type="date" name="break_date" required max="{{ now()->toDateString() }}"
                       value="{{ old('break_date', optional($entry->break_date)->toDateString() ?? now()->toDateString()) }}"
                       class="form-input" />
                @error('break_date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Break Type</label>
                <select name="break_type_id" class="form-select">
                    <option value="">Not specified</option>
                    @foreach($breakTypes as $t)
                        <option value="{{ $t->id }}" @selected(old('break_type_id', $entry->break_type_id) == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
                @can('tracker_settings.manage')
                    <p class="text-[11px] text-gray-400 mt-1">
                        Need another type? Add it in
                        <a href="{{ route('admin.hr.trackers.options.index') }}" class="text-primary font-semibold">Tracker Settings</a>.
                    </p>
                @endcan
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Out Time <span class="text-danger">*</span></label>
                <input type="time" name="out_time" x-model="out" required class="form-input" />
                @error('out_time')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">In Time</label>
                <input type="time" name="in_time" x-model="in" class="form-input" />
                <p class="text-[11px] text-gray-400 mt-1">Leave blank if the employee is still on break.</p>
                @error('in_time')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <div class="rounded-xl border border-dashed border-primary/40 bg-primary/5 px-4 py-3 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-primary">Total Timing</div>
                        <div class="text-xs text-gray-500">Calculated from Out and In time — a break crossing midnight is handled.</div>
                    </div>
                    <div class="text-2xl font-extrabold text-primary" x-text="durationLabel">—</div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Remarks</label>
                <textarea name="remarks" rows="2" maxlength="500" class="form-input"
                          placeholder="Optional note">{{ old('remarks', $entry->remarks) }}</textarea>
                @error('remarks')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ route('admin.hr.trackers.break.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Record Break' }}</button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('breakForm', (config) => ({
                employees: config.employees,
                employeeId: config.employeeId,
                out: config.out,
                in: config.in,

                get employeeCode() {
                    const match = this.employees.find(e => String(e.id) === String(this.employeeId));
                    return match ? match.code : '';
                },

                // Mirrors BreakSheet::minutesBetween() so the preview on screen
                // matches what the server will store, midnight rollover included.
                get durationLabel() {
                    if (!this.out || !this.in) return '—';
                    const [oh, om] = this.out.split(':').map(Number);
                    const [ih, im] = this.in.split(':').map(Number);
                    if ([oh, om, ih, im].some(isNaN)) return '—';
                    let minutes = (ih * 60 + im) - (oh * 60 + om);
                    if (minutes < 0) minutes += 24 * 60;
                    const h = Math.floor(minutes / 60), m = minutes % 60;
                    return h > 0 ? `${h}h ${m}m` : `${m}m`;
                },
            }));
        });
    </script>
    @endpush
</x-layout.admin>
