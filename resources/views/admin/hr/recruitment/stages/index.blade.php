<x-layout.admin title="Hiring Stages">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Stages']]" />
    <h1 class="text-2xl font-extrabold mb-1">Hiring Stages</h1>
    <p class="text-sm text-gray-500 mb-5">Configure the pipeline. Drag to reorder; terminal types (Hired / Rejected) auto-set candidate status.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel p-0 overflow-hidden">
            <table class="table-striped w-full">
                <thead><tr><th>Order</th><th>Name</th><th>Type</th><th>Candidates</th><th></th></tr></thead>
                <tbody id="stageRows">
                    @foreach($stages as $stage)
                        <tr data-id="{{ $stage->id }}">
                            <td class="cursor-move text-gray-400">⋮⋮ {{ $stage->sort_order }}</td>
                            <td>
                                <span class="inline-block w-3 h-3 rounded-full align-middle mr-1" style="background: {{ $stage->color }}"></span>
                                {{ $stage->name }}
                            </td>
                            <td><span class="badge bg-secondary/10 text-secondary">{{ ucfirst($stage->type) }}</span></td>
                            <td>{{ $stage->candidates_count }}</td>
                            <td class="text-right">
                                <button onclick="document.getElementById('edit-{{ $stage->id }}').classList.toggle('hidden')" class="text-primary text-sm">Edit</button>
                            </td>
                        </tr>
                        <tr id="edit-{{ $stage->id }}" class="hidden bg-gray-50 dark:bg-[#0e1726]">
                            <td colspan="5" class="p-3">
                                <form method="POST" action="{{ route('admin.hr.recruitment.stages.update', $stage) }}" class="flex gap-2 items-end flex-wrap">
                                    @csrf @method('PUT')
                                    <div><label class="text-xs text-gray-500">Name</label><input name="name" value="{{ $stage->name }}" class="form-input" required></div>
                                    <div><label class="text-xs text-gray-500">Type</label>
                                        <select name="type" class="form-select">
                                            @foreach(['open'=>'Open','hired'=>'Hired','rejected'=>'Rejected'] as $v=>$l)<option value="{{ $v }}" @selected($stage->type===$v)>{{ $l }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Color</label><input type="color" name="color" value="{{ $stage->color }}" class="form-input w-16 h-10 p-1"></div>
                                    <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="status" value="1" @checked($stage->status)> Active</label>
                                    <button class="btn btn-primary btn-sm">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.hr.recruitment.stages.destroy', $stage) }}" onsubmit="return confirm('Delete stage?')" class="mt-2">
                                    @csrf @method('DELETE')
                                    <button class="text-danger text-xs">Delete stage</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Add Stage</div>
            <form method="POST" action="{{ route('admin.hr.recruitment.stages.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-xs text-gray-500">Name *</label><input name="name" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Type *</label>
                    <select name="type" class="form-select">
                        <option value="open">Open (in pipeline)</option>
                        <option value="hired">Hired (terminal)</option>
                        <option value="rejected">Rejected (terminal)</option>
                    </select>
                </div>
                <div><label class="text-xs text-gray-500">Color</label><input type="color" name="color" value="#6366f1" class="form-input w-full h-10 p-1"></div>
                <button class="btn btn-primary w-full">Add Stage</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        new Sortable(document.getElementById('stageRows'), {
            handle: 'td:first-child', animation: 150,
            onEnd: () => {
                const order = [...document.querySelectorAll('#stageRows tr[data-id]')].map(r => r.getAttribute('data-id'));
                fetch('{{ route('admin.hr.recruitment.stages.reorder') }}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                    body: JSON.stringify({order})
                });
            }
        });
    </script>
</x-layout.admin>
