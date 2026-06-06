<x-layout.admin title="Recruitment">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment']]" />

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Recruitment & Hiring</h1>
            <p class="text-sm text-gray-500 mt-0.5">Candidate pipeline, sources, referrals and campus drives</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.hr.recruitment.pipeline') }}" class="btn btn-outline-primary">Pipeline Board</a>
            <a href="{{ route('admin.hr.recruitment.reports') }}" class="btn btn-outline-secondary">Reports</a>
            <a href="{{ route('admin.hr.recruitment.batches.index') }}" class="btn btn-outline-secondary">Batches</a>
            <a href="{{ route('admin.hr.recruitment.stages.index') }}" class="btn btn-outline-secondary">Stages</a>
            @can('recruitment.create')
                <a href="{{ route('admin.hr.recruitment.import.form') }}" class="btn btn-outline-secondary">Bulk Import</a>
                <a href="{{ route('admin.hr.recruitment.create') }}" class="btn btn-primary">+ Add Candidate</a>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning mb-4">{{ session('warning') }}</div>@endif

    @if(session('import_errors') && count(session('import_errors')))
        <div class="panel p-4 mb-4 border-l-4 border-danger">
            <div class="font-semibold text-danger mb-2">Some rows were skipped:</div>
            <ul class="text-xs text-gray-600 list-disc ltr:pl-5 space-y-0.5">
                @foreach(session('import_errors') as $err)
                    <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="panel p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / email / code" class="form-input md:col-span-2" />
        <select name="source" class="form-select">
            <option value="">All Sources</option>
            @foreach($sources as $val => $label)<option value="{{ $val }}" @selected(request('source')===$val)>{{ $label }}</option>@endforeach
        </select>
        <select name="stage_id" class="form-select">
            <option value="">All Stages</option>
            @foreach($stages as $s)<option value="{{ $s->id }}" @selected(request('stage_id')==$s->id)>{{ $s->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['active'=>'Active','hired'=>'Hired','rejected'=>'Rejected','withdrawn'=>'Withdrawn'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1">Filter</button>
            <a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead>
                <tr>
                    <th>Code</th><th>Candidate</th><th>Role</th><th>Source</th><th>Stage</th><th>Status</th><th>Applied</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $c)
                    <tr>
                        <td class="font-mono text-xs">{{ $c->candidate_code }}</td>
                        <td>
                            <div class="font-semibold">{{ $c->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $c->email }} {{ $c->phone ? '· '.$c->phone : '' }}</div>
                            @if($c->referrer)<div class="text-[11px] text-info">Referred by {{ $c->referrer->full_name }}</div>@endif
                        </td>
                        <td class="text-sm">{{ $c->designation?->name ?? '—' }}</td>
                        <td><span class="badge bg-info/10 text-info">{{ $c->source_label }}</span></td>
                        <td>
                            @if($c->stage)
                                <span class="badge" style="background: {{ $c->stage->color }}1a; color: {{ $c->stage->color }};">{{ $c->stage->name }}</span>
                            @else — @endif
                        </td>
                        <td>
                            @php $sc = ['active'=>'warning','hired'=>'success','rejected'=>'danger','withdrawn'=>'secondary'][$c->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($c->status) }}</span>
                        </td>
                        <td class="text-xs text-gray-500">{{ optional($c->applied_at)->format('d M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.hr.recruitment.show', $c) }}" class="text-primary text-sm font-semibold">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-10">No candidates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $candidates->links() }}</div>
</x-layout.admin>
