<x-layout.employee title="My Documents">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Documents</h1>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Uploaded Documents</h3>
            <div class="overflow-x-auto">
                <table class="table table-striped w-full">
                    <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Remarks</th><th></th></tr></thead>
                    <tbody>
                        @forelse($documents as $doc)
                            @php $vc = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'][$doc->verification_status] ?? 'secondary'; @endphp
                            <tr>
                                <td class="font-semibold">{{ $doc->title }}</td>
                                <td>{{ $doc->doc_type_label }}</td>
                                <td><span class="badge bg-{{ $vc }}/10 text-{{ $vc }}">{{ ucfirst($doc->verification_status) }}</span></td>
                                <td class="text-sm text-gray-500">{{ $doc->verification_remarks ?? '—' }}</td>
                                <td class="text-right">
                                    @if($doc->is_viewable)
                                        <a href="{{ route('employee.documents.view', $doc) }}" target="_blank" rel="noopener" class="text-primary text-sm">View</a>
                                    @endif
                                    <a href="{{ route('employee.documents.download', $doc) }}" class="text-primary text-sm ml-2">Download</a>
                                    @if($doc->verification_status === 'pending' && $doc->employee_uploaded_by)
                                        <form method="POST" action="{{ route('employee.documents.destroy', $doc) }}" class="inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-gray-400 py-8">No documents yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Upload New</h3>
            <form method="POST" action="{{ route('employee.documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div><label class="text-xs text-gray-500">Type *</label>
                    <select name="doc_type" class="form-select" required>@foreach($docTypes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                </div>
                <div><label class="text-xs text-gray-500">Title *</label><input name="title" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Expires On</label><input type="date" name="expires_on" class="form-input"></div>
                <div><label class="text-xs text-gray-500">File *</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="form-input" required></div>
                <button class="btn btn-primary w-full">Upload</button>
            </form>
        </div>
    </div>
</x-layout.employee>
