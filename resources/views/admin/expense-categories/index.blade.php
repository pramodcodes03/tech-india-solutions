<x-layout.admin title="Payment Categories">
    <x-admin.breadcrumb :items="[['label' => 'Routine Payment Tracker', 'url' => route('admin.expenses.index')], ['label' => 'Categories']]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold dark:text-white-light">Payment Categories</h5>
        @can('expense_categories.create')
            <a href="{{ route('admin.expense-categories.create') }}" class="btn btn-primary gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Category
            </a>
        @endcan
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>@endif

    <div class="panel px-0">
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2">Subcategories</th>
                        <th class="px-4 py-2">Payments</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 !text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $i => $cat)
                        <tr>
                            <td class="px-4 py-2">{{ $categories->firstItem() + $i }}</td>
                            <td class="px-4 py-2">
                                @if($cat->color)
                                    <span class="inline-block w-3 h-3 rounded-full mr-2" style="background:{{ $cat->color }}"></span>
                                @endif
                                <span class="font-semibold">{{ $cat->name }}</span>
                            </td>
                            <td class="px-4 py-2"><code>{{ $cat->slug }}</code></td>
                            <td class="px-4 py-2">{{ $cat->subcategories->count() }}</td>
                            <td class="px-4 py-2">{{ $cat->expenses_count }}</td>
                            <td class="px-4 py-2">
                                @if($cat->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-warning">Inactive</span>@endif
                            </td>
                            <td class="px-4 py-2 !text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.expense-categories.show', $cat) }}" class="btn btn-sm btn-outline-info">Manage</a>
                                    @can('expense_categories.edit')<a href="{{ route('admin.expense-categories.edit', $cat) }}" class="btn btn-sm btn-outline-warning">Edit</a>@endcan
                                    @can('expense_categories.delete')
                                        <form method="POST" action="{{ route('admin.expense-categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-gray-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
</x-layout.admin>
