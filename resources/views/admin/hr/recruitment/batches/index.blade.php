<x-layout.admin title="Campus Batches">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Batches']]" />
    <h1 class="text-2xl font-extrabold mb-1">Campus Recruitment Batches</h1>
    <p class="text-sm text-gray-500 mb-5">Group candidates that came from a single campus drive.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel overflow-x-auto">
            <table class="table-striped w-full">
                <thead><tr><th>Name</th><th>Institution</th><th>Drive Date</th><th>Coordinator</th><th>Candidates</th><th></th></tr></thead>
                <tbody>
                    @forelse($batches as $b)
                        <tr>
                            <td class="font-semibold">{{ $b->name }}</td>
                            <td>{{ $b->institution ?? '—' }}</td>
                            <td>{{ optional($b->drive_date)->format('d M Y') ?? '—' }}</td>
                            <td>{{ $b->coordinator ?? '—' }}</td>
                            <td>{{ $b->candidates_count }}</td>
                            <td class="text-right"><button onclick="document.getElementById('eb-{{ $b->id }}').classList.toggle('hidden')" class="text-primary text-sm">Edit</button></td>
                        </tr>
                        <tr id="eb-{{ $b->id }}" class="hidden bg-gray-50 dark:bg-[#0e1726]"><td colspan="6" class="p-3">
                            <form method="POST" action="{{ route('admin.hr.recruitment.batches.update', $b) }}" class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $b->name }}" class="form-input" placeholder="Name" required>
                                <input name="institution" value="{{ $b->institution }}" class="form-input" placeholder="Institution">
                                <input type="date" name="drive_date" value="{{ optional($b->drive_date)->format('Y-m-d') }}" class="form-input">
                                <input name="coordinator" value="{{ $b->coordinator }}" class="form-input" placeholder="Coordinator">
                                <button class="btn btn-primary btn-sm">Save</button>
                            </form>
                            <form method="POST" action="{{ route('admin.hr.recruitment.batches.destroy', $b) }}" onsubmit="return confirm('Delete batch?')" class="mt-2">
                                @csrf @method('DELETE')<button class="text-danger text-xs">Delete</button>
                            </form>
                        </td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400 py-8">No batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">New Batch</div>
            <form method="POST" action="{{ route('admin.hr.recruitment.batches.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-xs text-gray-500">Batch Name *</label><input name="name" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Institution</label><input name="institution" class="form-input"></div>
                <div><label class="text-xs text-gray-500">Drive Date</label><input type="date" name="drive_date" class="form-input"></div>
                <div><label class="text-xs text-gray-500">Coordinator</label><input name="coordinator" class="form-input"></div>
                <div><label class="text-xs text-gray-500">Notes</label><textarea name="notes" rows="2" class="form-textarea"></textarea></div>
                <button class="btn btn-primary w-full">Create Batch</button>
            </form>
        </div>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-layout.admin>
