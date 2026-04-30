<x-layout.admin title="Businesses">
    <x-admin.breadcrumb :items="[['label' => 'Businesses']]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold dark:text-white-light">Businesses</h5>
        <a href="{{ route('admin.businesses.create') }}" class="btn btn-primary gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Business
        </a>
    </div>

    @if (session('success'))
        <div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Logo</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2">GST</th>
                        <th class="px-4 py-2">Admins</th>
                        <th class="px-4 py-2">Employees</th>
                        <th class="px-4 py-2">Customers</th>
                        <th class="px-4 py-2">Invoices</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 !text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($businesses as $i => $business)
                        <tr>
                            <td class="px-4 py-2">{{ $businesses->firstItem() + $i }}</td>
                            <td class="px-4 py-2">
                                @if($business->logo)
                                    <img src="{{ asset('storage/'.$business->logo) }}" class="w-10 h-10 rounded object-cover" alt="logo" />
                                @else
                                    <div class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs">{{ strtoupper(substr($business->name, 0, 2)) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-semibold">{{ $business->name }}</td>
                            <td class="px-4 py-2"><code>{{ $business->slug }}</code></td>
                            <td class="px-4 py-2">{{ $business->gst ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $business->admins_count }}</td>
                            <td class="px-4 py-2">{{ $business->employees_count }}</td>
                            <td class="px-4 py-2">{{ $business->customers_count }}</td>
                            <td class="px-4 py-2">{{ $business->invoices_count }}</td>
                            <td class="px-4 py-2">
                                @if($business->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 !text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form method="POST" action="{{ route('admin.businesses.switch', $business) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Switch to this business">Switch</button>
                                    </form>
                                    <a href="{{ route('admin.businesses.show', $business) }}#admins" class="btn btn-sm btn-outline-info" title="View business + manage admin credentials">View / Admins</a>
                                    <a href="{{ route('admin.businesses.edit', $business) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                    <form method="POST" action="{{ route('admin.businesses.destroy', $business) }}" onsubmit="return confirm('Archive this business?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 text-gray-500">No businesses yet. <a href="{{ route('admin.businesses.create') }}" class="text-primary">Create the first one</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $businesses->links() }}</div>
    </div>
</x-layout.admin>
