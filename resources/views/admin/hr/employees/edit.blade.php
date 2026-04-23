<x-layout.admin title="Edit Employee">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees', 'url' => route('admin.hr.employees.index')], ['label' => $employee->employee_code]]" />

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Edit {{ $employee->full_name }} ({{ $employee->employee_code }})</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.hr.employees.reset-password', $employee) }}" onsubmit="return confirm('Reset password to employee code?')">
                @csrf
                <button class="btn btn-outline-warning">Reset Password</button>
            </form>
            <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}">
        @csrf
        @method('PUT')
        @include('admin.hr.employees._form', ['employee' => $employee, 'departments' => $departments, 'designations' => $designations, 'shifts' => $shifts, 'managers' => $managers])
        <div class="flex gap-3 mt-4">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
