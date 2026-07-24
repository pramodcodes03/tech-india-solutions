<x-layout.employee title="Request Attendance Correction">
    <h1 class="text-2xl font-extrabold mb-4">Request Attendance Correction</h1>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

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
