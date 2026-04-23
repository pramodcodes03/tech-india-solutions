<x-layout.admin title="New Designation">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Designations', 'url' => route('admin.hr.designations.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Designation</h1>
    <form method="POST" action="{{ route('admin.hr.designations.store') }}">
        @csrf
        @include('admin.hr.designations._form', ['departments' => $departments])
        <div class="flex gap-3 mt-4"><button class="btn btn-primary">Create</button><a href="{{ route('admin.hr.designations.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</x-layout.admin>
