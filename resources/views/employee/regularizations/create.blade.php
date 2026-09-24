<x-layout.employee title="Request Attendance Correction">
    <h1 class="text-2xl font-extrabold mb-4">Request Attendance Correction</h1>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    {{-- Your shift, up front. Requests asking for times outside the assigned
         shift are the main reason corrections get rejected, so state the
         window here instead of letting people guess. --}}
    <div class="max-w-2xl mb-4 p-4 rounded-xl border {{ $shift ? 'border-primary/20 bg-primary/5' : 'border-warning/30 bg-warning/5' }}">
        <div class="text-[11px] font-semibold text-gray-500 uppercase mb-1.5">Your Shift</div>
        @if($shift)
            <div class="font-bold text-lg">{{ $shift->name }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}
                &ndash; {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                @if($shift->grace_minutes > 0)
                    <span class="text-gray-500">&middot; {{ $shift->grace_minutes }} min grace</span>
                @endif
            </div>
            <div class="text-xs text-gray-500 mt-2">
                Enter the times you actually worked <strong>within this shift</strong>. Requests outside it are usually rejected.
            </div>
        @else
            <div class="font-bold text-warning">No shift assigned</div>
            <div class="text-xs text-gray-500 mt-1">
                Your attendance is counted on total hours worked. Please ask HR to assign your shift.
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('employee.regularizations.store') }}" class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow max-w-2xl space-y-5">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Date *</label>
                <input type="date" name="date" value="{{ old('date', $date) }}" max="{{ date('Y-m-d') }}" required class="form-input mt-1">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Request Type *</label>
                <select name="request_type" class="form-select mt-1">
                    @foreach(\App\Models\AttendanceRegularization::TYPES as $v => $l)
                        <option value="{{ $v }}" @selected(old('request_type')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Expected Check-in</label>
                <input type="time" name="expected_in" value="{{ old('expected_in') }}" class="form-input mt-1">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Expected Check-out</label>
                <input type="time" name="expected_out" value="{{ old('expected_out') }}" class="form-input mt-1">
            </div>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase">Reason *</label>
            <textarea name="reason" rows="3" required class="form-textarea mt-1" placeholder="Explain why the punch was missed or incorrect…">{{ old('reason') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Submit Request</button>
            <a href="{{ route('employee.regularizations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.employee>
