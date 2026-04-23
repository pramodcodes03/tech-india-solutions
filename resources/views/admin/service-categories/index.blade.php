<x-layout.admin title="Service Categories">
    <x-admin.breadcrumb :items="[['label' => 'Service'], ['label' => 'Categories']]" />

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-extrabold">Service Categories</h1>
            <div class="text-sm text-gray-500">Types of work tickets can be raised for — Electrician, Plumber, etc.</div>
        </div>
        @can('service_tickets.create')
            <a href="{{ route('admin.service-categories.create') }}" class="btn btn-primary">+ Add Category</a>
        @endcan
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search categories..." class="form-input max-w-sm" />
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        @forelse($categories as $c)
            <div class="panel p-4" style="border-left: 4px solid {{ $c->color }}">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2 min-w-0">
                        @if($c->icon)
                            <span class="text-2xl shrink-0">{!! $c->icon !!}</span>
                        @endif
                        <div class="min-w-0">
                            <div class="font-bold truncate">{{ $c->name }}</div>
                            <div class="text-xs text-gray-500">{{ $c->tickets_count }} ticket{{ $c->tickets_count === 1 ? '' : 's' }}</div>
                        </div>
                    </div>
                    <span @class([
                        'shrink-0 px-2 py-0.5 rounded text-xs font-semibold',
                        'bg-success/10 text-success' => $c->status === 'active',
                        'bg-gray-200 text-gray-600' => $c->status !== 'active',
                    ])>{{ ucfirst($c->status) }}</span>
                </div>
                @if($c->description)
                    <div class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $c->description }}</div>
                @endif
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                    @can('service_tickets.edit')
                        <a href="{{ route('admin.service-categories.edit', $c) }}" class="text-info text-xs font-semibold hover:underline">Edit</a>
                    @endcan
                    <a href="{{ route('admin.service-tickets.index', ['category_id' => $c->id]) }}" class="text-primary text-xs font-semibold hover:underline">View Tickets →</a>
                    @can('service_tickets.delete')
                        @if($c->tickets_count === 0)
                            <form method="POST" action="{{ route('admin.service-categories.destroy', $c) }}" class="inline ml-auto" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="text-danger text-xs font-semibold hover:underline">Delete</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-span-full panel p-8 text-center text-gray-500">No service categories yet. Click "Add Category" to create one.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $categories->links() }}</div>
</x-layout.admin>
