<x-layout.admin title="Employee Documents">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees', 'url' => route('admin.hr.employees.index')], ['label' => $employee->full_name, 'url' => route('admin.hr.employees.show', $employee)], ['label' => 'Documents']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Documents — {{ $employee->full_name }}</h1>
            <p class="text-sm text-gray-500">{{ $employee->employee_code }}</p>
        </div>
        @can('employee_documents.view')
            <a href="{{ route('admin.hr.employees.documents.bulk-download', $employee) }}" class="btn btn-outline-primary">Download All (ZIP)</a>
        @endcan
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-4">
            @forelse($documents as $doc)
                @php $vc = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$doc->verification_status] ?? 'secondary'; @endphp
                <div class="panel p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">{{ $doc->title }}</div>
                            <div class="text-xs text-gray-500">{{ $doc->doc_type_label }} · uploaded {{ $doc->created_at->format('d M Y') }}
                                @if($doc->employee_uploaded_by) by employee @elseif($doc->uploader) by {{ $doc->uploader->name }} @endif
                            </div>
                            @if($doc->expires_on)<div class="text-xs text-gray-500">Expires {{ $doc->expires_on->format('d M Y') }}</div>@endif
                        </div>
                        <span class="badge bg-{{ $vc }}/10 text-{{ $vc }}">{{ ucfirst($doc->verification_status) }}</span>
                    </div>

                    @if($doc->verification_remarks)<div class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 dark:bg-[#0e1726] rounded">Remarks: {{ $doc->verification_remarks }}</div>@endif

                    <div class="flex items-center gap-3 mt-3 flex-wrap">
                        @if($doc->is_viewable)
                            <a href="{{ route('admin.hr.employee-documents.view', $doc) }}" target="_blank" rel="noopener" class="text-primary text-sm font-semibold">View</a>
                        @endif
                        <a href="{{ route('admin.hr.employee-documents.download', $doc) }}" class="text-primary text-sm font-semibold">Download</a>
                        @can('employee_documents.verify')
                            @if($doc->verification_status !== 'verified')
                                <form method="POST" action="{{ route('admin.hr.employee-documents.verify', $doc) }}" class="inline">
                                    @csrf <button class="text-success text-sm">✓ Verify</button>
                                </form>
                            @endif
                            @if($doc->verification_status !== 'rejected')
                                <button onclick="document.getElementById('rej-{{ $doc->id }}').classList.toggle('hidden')" class="text-danger text-sm">✗ Reject</button>
                            @endif
                        @endcan
                        @can('employee_documents.delete')
                            <form method="POST" action="{{ route('admin.hr.employee-documents.destroy', $doc) }}" onsubmit="return confirm('Delete document?')" class="inline">
                                @csrf @method('DELETE')<button class="text-gray-400 text-sm">Delete</button>
                            </form>
                        @endcan
                    </div>
                    <form id="rej-{{ $doc->id }}" method="POST" action="{{ route('admin.hr.employee-documents.reject', $doc) }}" class="hidden mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="remarks" placeholder="Reason for rejection" class="form-input" required>
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>

                    @if($doc->verifications->count())
                        <details class="mt-2 text-xs">
                            <summary class="cursor-pointer text-gray-400">Audit log ({{ $doc->verifications->count() }})</summary>
                            <ul class="mt-1 space-y-1 text-gray-500">
                                @foreach($doc->verifications as $v)
                                    <li>{{ ucfirst($v->action) }} · {{ $v->created_at->format('d M Y H:i') }} · {{ $v->admin?->name ?? $v->employee?->full_name ?? 'System' }}{{ $v->remarks ? ' — '.$v->remarks : '' }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @empty
                <div class="panel p-8 text-center text-gray-400">No documents uploaded yet.</div>
            @endforelse
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Upload Document</div>
            @can('employee_documents.upload')
            <form method="POST" action="{{ route('admin.hr.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div><label class="text-xs text-gray-500">Type *</label>
                    <select name="doc_type" class="form-select" required>
                        @foreach($docTypes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs text-gray-500">Title *</label><input name="title" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Expires On</label><input type="date" name="expires_on" class="form-input"></div>
                <div><label class="text-xs text-gray-500">File *</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="form-input" required></div>
                <button class="btn btn-primary w-full">Upload</button>
            </form>
            @else
                <p class="text-sm text-gray-400">You don't have upload permission.</p>
            @endcan
        </div>
    </div>
</x-layout.admin>
