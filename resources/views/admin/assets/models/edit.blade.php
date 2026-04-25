<x-layout.admin title="Edit Asset Model">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Models', 'url' => route('admin.assets.models.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit: {{ $model->name }}</h1>
    <form method="POST" action="{{ route('admin.assets.models.update', $model) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.assets.models._form', ['model' => $model, 'categories' => $categories])
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.assets.models.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
