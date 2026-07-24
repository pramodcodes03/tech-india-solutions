<x-layout.admin title="Candidate">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => $candidate->full_name]]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger mb-4">{{ session('error') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $candidate->full_name }}</h1>
            <div class="text-sm text-gray-500">{{ $candidate->candidate_code }} · {{ $candidate->designation?->name ?? 'Role N/A' }}</div>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.hr.recruitment.offer-letter', $candidate) }}" target="_blank" class="btn btn-outline-primary">Offer Letter PDF</a>
            @can('recruitment.edit')<a href="{{ route('admin.hr.recruitment.edit', $candidate) }}" class="btn btn-outline-secondary">Edit</a>@endcan
            @can('recruitment.delete')
                <form method="POST" action="{{ route('admin.hr.recruitment.destroy', $candidate) }}" onsubmit="return confirm('Delete this candidate?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Left: profile --}}
        <div class="space-y-5">
            <div class="panel p-5">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Profile</div>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $candidate->email ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $candidate->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Location</dt><dd>{{ $candidate->current_location ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Experience</dt><dd>{{ $candidate->total_experience ? $candidate->total_experience.' yrs' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Current CTC</dt><dd>{{ $candidate->current_ctc ? number_format($candidate->current_ctc) : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Expected CTC</dt><dd>{{ $candidate->expected_ctc ? number_format($candidate->expected_ctc) : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Notice</dt><dd>{{ $candidate->notice_period_days ? $candidate->notice_period_days.' days' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Source</dt><dd>{{ $candidate->source_label }}</dd></div>
                    @if($candidate->referrer)<div class="flex justify-between"><dt class="text-gray-500">Referred By</dt><dd>{{ $candidate->referrer->full_name }}</dd></div>@endif
                    @if($candidate->batch)<div class="flex justify-between"><dt class="text-gray-500">Campus / Batch</dt><dd class="text-right">{{ $candidate->batch->name }}@if($candidate->batch->institution)<div class="text-xs text-gray-500">{{ $candidate->batch->institution }}</div>@endif</dd></div>@endif
                </dl>
                @if($candidate->resume_path)
                    <a href="{{ Storage::url($candidate->resume_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-full mt-4">View Resume</a>
                @endif
            </div>

            @if($candidate->notes)
                <div class="panel p-5">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Notes</div>
                    <p class="text-sm whitespace-pre-line">{{ $candidate->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Middle: stage + offer --}}
        <div class="space-y-5">
            <div class="panel p-5">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Move Stage</div>
                @php $sc = ['active'=>'warning','hired'=>'success','rejected'=>'danger','withdrawn'=>'secondary'][$candidate->status] ?? 'secondary'; @endphp
                <div class="mb-3">Current: <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($candidate->status) }}</span>
                    @if($candidate->stage)<span class="badge ml-1" style="background: {{ $candidate->stage->color }}1a; color: {{ $candidate->stage->color }};">{{ $candidate->stage->name }}</span>@endif
                </div>
                @can('recruitment.edit')
                <form method="POST" action="{{ route('admin.hr.recruitment.move', $candidate) }}" class="space-y-2">
                    @csrf
                    <select name="stage_id" class="form-select" required>
                        @foreach($stages as $s)<option value="{{ $s->id }}" @selected($candidate->stage_id==$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                    <input type="text" name="remarks" placeholder="Remarks (optional)" class="form-input" />
                    <button class="btn btn-primary w-full">Update Stage</button>
                </form>
                @endcan
            </div>

            <div class="panel p-5">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Offer Details</div>
                <form method="POST" action="{{ route('admin.hr.recruitment.offer-letter', $candidate) }}" target="_blank" class="space-y-2">
                    @csrf
                    <input type="text" name="offer_designation" value="{{ $candidate->offer_designation ?? $candidate->designation?->name }}" placeholder="Designation" class="form-input" />
                    <input type="number" step="0.01" name="offer_ctc" value="{{ $candidate->offer_ctc ?? $candidate->expected_ctc }}" placeholder="Offered CTC" class="form-input" />
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="offer_date" value="{{ optional($candidate->offer_date)->format('Y-m-d') ?? date('Y-m-d') }}" class="form-input" />
                        <input type="date" name="proposed_joining_date" value="{{ optional($candidate->proposed_joining_date)->format('Y-m-d') }}" class="form-input" />
                    </div>
                    <button class="btn btn-outline-primary w-full">Generate Offer Letter (PDF)</button>
                    <button type="submit"
                            formaction="{{ route('admin.hr.recruitment.offer-letter.email', $candidate) }}"
                            formtarget="_self"
                            @disabled(empty($candidate->email))
                            class="btn btn-primary w-full"
                            onclick="return confirm('Email the offer letter to {{ $candidate->email ?: 'this candidate' }}?')">
                        ✉ Email Offer Letter to Candidate
                    </button>
                    @if(empty($candidate->email))
                        <p class="text-[11px] text-danger">No email on file — add the candidate's email to enable sending.</p>
                    @else
                        <p class="text-[11px] text-gray-400">Will be sent to <strong>{{ $candidate->email }}</strong>.</p>
                    @endif
                </form>
            </div>
        </div>

        {{-- Right: timeline --}}
        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Timeline</div>
            @can('recruitment.edit')
            <form method="POST" action="{{ route('admin.hr.recruitment.note', $candidate) }}" class="flex gap-2 mb-4">
                @csrf
                <input type="text" name="remarks" placeholder="Add a note…" class="form-input" required />
                <button class="btn btn-primary">Add</button>
            </form>
            @endcan
            <ol class="relative border-l border-gray-200 ltr:ml-2 space-y-4">
                @forelse($candidate->history as $h)
                    <li class="ltr:ml-4">
                        <div class="absolute w-2.5 h-2.5 bg-primary rounded-full -left-[5px] mt-1.5"></div>
                        <div class="text-sm font-semibold">
                            @if($h->action === 'created') Added to pipeline
                            @elseif($h->action === 'note') Note
                            @elseif($h->action === 'hired') Marked Hired
                            @elseif($h->action === 'rejected') Marked Rejected
                            @else Moved {{ $h->fromStage?->name ? $h->fromStage->name.' → ' : '' }}{{ $h->toStage?->name }}
                            @endif
                        </div>
                        @if($h->remarks)<div class="text-xs text-gray-600">{{ $h->remarks }}</div>@endif
                        <div class="text-[11px] text-gray-400">{{ $h->created_at->format('d M Y, H:i') }} · {{ $h->mover?->name ?? 'System' }}</div>
                    </li>
                @empty
                    <li class="ltr:ml-4 text-gray-400 text-sm">No activity yet.</li>
                @endforelse
            </ol>
        </div>
    </div>
</x-layout.admin>
