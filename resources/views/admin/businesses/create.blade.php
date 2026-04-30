<x-layout.admin title="Create Business">
    <x-admin.breadcrumb :items="[['label' => 'Businesses', 'url' => route('admin.businesses.index')], ['label' => 'Create']]" />

    <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Create Business</h5>

    <form method="POST" action="{{ route('admin.businesses.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel">
            @include('admin.businesses._form')
        </div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.businesses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Business + Admin</button>
        </div>
    </form>
</x-layout.admin>
