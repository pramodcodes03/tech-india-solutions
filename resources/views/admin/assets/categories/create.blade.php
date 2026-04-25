<x-layout.admin title="New Asset Category">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Categories', 'url' => route('admin.assets.categories.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Asset Category</h1>
    <form method="POST" action="{{ route('admin.assets.categories.store') }}">
        @csrf
        @include('admin.assets.categories._form')
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.assets.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
