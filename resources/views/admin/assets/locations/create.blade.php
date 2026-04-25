<x-layout.admin title="New Location">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Locations', 'url' => route('admin.assets.locations.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Asset Location</h1>
    <form method="POST" action="{{ route('admin.assets.locations.store') }}">
        @csrf
        @include('admin.assets.locations._form', ['managers' => $managers])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.assets.locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
