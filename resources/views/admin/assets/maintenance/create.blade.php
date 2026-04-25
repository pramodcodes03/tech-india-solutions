<x-layout.admin title="New Maintenance Log">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Maintenance', 'url' => route('admin.assets.maintenance.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">Log Maintenance</h1>
    <form method="POST" action="{{ route('admin.assets.maintenance.store') }}">
        @csrf
        @include('admin.assets.maintenance._form', ['assets' => $assets, 'employees' => $employees, 'asset' => $asset ?? null])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.assets.maintenance.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
