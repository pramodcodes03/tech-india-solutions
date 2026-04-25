<x-layout.admin title="Edit Asset">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register', 'url' => route('admin.assets.assets.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-4">Edit: {{ $asset->name }} <span class="text-sm text-gray-400 font-mono">({{ $asset->asset_code }})</span></h1>
    <form method="POST" action="{{ route('admin.assets.assets.update', $asset) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.assets.assets._form', ['asset' => $asset] + compact('categories', 'models', 'locations', 'employees', 'vendors', 'purchaseOrders'))
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.assets.assets.show', $asset) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
