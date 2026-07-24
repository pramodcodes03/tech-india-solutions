<x-layout.admin title="Helpdesk Config">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Helpdesk', 'url' => route('admin.hr.internal-tickets.index')], ['label' => 'Configure']]" />
    <h1 class="text-2xl font-extrabold mb-1">Helpdesk Configuration</h1>
    <p class="text-sm text-gray-500 mb-5">Categories carry per-department TAT; the escalation matrix defines who owns each level after how many days.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Categories --}}
        <div class="panel p-5">
            <h6 class="font-bold mb-3">Categories &amp; TAT</h6>
            <form method="POST" action="{{ route('admin.hr.internal-tickets.categories.store') }}"
                  class="grid grid-cols-2 gap-2 items-end mb-4"
                  x-data="categoryAssignee()">
                @csrf
                <div>
                    <label class="text-xs text-gray-500">Department</label>
                    <select name="department" class="form-select">@foreach(\App\Models\InternalTicket::DEPARTMENTS as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Category name</label>
                    <input name="name" placeholder="Category name" class="form-input" required>
                </div>

                {{-- Role → loads the users of that role into the next dropdown --}}
                <div>
                    <label class="text-xs text-gray-500">Assign to Role</label>
                    <select name="assigned_role" class="form-select" x-model="role" @change="loadUsers()">
                        <option value="">— Select role —</option>
                        @foreach($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Receiving User</label>
                    <select name="assigned_admin_id" class="form-select" x-model="userId" :disabled="!role || loading">
                        <option value="">— <span x-text="loading ? 'Loading…' : 'Select user'"></span> —</option>
                        <template x-for="u in users" :key="u.id">
                            <option :value="u.id" x-text="u.name"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-0.5" x-show="role && !loading && users.length === 0" x-cloak>No active users in this role.</p>
                </div>

                <div>
                    <label class="text-xs text-gray-500">TAT hours</label>
                    <input type="number" name="tat_hours" placeholder="TAT hours" value="48" class="form-input" required>
                </div>
                <div class="flex items-end">
                    <button class="btn btn-primary w-full">Add</button>
                </div>
            </form>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Dept</th><th>Name</th><th>Receiving User</th><th>TAT</th><th></th></tr></thead>
                <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td>{{ \App\Models\InternalTicket::DEPARTMENTS[$c->department] ?? $c->department }}</td>
                            <td>{{ $c->name }}</td>
                            <td>
                                @if($c->assignedAdmin)
                                    <span class="font-semibold">{{ $c->assignedAdmin->name }}</span>
                                    @if($c->assigned_role)<span class="text-[11px] text-gray-400">({{ $c->assigned_role }})</span>@endif
                                @else
                                    <span class="text-gray-400 text-xs">All admins</span>
                                @endif
                            </td>
                            <td>{{ $c->tat_hours }}h</td>
                            <td class="text-right"><form method="POST" action="{{ route('admin.hr.internal-tickets.categories.destroy', $c) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-4">None.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <script>
                function categoryAssignee() {
                    return {
                        role: '', userId: '', users: [], loading: false,
                        loadUsers() {
                            this.userId = ''; this.users = [];
                            if (!this.role) return;
                            this.loading = true;
                            fetch(`{{ route('admin.hr.internal-tickets.admins-by-role') }}?role=${encodeURIComponent(this.role)}`, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(r => r.json())
                            .then(data => { this.users = data; })
                            .finally(() => { this.loading = false; });
                        },
                    };
                }
            </script>
        </div>

        {{-- Escalation matrix --}}
        <div class="panel p-5">
            <h6 class="font-bold mb-3">Escalation Matrix</h6>
            <form method="POST" action="{{ route('admin.hr.internal-tickets.levels.store') }}" class="grid grid-cols-2 gap-2 items-end mb-4">
                @csrf
                <select name="department" class="form-select">@foreach(\App\Models\InternalTicket::DEPARTMENTS as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                <input type="number" name="level" placeholder="Level (1,2…)" class="form-input" required>
                <input type="number" name="after_days" placeholder="After days" class="form-input" required>
                <select name="owner_admin_id" class="form-select"><option value="">— Owner —</option>@foreach($admins as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                <button class="btn btn-primary col-span-2">Add Level</button>
            </form>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Dept</th><th>Level</th><th>After</th><th>Owner</th><th></th></tr></thead>
                <tbody>
                    @forelse($levels as $lv)
                        <tr>
                            <td>{{ \App\Models\InternalTicket::DEPARTMENTS[$lv->department] ?? $lv->department }}</td>
                            <td>{{ $lv->level }}</td>
                            <td>{{ $lv->after_days }}d</td>
                            <td>{{ $lv->owner?->name ?? '—' }}</td>
                            <td class="text-right"><form method="POST" action="{{ route('admin.hr.internal-tickets.levels.destroy', $lv) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-4">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
