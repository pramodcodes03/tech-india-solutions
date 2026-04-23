<x-layout.admin title="Edit Designation">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Designations', 'url' => route('admin.hr.designations.index')], ['label' => $designation->name]]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit Designation</h1>
    <form method="POST" action="{{ route('admin.hr.designations.update', $designation) }}">
        @csrf @method('PUT')
        @include('admin.hr.designations._form', ['designation' => $designation, 'departments' => $departments])
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Save</button><a href="{{ route('admin.hr.designations.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
