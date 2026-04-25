<x-layout.admin title="Edit Asset Category">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Categories', 'url' => route('admin.assets.categories.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit: {{ $category->name }}</h1>
    <form method="POST" action="{{ route('admin.assets.categories.update', $category) }}">
        @csrf @method('PUT')
        @include('admin.assets.categories._form', ['category' => $category])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.assets.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
