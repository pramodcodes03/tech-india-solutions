<x-layout.admin title="Edit Ticket">
    <x-admin.breadcrumb :items="[['label' => 'Service Tickets', 'url' => route('admin.service-tickets.index')], ['label' => $ticket->ticket_number]]" />

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-extrabold">Edit Ticket <span class="text-gray-500 text-base font-mono">#{{ $ticket->ticket_number }}</span></h1>
        <a href="{{ route('admin.service-tickets.show', $ticket) }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <form action="{{ route('admin.service-tickets.update', $ticket->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.service-tickets._form', [
            'ticket' => $ticket,
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'admins' => $admins,
        ])

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.service-tickets.show', $ticket) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Ticket</button>
        </div>
    </form>
</x-layout.admin>
