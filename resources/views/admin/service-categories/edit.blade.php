<x-layout.admin title="Edit Service Category">
    <x-admin.breadcrumb :items="[['label' => 'Service'], ['label' => 'Categories', 'url' => route('admin.service-categories.index')], ['label' => $category->name]]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit: {{ $category->name }}</h1>
    <form method="POST" action="{{ route('admin.service-categories.update', $category) }}">
        @csrf @method('PUT')
        @include('admin.service-categories._form', ['category' => $category])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
