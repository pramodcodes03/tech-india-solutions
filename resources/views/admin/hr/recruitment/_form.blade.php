@props([
    'candidate' => null,
    'stages' => collect(),
    'batches' => collect(),
    'departments' => collect(),
    'designations' => collect(),
    'employees' => collect(),
    'sources' => [],
])

<div class="panel p-6 space-y-6">
    {{-- Personal --}}
    <div>
        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Candidate Details</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $candidate?->first_name) }}" required class="form-input mt-1" />
                @error('first_name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $candidate?->last_name) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Email</label>
                <input type="email" name="email" value="{{ old('email', $candidate?->email) }}" class="form-input mt-1" />
                @error('email')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $candidate?->phone) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Current Location</label>
                <input type="text" name="current_location" value="{{ old('current_location', $candidate?->current_location) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Resume (PDF/DOC)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" class="form-input mt-1" />
                @if($candidate?->resume_path)
                    <a href="{{ Storage::url($candidate->resume_path) }}" target="_blank" class="text-primary text-xs">View current resume</a>
                @endif
                @error('resume')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Source --}}
    <div>
        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Source & Referral</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Source *</label>
                <select name="source" class="form-select mt-1" onchange="toggleSourceFields(this.value)" id="sourceSelect">
                    @foreach($sources as $val => $label)
                        <option value="{{ $val }}" @selected(old('source', $candidate?->source) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div data-source-field="referral">
                <label class="text-xs font-semibold text-gray-500 uppercase">Referred By (Employee)</label>
                <select name="referred_by_employee_id" class="form-select mt-1">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(old('referred_by_employee_id', $candidate?->referred_by_employee_id) == $emp->id)>{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div data-source-field="campus">
                <label class="text-xs font-semibold text-gray-500 uppercase">Campus Batch</label>
                <select name="batch_id" class="form-select mt-1">
                    <option value="">— Select —</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" @selected(old('batch_id', $candidate?->batch_id) == $batch->id)>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Role & pipeline --}}
    <div>
        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Role & Pipeline</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Department</label>
                <select name="department_id" class="form-select mt-1">
                    <option value="">— Select —</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(old('department_id', $candidate?->department_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Designation (Role)</label>
                <select name="designation_id" class="form-select mt-1">
                    <option value="">— Select —</option>
                    @foreach($designations as $d)
                        <option value="{{ $d->id }}" @selected(old('designation_id', $candidate?->designation_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Stage</label>
                <select name="stage_id" class="form-select mt-1">
                    @foreach($stages as $s)
                        <option value="{{ $s->id }}" @selected(old('stage_id', $candidate?->stage_id ?? optional($stages->firstWhere('is_default', true))->id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Applied On</label>
                <input type="date" name="applied_at" value="{{ old('applied_at', optional($candidate?->applied_at)->format('Y-m-d') ?? date('Y-m-d')) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Total Experience (yrs)</label>
                <input type="number" step="0.1" name="total_experience" value="{{ old('total_experience', $candidate?->total_experience) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Notice Period (days)</label>
                <input type="number" name="notice_period_days" value="{{ old('notice_period_days', $candidate?->notice_period_days) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Current CTC</label>
                <input type="number" step="0.01" name="current_ctc" value="{{ old('current_ctc', $candidate?->current_ctc) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Expected CTC</label>
                <input type="number" step="0.01" name="expected_ctc" value="{{ old('expected_ctc', $candidate?->expected_ctc) }}" class="form-input mt-1" />
            </div>
        </div>
    </div>

    <div>
        <label class="text-xs font-semibold text-gray-500 uppercase">Notes</label>
        <textarea name="notes" rows="3" class="form-textarea mt-1">{{ old('notes', $candidate?->notes) }}</textarea>
    </div>
</div>

<script>
    function toggleSourceFields(source) {
        document.querySelectorAll('[data-source-field]').forEach(el => {
            const type = el.getAttribute('data-source-field');
            el.style.opacity = (source === type) ? '1' : '0.5';
        });
    }
    document.addEventListener('DOMContentLoaded', () => toggleSourceFields(document.getElementById('sourceSelect').value));
</script>
