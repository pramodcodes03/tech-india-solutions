<x-layout.admin title="Create Ticket">
    <x-admin.breadcrumb :items="[['label' => 'Service Tickets', 'url' => route('admin.service-tickets.index')], ['label' => 'Create']]" />

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-extrabold">Create Service Ticket</h1>
        <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-primary">← Back</a>
    </div>

    <form action="{{ route('admin.service-tickets.store') }}" method="POST">
        @csrf
        @include('admin.service-tickets._form', [
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'admins' => $admins,
        ])

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Ticket</button>
        </div>
    </form>
</x-layout.admin>
