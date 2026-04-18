<x-layout.admin title="Roles & Permissions">
    <style>
        .role-card { transition: all 0.2s ease; border: 1.5px solid #e5e7eb; }
        .role-card:hover { border-color: #7C3AEC; box-shadow: 0 4px 20px rgba(124,58,236,0.12); transform: translateY(-2px); }
        .badge-violet { background: rgba(124,58,236,0.12); color: #7C3AEC; font-weight: 600; font-size: 11px; padding: 3px 10px; border-radius: 20px; }
        .badge-slate  { background: rgba(100,116,139,0.1); color: #475569; font-weight: 600; font-size: 11px; padding: 3px 10px; border-radius: 20px; }
        .btn-violet   { background: #7C3AEC; color: #fff; border: none; }
        .btn-violet:hover { background: #6D28D9; color: #fff; box-shadow: 0 4px 14px rgba(124,58,236,0.4); }
        .btn-violet-outline { border: 1.5px solid #7C3AEC; color: #7C3AEC; background: transparent; }
        .btn-violet-outline:hover { background: #7C3AEC; color: #fff; }
    </style>

    <div>
        <x-admin.breadcrumb :items="[['label' => 'Roles & Permissions']]" />

        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h5 class="text-xl font-bold text-gray-800 dark:text-white">Roles & Permissions</h5>
                <p class="text-sm text-gray-500 mt-0.5">Manage access control for your team</p>
            </div>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-violet gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Role
            </a>
        </div>

        {{-- Stats bar --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="panel flex items-center gap-4 py-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:rgba(124,58,236,0.1)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" style="color:#7C3AEC" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $roles->count() }}</div>
                    <div class="text-xs text-gray-500">Total Roles</div>
                </div>
            </div>
            <div class="panel flex items-center gap-4 py-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:rgba(16,185,129,0.1)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $roles->sum(fn($r) => $r->users_count ?? $r->users->count()) }}</div>
                    <div class="text-xs text-gray-500">Users Assigned</div>
                </div>
            </div>
            <div class="panel flex items-center gap-4 py-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:rgba(59,130,246,0.1)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $roles->sum(fn($r) => $r->permissions_count ?? $r->permissions->count()) }}</div>
                    <div class="text-xs text-gray-500">Total Permissions</div>
                </div>
            </div>
        </div>

        {{-- Role Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($roles as $role)
                <div class="role-card panel rounded-xl p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-white text-sm"
                                style="background: linear-gradient(135deg, #7C3AEC, #A855F7)">
                                {{ strtoupper(substr($role->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 dark:text-white">{{ $role->name }}</div>
                                @if($role->name === 'Super Admin')
                                    <span style="background:rgba(239,68,68,0.1);color:#DC2626;font-size:10px;padding:1px 8px;border-radius:20px;font-weight:600;">SYSTEM</span>
                                @else
                                    <span style="background:rgba(124,58,236,0.1);color:#7C3AEC;font-size:10px;padding:1px 8px;border-radius:20px;font-weight:600;">CUSTOM</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <a href="{{ route('admin.roles.edit', $role->id) }}"
                               class="w-8 h-8 rounded-lg flex items-center justify-center btn-violet-outline transition-all"
                               data-tippy-content="Edit Permissions">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @if($role->name !== 'Super Admin')
                                <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline" onsubmit="return confirmDelete(event)">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center border-1.5 border-danger text-danger hover:bg-danger hover:text-white transition-all"
                                        style="border:1.5px solid #ef4444;"
                                        data-tippy-content="Delete Role">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-3 mb-4">
                        <div class="flex-1 rounded-lg p-3 text-center" style="background:#f8f4ff;">
                            <div class="text-lg font-bold" style="color:#7C3AEC">{{ $role->permissions_count ?? $role->permissions->count() }}</div>
                            <div class="text-xs text-gray-500">Permissions</div>
                        </div>
                        <div class="flex-1 rounded-lg p-3 text-center" style="background:#f0fdf4;">
                            <div class="text-lg font-bold text-success">{{ $role->users_count ?? $role->users->count() }}</div>
                            <div class="text-xs text-gray-500">Users</div>
                        </div>
                    </div>

                    <a href="{{ route('admin.roles.edit', $role->id) }}"
                       class="flex items-center justify-center gap-2 w-full py-2 rounded-lg text-sm font-semibold transition-all btn-violet-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Manage Permissions
                    </a>
                </div>
            @empty
                <div class="col-span-3 px-6 py-16 text-center">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-primary/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No roles yet</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Create your first role to control user access.</p>
                        </div>
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm mt-1 gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Create Role
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function confirmDelete(event) {
            event.preventDefault();
            const form = event.target;
            Swal.fire({ title: 'Delete this role?', text: 'All users with this role will lose their permissions!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel', confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280', reverseButtons: true, padding: '2em' }).then(r => { if (r.isConfirmed) form.submit(); });
            return false;
        }
    </script>
</x-layout.admin>
