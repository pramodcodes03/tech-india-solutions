<x-layout.admin>
    <div>
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Roles</h5>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Role
            </a>
        </div>

        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Permissions Count</th>
                            <th class="px-4 py-2">Users Count</th>
                            <th class="px-4 py-2 !text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $index => $role)
                            <tr>
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-semibold">{{ $role->name }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="badge bg-primary">{{ $role->permissions_count ?? $role->permissions->count() }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="badge bg-info">{{ $role->users_count ?? $role->users->count() }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @if($role->name !== 'Super Admin')
                                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline" onsubmit="return confirmDelete(event)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(event) {
            event.preventDefault();
            const form = event.target;
            const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
            swalWithButtons.fire({ title: 'Are you sure?', text: 'This action cannot be undone!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
    </script>
</x-layout.admin>
