<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Admin User Details</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.admin-users.edit', $adminUser->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back to List
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->email }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Phone</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Role</label>
                    <p><span class="badge bg-primary">{{ $adminUser->role->name ?? '-' }}</span></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p><span class="badge {{ $adminUser->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($adminUser->status) }}</span></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Last Login</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->last_login_at ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Created At</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Updated At</label>
                    <p class="text-base dark:text-white-light">{{ $adminUser->updated_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-layout.admin>
