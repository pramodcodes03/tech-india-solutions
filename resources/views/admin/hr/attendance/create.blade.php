<x-layout.admin title="Mark Attendance">
    <h1 class="text-2xl font-extrabold mb-4">Mark Attendance</h1>
    <form method="POST" action="{{ route('admin.hr.attendance.store') }}" class="panel p-5 grid grid-cols-2 md:grid-cols-3 gap-4 max-w-4xl">
        @csrf
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-gray-500 uppercase">Employee *</label>
            <select name="employee_id" required class="form-select mt-1">
                <option value="">Select employee</option>
                @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->employee_code }} · {{ $e->full_name }}</option>@endforeach
            </select>
        </div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Date *</label><input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="form-input mt-1" /></div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Check-in</label><input type="time" name="check_in" class="form-input mt-1" /></div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Check-out</label><input type="time" name="check_out" class="form-input mt-1" /></div>
        <div><label class="text-xs font-semibold text-gray-500 uppercase">Status *</label>
            <select name="status" required class="form-select mt-1">
                @foreach(['present','absent','half_day','late','on_leave','holiday','weekend'] as $s)
                    <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-3"><label class="text-xs font-semibold text-gray-500 uppercase">Remarks</label><input type="text" name="remarks" class="form-input mt-1" /></div>
        <div class="md:col-span-3 flex gap-3">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
