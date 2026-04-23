<x-layout.admin title="Edit Appraisal">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Appraisals / Increments', 'url' => route('admin.hr.appraisals.index')],
        ['label' => $appraisal->appraisal_code, 'url' => route('admin.hr.appraisals.show', $appraisal)],
        ['label' => 'Edit'],
    ]" />

    <div class="max-w-3xl">
        <div class="mb-5">
            <h1 class="text-2xl font-extrabold">Edit Appraisal</h1>
            <p class="text-sm text-gray-500 mt-1">For <strong>{{ $appraisal->employee->full_name }}</strong></p>
        </div>

        <form method="POST" action="{{ route('admin.hr.appraisals.update', $appraisal) }}" class="panel p-6 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Performance Rating (0–100) *</label>
                    <input type="number" step="0.5" min="0" max="100" name="performance_score"
                           value="{{ old('performance_score', $appraisal->performance_score) }}" required class="form-input mt-1 text-xl font-bold" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Hike %</label>
                    <input type="number" step="0.1" min="0" name="recommended_hike_percent"
                           value="{{ old('recommended_hike_percent', $appraisal->recommended_hike_percent) }}" class="form-input mt-1" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">New Annual CTC (₹)</label>
                    <input type="number" step="0.01" min="0" name="new_ctc_annual"
                           value="{{ old('new_ctc_annual', $appraisal->new_ctc_annual) }}" class="form-input mt-1" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Effective From</label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', $appraisal->effective_from?->format('Y-m-d')) }}" class="form-input mt-1 max-w-xs" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Strengths</label>
                    <textarea name="strengths" rows="2" class="form-input mt-1">{{ old('strengths', $appraisal->strengths) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Areas to Improve</label>
                    <textarea name="improvement_areas" rows="2" class="form-input mt-1">{{ old('improvement_areas', $appraisal->improvement_areas) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Manager Comments</label>
                    <textarea name="manager_comments" rows="3" class="form-input mt-1">{{ old('manager_comments', $appraisal->manager_comments) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <button class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.hr.appraisals.show', $appraisal) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout.admin>
