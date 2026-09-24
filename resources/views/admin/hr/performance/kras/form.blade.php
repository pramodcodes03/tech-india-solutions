@php
    use App\Models\PerformanceCycle;
    $editing = $kra->exists;
@endphp

<x-layout.admin :title="($editing ? 'Edit' : 'New').' KRA'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'KRA Master', 'url' => route('admin.hr.performance.kras.index')],
        ['label' => $editing ? $kra->code : 'New KRA'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-1">{{ $editing ? 'Edit KRA' : 'New KRA' }}</h1>
    <p class="text-sm text-gray-500 mb-5">A Key Result Area is what someone is measured on. KPIs under it say how.</p>

    <form method="POST"
          action="{{ $editing ? route('admin.hr.performance.kras.update', $kra) : route('admin.hr.performance.kras.store') }}"
          class="panel p-6 max-w-4xl">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" required maxlength="30" value="{{ old('code', $kra->code) }}" class="form-input font-mono" />
                @error('code')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">KRA Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required maxlength="150" value="{{ old('name', $kra->name) }}" class="form-input" placeholder="Sales Growth" />
                @error('name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Description</label>
                <textarea name="description" rows="2" maxlength="2000" class="form-input" placeholder="What good looks like for this area">{{ old('description', $kra->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(old('department_id', $kra->department_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Leave blank to make it available to everyone.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Designation</label>
                <select name="designation_id" class="form-select">
                    <option value="">All designations</option>
                    @foreach($designations as $d)
                        <option value="{{ $d->id }}" @selected(old('designation_id', $kra->designation_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Default Weightage (%) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="weightage" required value="{{ old('weightage', $kra->weightage ?? 0) }}" class="form-input" />
                <p class="text-[11px] text-gray-400 mt-1">Share this KRA takes when assigned. Adjustable per employee later.</p>
                @error('weightage')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Review Frequency <span class="text-danger">*</span></label>
                <select name="review_frequency" required class="form-select">
                    @foreach(PerformanceCycle::FREQUENCIES as $key => $label)
                        <option value="{{ $key }}" @selected(old('review_frequency', $kra->review_frequency ?? 'quarterly') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Default Reviewer</label>
                <select name="manager_id" class="form-select">
                    <option value="">Employee's reporting manager</option>
                    @foreach($managers as $m)
                        <option value="{{ $m->id }}" @selected(old('manager_id', $kra->manager_id) == $m->id)>{{ $m->full_name }} ({{ $m->employee_code }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Who reviews this KRA. Defaults to each employee's own manager.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status <span class="text-danger">*</span></label>
                <select name="status" required class="form-select">
                    <option value="active" @selected(old('status', $kra->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $kra->status) === 'inactive')>Inactive</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Inactive keeps it out of new assignments; existing reviews are untouched.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Start Date</label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($kra->start_date)->toDateString()) }}" class="form-input" />
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">End Date</label>
                <input type="date" name="end_date" value="{{ old('end_date', optional($kra->end_date)->toDateString()) }}" class="form-input" />
                @error('end_date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ route('admin.hr.performance.kras.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create KRA' }}</button>
        </div>
    </form>
</x-layout.admin>
