@php
    use App\Models\VisitorLog;
    $editing = $entry->exists;
@endphp

<x-layout.admin :title="($editing ? 'Edit' : 'Add').' Visitor Entry'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Daily Visitor', 'url' => route('admin.hr.trackers.visitors.index')],
        ['label' => $editing ? 'Edit Entry' : 'Add Entry'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-5">{{ $editing ? 'Edit Visitor Entry' : 'Add Visitor Entry' }}</h1>

    <form method="POST"
          action="{{ $editing ? route('admin.hr.trackers.visitors.update', $entry) : route('admin.hr.trackers.visitors.store') }}"
          class="panel p-6 max-w-4xl">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Date of Visit <span class="text-danger">*</span></label>
                <input type="date" name="visit_date" required
                       value="{{ old('visit_date', optional($entry->visit_date)->toDateString() ?? now()->toDateString()) }}"
                       class="form-input" />
                @error('visit_date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Candidate / Visitor Name <span class="text-danger">*</span></label>
                <input type="text" name="visitor_name" required maxlength="150"
                       value="{{ old('visitor_name', $entry->visitor_name) }}" class="form-input" />
                @error('visitor_name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Mobile Number</label>
                <input type="text" name="mobile" maxlength="20" inputmode="tel"
                       value="{{ old('mobile', $entry->mobile) }}" class="form-input font-mono" />
                @error('mobile')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Source</label>
                <select name="source_id" class="form-select">
                    <option value="">Not specified</option>
                    @foreach($sources as $s)
                        <option value="{{ $s->id }}" @selected(old('source_id', $entry->source_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                @can('tracker_settings.manage')
                    <p class="text-[11px] text-gray-400 mt-1">
                        Add a new source in
                        <a href="{{ route('admin.hr.trackers.options.index') }}" class="text-primary font-semibold">Tracker Settings</a>.
                    </p>
                @endcan
                @error('source_id')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Arrival Time</label>
                <input type="time" name="arrival_time" value="{{ old('arrival_time', substr((string) $entry->arrival_time, 0, 5)) }}" class="form-input" />
                @error('arrival_time')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Purpose of Visit</label>
                <select name="purpose_id" class="form-select">
                    <option value="">Not specified</option>
                    @foreach($purposes as $p)
                        <option value="{{ $p->id }}" @selected(old('purpose_id', $entry->purpose_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('purpose_id')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Called By</label>
                <input type="text" name="called_by" maxlength="120" placeholder="Who invited them"
                       value="{{ old('called_by', $entry->called_by) }}" class="form-input" />
                @error('called_by')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Interview By</label>
                <input type="text" name="interview_by" maxlength="120" placeholder="Who met them"
                       value="{{ old('interview_by', $entry->interview_by) }}" class="form-input" />
                @error('interview_by')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Availability Status <span class="text-danger">*</span></label>
                <select name="availability_status" required class="form-select">
                    @foreach(VisitorLog::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(old('availability_status', $entry->availability_status ?? 'available') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('availability_status')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Outcome <span class="text-danger">*</span></label>
                <select name="outcome" required class="form-select">
                    @foreach(VisitorLog::OUTCOMES as $key => $label)
                        <option value="{{ $key }}" @selected(old('outcome', $entry->outcome ?? 'pending') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Drives the interview conversion figure on the analytics page.</p>
                @error('outcome')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Remarks</label>
                <textarea name="remarks" rows="2" maxlength="500" class="form-input"
                          placeholder="Optional note">{{ old('remarks', $entry->remarks) }}</textarea>
                @error('remarks')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ route('admin.hr.trackers.visitors.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Record Visitor' }}</button>
        </div>
    </form>
</x-layout.admin>
