<x-layout.admin title="Edit Location">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Locations', 'url' => route('admin.assets.locations.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit: {{ $location->name }}</h1>
    <form method="POST" action="{{ route('admin.assets.locations.update', $location) }}">
        @csrf @method('PUT')
        @include('admin.assets.locations._form', ['location' => $location, 'managers' => $managers])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.assets.locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
