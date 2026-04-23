<x-layout.admin title="New Service Category">
    <x-admin.breadcrumb :items="[['label' => 'Service'], ['label' => 'Categories', 'url' => route('admin.service-categories.index')], ['label' => 'New']]" />
    <h1 class="text-2xl font-extrabold mb-4">New Service Category</h1>
    <form method="POST" action="{{ route('admin.service-categories.store') }}">
        @csrf
        @include('admin.service-categories._form')
        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
