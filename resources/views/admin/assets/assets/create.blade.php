<x-layout.admin title="New Asset">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register', 'url' => route('admin.assets.assets.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Asset</h1>
    <form method="POST" action="{{ route('admin.assets.assets.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.assets.assets._form', compact('categories', 'models', 'locations', 'employees', 'vendors', 'purchaseOrders'))
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create Asset</button>
            <a href="{{ route('admin.assets.assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
