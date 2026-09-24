{{-- Manager assessment recorded by an admin standing in for the manager.
     Same fields as the manager's own screen in the employee portal. --}}
<form method="POST" action="{{ route('admin.hr.performance.reviews.manager', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}" class="mt-4 space-y-4">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Overall rating (1–5) <span class="text-danger">*</span></label>
            <select name="overall_rating" required class="form-select">
                <option value="">Select a rating…</option>
                @foreach(\App\Models\EmployeeKra::RATINGS as $value => $label)
                    <option value="{{ $value }}">{{ $value }} — {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-4">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="recommend_promotion" value="1" class="form-checkbox" /> Recommend promotion
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="recommend_training" value="1" class="form-checkbox" /> Recommend training
            </label>
        </div>
    </div>

    {{-- Achieved values, per KPI. The manager is the one who knows what landed. --}}
    @foreach($goals as $goal)
        @if($goal->kpis->isNotEmpty())
            <div class="rounded-lg border border-gray-100 dark:border-[#1b2e4b] p-3">
                <div class="text-xs font-bold mb-2">{{ $goal->kra?->name }}</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($goal->kpis as $kpi)
                        <div class="flex items-center gap-2">
                            <div class="min-w-0 flex-1 text-xs">
                                <div class="font-semibold truncate">{{ $kpi->kpi?->name }}</div>
                                <div class="text-[10px] text-gray-400">Target {{ $kpi->kpi?->formatValue((float) $kpi->target_value) }}</div>
                            </div>
                            <input type="number" step="0.01" name="kpi[{{ $kpi->id }}]" value="{{ $kpi->achieved_value }}"
                                   placeholder="Achieved" class="form-input w-28 text-right py-1 text-sm" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-start">
            <div class="text-xs font-semibold pt-2">{{ $goal->kra?->name }} rating</div>
            <select name="kra[{{ $goal->id }}][rating]" class="form-select text-sm">
                <option value="">No rating</option>
                @foreach(\App\Models\EmployeeKra::RATINGS as $value => $label)
                    <option value="{{ $value }}" @selected($goal->manager_rating == $value)>{{ $value }} — {{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="kra[{{ $goal->id }}][feedback]" value="{{ $goal->manager_feedback }}"
                   maxlength="2000" placeholder="Feedback on this KRA" class="form-input text-sm" />
        </div>
    @endforeach

    <div>
        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Feedback</label>
        <textarea name="feedback" rows="3" maxlength="5000" class="form-input" placeholder="What went well, what did not"></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Suggestions</label>
            <textarea name="suggestions" rows="2" maxlength="5000" class="form-input"></textarea>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Training notes</label>
            <textarea name="training_notes" rows="2" maxlength="500" class="form-input"></textarea>
        </div>
    </div>

    <button class="btn btn-primary">Submit Manager Assessment</button>
</form>
