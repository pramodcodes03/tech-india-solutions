<x-layout.admin title="New Asset Model">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Models', 'url' => route('admin.assets.models.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Asset Model</h1>
    <form method="POST" action="{{ route('admin.assets.models.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.assets.models._form', ['categories' => $categories])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.assets.models.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
