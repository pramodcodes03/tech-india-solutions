<x-layout.admin title="Edit Department">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Departments', 'url' => route('admin.hr.departments.index')], ['label' => $department->name]]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit Department</h1>
    <form method="POST" action="{{ route('admin.hr.departments.update', $department) }}">
        @csrf @method('PUT')
        @include('admin.hr.departments._form', ['department' => $department, 'employees' => $employees])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.hr.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
