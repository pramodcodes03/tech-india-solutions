<x-layout.admin title="Categories">
    <div>
        <x-admin.breadcrumb :items="[['label' => 'Categories']]" />

        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Categories</h5>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Category
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
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Slug</th>
                            <th class="px-4 py-2">Parent Category</th>
                            <th class="px-4 py-2">Products Count</th>
                            <th class="px-4 py-2">Sort Order</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $index => $category)
                            <tr>
                                <td class="px-4 py-2">{{ $categories->firstItem() + $index }}</td>
                                <td class="px-4 py-2">{{ $category->name }}</td>
                                <td class="px-4 py-2">{{ $category->slug }}</td>
                                <td class="px-4 py-2">{{ $category->parent->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $category->products_count ?? 0 }}</td>
                                <td class="px-4 py-2">{{ $category->sort_order ?? 0 }}</td>
                                <td class="px-4 py-2">
                                    <form action="{{ route('admin.categories.toggle-status', $category->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="badge cursor-pointer {{ $category->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary p-1.5" data-tippy-content="Edit"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                        @if(($category->products_count ?? 0) === 0 && ($category->children_count ?? 0) === 0)
                                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger opacity-40 cursor-not-allowed" disabled title="Cannot delete: has products or subcategories">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-4 text-center text-gray-500">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="px-5 py-3">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
