<x-layout.admin title="New Department">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Departments', 'url' => route('admin.hr.departments.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Department</h1>
    <form method="POST" action="{{ route('admin.hr.departments.store') }}">
        @csrf
        @include('admin.hr.departments._form', ['employees' => $employees])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.hr.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
