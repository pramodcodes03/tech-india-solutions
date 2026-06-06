<x-layout.admin title="Recruitment Pipeline">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Pipeline']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Recruitment Pipeline</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline-secondary">List View</a>
            @can('recruitment.create')<a href="{{ route('admin.hr.recruitment.create') }}" class="btn btn-primary">+ Add Candidate</a>@endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    {{-- Source filter --}}
    <form method="GET" class="flex gap-2 mb-5 flex-wrap">
        <select name="source" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Sources</option>
            @foreach($sources as $val => $label)<option value="{{ $val }}" @selected(request('source')===$val)>{{ $label }}</option>@endforeach
        </select>
        <select name="batch_id" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Batches</option>
            @foreach($batches as $b)<option value="{{ $b->id }}" @selected(request('batch_id')==$b->id)>{{ $b->name }}</option>@endforeach
        </select>
    </form>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($stages as $stage)
            @php $list = $candidates[$stage->id] ?? collect(); @endphp
            <div class="shrink-0 w-72">
                <div class="rounded-t-xl px-3 py-2 flex items-center justify-between text-white font-semibold text-sm" style="background: {{ $stage->color }};">
                    <span>{{ $stage->name }}</span>
                    <span class="bg-white/25 rounded-full px-2 text-xs">{{ $list->count() }}</span>
                </div>
                <div class="bg-gray-50 dark:bg-[#0e1726] rounded-b-xl p-2 space-y-2 min-h-[120px]" data-stage="{{ $stage->id }}">
                    @forelse($list as $c)
                        <div class="panel p-3 cursor-move" draggable="true" data-candidate="{{ $c->id }}">
                            <a href="{{ route('admin.hr.recruitment.show', $c) }}" class="font-semibold text-sm hover:text-primary">{{ $c->full_name }}</a>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $c->designation?->name ?? '—' }}</div>
                            <div class="flex items-center justify-between mt-1.5">
                                <span class="badge bg-info/10 text-info text-[10px]">{{ $c->source_label }}</span>
                                <span class="text-[10px] text-gray-400">{{ $c->candidate_code }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-300 text-xs py-6">Empty</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <script>
        let dragged = null;
        document.querySelectorAll('[data-candidate]').forEach(card => {
            card.addEventListener('dragstart', () => dragged = card);
        });
        document.querySelectorAll('[data-stage]').forEach(col => {
            col.addEventListener('dragover', e => e.preventDefault());
            col.addEventListener('drop', e => {
                e.preventDefault();
                if (!dragged) return;
                const candidateId = dragged.getAttribute('data-candidate');
                const stageId = col.getAttribute('data-stage');
                col.appendChild(dragged);
                fetch(`{{ url('admin/hr/recruitment') }}/${candidateId}/move`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                    body: JSON.stringify({stage_id: stageId})
                }).then(r => r.json()).then(() => location.reload());
            });
        });
    </script>
</x-layout.admin>
