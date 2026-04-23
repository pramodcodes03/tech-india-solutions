<x-layout.admin title="Add Employee">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees', 'url' => route('admin.hr.employees.index')], ['label' => 'New']]" />

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Add Employee</h1>
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>

    <form method="POST" action="{{ route('admin.hr.employees.store') }}">
        @csrf
        @include('admin.hr.employees._form', ['departments' => $departments, 'designations' => $designations, 'shifts' => $shifts, 'managers' => $managers])
        <div class="flex gap-3 mt-4">
            <button type="submit" class="btn btn-primary">Create Employee</button>
            <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
