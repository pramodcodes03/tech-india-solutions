<x-layout.admin title="Edit Attendance">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Attendance', 'url' => route('admin.hr.attendance.index', ['date' => $attendance->date->toDateString()])],
        ['label' => 'Edit'],
    ]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Edit Attendance</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $attendance->employee->full_name }}
                <span class="text-xs">({{ $attendance->employee->employee_code }})</span>
                ·
                {{ $attendance->date->format('D, d M Y') }}
            </p>
        </div>

        {{-- Current state — useful context when correcting a biometric row --}}
        <div class="text-right">
            <div class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Current Status</div>
            <div class="mt-1">
                <span @class([
                    'px-2 py-0.5 rounded text-xs font-semibold',
                    'bg-success/10 text-success' => $attendance->status === 'present',
                    'bg-warning/10 text-warning' => $attendance->status === 'half_day',
                    'bg-danger/10 text-danger'   => $attendance->status === 'absent',
                    'bg-info/10 text-info'       => $attendance->status === 'on_leave',
                    'bg-gray-200 text-gray-600'  => in_array($attendance->status, ['holiday', 'weekend']),
                ])>{{ ucfirst(str_replace('_',' ', $attendance->status)) }}</span>
                <span class="text-xs text-gray-500 ml-2">{{ number_format($attendance->hours_worked, 2) }} hrs · {{ str_replace('_',' ', $attendance->source) }}</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hr.attendance.update', $attendance) }}" class="panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
        @csrf
        @method('PATCH')

        @if($errors->any())
            <div class="md:col-span-2 p-3 rounded-lg bg-danger/10 border border-danger/30 text-danger text-sm">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase flex items-center gap-1.5">
                Check-in
                @if($attendance->check_in_locked)
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-warning/15 text-warning normal-case"
                          title="Manually corrected. Biometric sync will not overwrite this field.">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.657 1.343-3 3-3s3 1.343 3 3v4M6 21h12a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                        </svg>
                        Locked
                    </span>
                @endif
            </label>
            <input type="time" name="check_in"
                   value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}"
                   class="form-input mt-1" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase flex items-center gap-1.5">
                Check-out
                @if($attendance->check_out_locked)
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-warning/15 text-warning normal-case"
                          title="Manually corrected. Biometric sync will not overwrite this field.">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.657 1.343-3 3-3s3 1.343 3 3v4M6 21h12a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                        </svg>
                        Locked
                    </span>
                @endif
            </label>
            <input type="time" name="check_out"
                   value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}"
                   class="form-input mt-1" />
        </div>

        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-gray-500 uppercase">Remarks</label>
            <input type="text" name="remarks" maxlength="500"
                   value="{{ old('remarks', $attendance->remarks) }}"
                   placeholder="e.g. Forgot to punch out — verified with line manager"
                   class="form-input mt-1" />
        </div>

        <div class="md:col-span-2 p-3 rounded-lg bg-info/5 border border-info/30 text-xs text-gray-600 dark:text-gray-400">
            <div><strong class="text-info">Status will be recalculated automatically</strong> from the new check-in / check-out times.
            Worked hours ≥ 8 → <em>Present</em>. Any completed day under 8 hours → <em>Half day</em>.
            No check-in → <em>Absent</em>. Saving the same times back will produce the same status.</div>
            <div class="mt-2 pt-2 border-t border-info/20"><strong class="text-warning">Lock behaviour:</strong> any field whose value you actually change here gets locked for this day, so the biometric API sync won't overwrite your correction. Untouched fields stay open and continue to sync. Locks reset automatically the next day.</div>
        </div>

        <div class="md:col-span-2 flex gap-3">
            <button class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.hr.attendance.index', ['date' => $attendance->date->toDateString()]) }}"
               class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
