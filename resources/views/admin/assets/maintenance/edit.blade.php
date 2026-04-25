<x-layout.admin title="Edit Maintenance Log">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Maintenance', 'url' => route('admin.assets.maintenance.index')], ['label' => $log->log_code]]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit Maintenance Log {{ $log->log_code }}</h1>
    <form method="POST" action="{{ route('admin.assets.maintenance.update', $log) }}">
        @csrf @method('PUT')
        @include('admin.assets.maintenance._form', ['log' => $log, 'assets' => $assets, 'employees' => $employees])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.assets.maintenance.show', $log) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
