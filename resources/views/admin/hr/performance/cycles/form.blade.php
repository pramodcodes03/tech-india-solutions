@php
    use App\Models\PerformanceCycle;
    $editing = $cycle->exists;
@endphp

<x-layout.admin :title="($editing ? 'Edit' : 'New').' Performance Cycle'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Cycles', 'url' => route('admin.hr.performance.cycles.index')],
        ['label' => $editing ? 'Edit' : 'New Cycle'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-1">{{ $editing ? 'Edit Cycle' : 'New Performance Cycle' }}</h1>
    <p class="text-sm text-gray-500 mb-5">A cycle opens for assessments, then locks to freeze the scores.</p>

    <form method="POST"
          action="{{ $editing ? route('admin.hr.performance.cycles.update', $cycle) : route('admin.hr.performance.cycles.store') }}"
          class="panel p-6 max-w-4xl"
          x-data="{ frequency: '{{ old('frequency', $cycle->frequency ?? 'quarterly') }}' }">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Cycle Name <span class="text-danger">*</span></label>
                <input type="text" name="name" required maxlength="120" value="{{ old('name', $cycle->name) }}" class="form-input" placeholder="Q2 2026" />
                @error('name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Frequency <span class="text-danger">*</span></label>
                <select name="frequency" x-model="frequency" required class="form-select">
                    @foreach(PerformanceCycle::FREQUENCIES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-400 mt-1">Used when rolling the cycle forward to the next period.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Period Start <span class="text-danger">*</span></label>
                <input type="date" name="period_start" required value="{{ old('period_start', optional($cycle->period_start)->toDateString() ?? $cycle->period_start) }}" class="form-input" />
                @error('period_start')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Period End <span class="text-danger">*</span></label>
                <input type="date" name="period_end" required value="{{ old('period_end', optional($cycle->period_end)->toDateString() ?? $cycle->period_end) }}" class="form-input" />
                @error('period_end')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <div class="rounded-xl border border-gray-200 dark:border-[#253b5c] p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">Review deadlines</div>
                    <p class="text-[12px] text-gray-400 mb-4">Optional, but they drive the reminders and the “upcoming reviews” list. Each must fall on or after the one before it.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1.5">Self-assessment due</label>
                            <input type="date" name="self_review_due" value="{{ old('self_review_due', optional($cycle->self_review_due)->toDateString()) }}" class="form-input" />
                            @error('self_review_due')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1.5">Manager review due</label>
                            <input type="date" name="manager_review_due" value="{{ old('manager_review_due', optional($cycle->manager_review_due)->toDateString()) }}" class="form-input" />
                            @error('manager_review_due')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1.5">HR review due</label>
                            <input type="date" name="hr_review_due" value="{{ old('hr_review_due', optional($cycle->hr_review_due)->toDateString()) }}" class="form-input" />
                            @error('hr_review_due')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Notes</label>
                <textarea name="notes" rows="2" maxlength="500" class="form-input" placeholder="Anything the reviewers should know about this cycle">{{ old('notes', $cycle->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ route('admin.hr.performance.cycles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Cycle' }}</button>
        </div>
    </form>

    @push('scripts')
    <script>
        // Select the frequency the server rendered — a plain `selected` attribute
        // would fight Alpine's x-model on first paint.
        document.addEventListener('alpine:initialized', () => {
            const select = document.querySelector('select[name="frequency"]');
            if (select) select.value = '{{ old('frequency', $cycle->frequency ?? 'quarterly') }}';
        });
    </script>
    @endpush
</x-layout.admin>
