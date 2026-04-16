<x-layout.admin>
    <div>
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Warehouses</h5>
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Warehouse
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 mb-5 border-l-4 border-success rounded bg-success-light dark:bg-success dark:bg-opacity-20">
                <p class="text-sm text-success">{{ session('success') }}</p>
            </div>
        @endif

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Code</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Address</th>
                            <th class="px-4 py-2">Is Default</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $index => $warehouse)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">{{ $warehouse->code }}</td>
                                <td class="px-4 py-2">{{ $warehouse->name }}</td>
                                <td class="px-4 py-2">{{ $warehouse->address ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    @if($warehouse->is_default)
                                        <span class="badge bg-primary">Default</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <form action="{{ route('admin.warehouses.toggle-status', $warehouse->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="badge cursor-pointer {{ $warehouse->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this warehouse?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No warehouses found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
