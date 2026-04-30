<x-layout.admin title="Edit Business">
    <x-admin.breadcrumb :items="[['label' => 'Businesses', 'url' => route('admin.businesses.index')], ['label' => $business->name]]" />

    <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Edit Business</h5>

    <form method="POST" action="{{ route('admin.businesses.update', $business) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="panel">
            @include('admin.businesses._form', ['business' => $business])
        </div>
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.businesses.show', $business) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</x-layout.admin>
