<x-layout.admin title="Give Increment">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Employees', 'url' => route('admin.hr.employees.index')],
        ['label' => $employee->full_name, 'url' => route('admin.hr.employees.show', $employee)],
        ['label' => 'Give Increment'],
    ]" />

    <div class="max-w-3xl">
        <div class="mb-5">
            <h1 class="text-2xl font-extrabold">💰 Give Increment</h1>
            <p class="text-sm text-gray-500 mt-1">Quickly record a performance review & salary hike for <strong>{{ $employee->full_name }}</strong>.</p>
        </div>

        {{-- Employee context card --}}
        <div class="panel p-5 mb-4 bg-gradient-to-br from-primary/5 to-info/5 border border-primary/20">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-xl font-extrabold">
                    {{ strtoupper(substr($employee->first_name, 0, 1).substr($employee->last_name ?? '', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-lg">{{ $employee->full_name }}</div>
                    <div class="text-xs text-gray-500">{{ $employee->employee_code }} · {{ $employee->designation?->name }} · {{ $employee->department?->name }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500 uppercase font-semibold">Current CTC</div>
                    <div class="text-xl font-extrabold text-primary">
                        @if($currentCtc)₹{{ number_format($currentCtc, 0) }}@else<span class="text-gray-400 text-sm">Not set</span>@endif
                    </div>
                </div>
            </div>
            @if(! $currentCtc)
                <div class="mt-3 p-2 rounded bg-warning/10 text-warning text-xs">
                    ⚠ Employee has no salary structure. <a href="{{ route('admin.hr.salary.form', $employee) }}" class="underline">Set current CTC first</a> for hike % calculations.
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.hr.employees.increments.store', $employee) }}"
              x-data="{
                  currentCtc: {{ $currentCtc ?? 0 }},
                  hike: '',
                  newCtc: '',
                  onHikeChange() {
                      if (this.currentCtc > 0 && this.hike !== '') {
                          this.newCtc = Math.round(this.currentCtc * (1 + parseFloat(this.hike) / 100));
                      }
                  },
                  onCtcChange() {
                      if (this.currentCtc > 0 && this.newCtc !== '') {
                          this.hike = (((parseFloat(this.newCtc) - this.currentCtc) / this.currentCtc) * 100).toFixed(2);
                      }
                  },
              }"
              class="panel p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Review Date *</label>
                    <input type="date" name="review_date" value="{{ old('review_date', now()->toDateString()) }}" required class="form-input mt-1" />
                    <p class="text-xs text-gray-500 mt-1">When you're giving this review.</p>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Performance Rating (0–100) *</label>
                    <input type="number" step="0.5" min="0" max="100" name="performance_score" value="{{ old('performance_score', 80) }}" required class="form-input mt-1" />
                    <p class="text-xs text-gray-500 mt-1">60 = meets, 80 = exceeds, 95+ = outstanding.</p>
                </div>
            </div>

            <div class="p-4 rounded-lg border-2 border-dashed border-success/30 bg-success/5">
                <div class="text-xs font-bold text-success uppercase mb-3">💸 Increment</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Hike %</label>
                        <div class="relative mt-1">
                            <input type="number" step="0.1" min="0" max="500" name="hike_percent" x-model="hike" @input="onHikeChange()" class="form-input pr-8" placeholder="e.g. 10" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">New Annual CTC (₹)</label>
                        <input type="number" step="0.01" min="0" name="new_ctc_annual" x-model="newCtc" @input="onCtcChange()" class="form-input mt-1" placeholder="e.g. 660000" />
                        <p class="text-xs text-gray-500 mt-1" x-show="currentCtc > 0">Fill one — the other auto-calculates.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Effective From</label>
                        <input type="date" name="effective_from" value="{{ old('effective_from', now()->addMonth()->startOfMonth()->toDateString()) }}" class="form-input mt-1 max-w-xs" />
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Feedback (optional)</div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Strengths</label>
                        <textarea name="strengths" rows="2" class="form-input mt-1" placeholder="What the employee did well...">{{ old('strengths') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Areas to Improve</label>
                        <textarea name="improvement_areas" rows="2" class="form-input mt-1" placeholder="What they can work on...">{{ old('improvement_areas') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Comments for Employee</label>
                        <textarea name="manager_comments" rows="2" class="form-input mt-1" placeholder="Final message for the appraisal letter...">{{ old('manager_comments') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button class="btn btn-primary">💾 Save Increment</button>
                <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>

        <p class="text-xs text-gray-500 mt-3">💡 You can view the appraisal letter (PDF) from the employee's page after saving.</p>
    </div>
</x-layout.admin>
