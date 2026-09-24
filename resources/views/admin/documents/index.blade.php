@php use App\Support\DocumentCatalog; @endphp

<x-layout.admin title="Documents">
    <x-admin.breadcrumb :items="[['label' => 'Documents']]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Documents</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ DocumentCatalog::count() }} documents across {{ count($packs) }} packs — every one printed on the shared letterhead.
            </p>
        </div>
        @can('documents.configure')
            <a href="{{ route('admin.documents.letterhead') }}" class="btn btn-outline-primary gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M4 4h16v6H4zM4 14h10M4 18h7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Letterhead Settings
            </a>
        @endcan
    </div>

    @if(empty($visiblePacks))
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">No document packs are available to your role.</div>
            <div class="text-xs mt-1">Ask an administrator to grant the relevant documents_* permission.</div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            {{-- Left: the catalogue, grouped by pack. --}}
            <div class="xl:col-span-2 space-y-4">
                @foreach($visiblePacks as $packKey => [$packLabel, $permission])
                    @php
                        // Only the documents this viewer can actually open. A
                        // pack the viewer holds outright yields all of them; a
                        // pack visible only because of a self-governed document
                        // (the Helpdesk Report) yields just that one, so nobody
                        // is shown a card that 403s when clicked.
                        $docs = array_intersect_key(
                            DocumentCatalog::forPack($packKey),
                            array_flip($reachableKeys),
                        );
                    @endphp
                    @continue(empty($docs))
                    <div class="panel p-0">
                        <div class="p-5 pb-3 flex items-center justify-between">
                            <div>
                                <h2 class="font-bold">{{ $packLabel }}</h2>
                                <p class="text-xs text-gray-500">{{ count($docs) }} document{{ count($docs) === 1 ? '' : 's' }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-gray-400">{{ $permission }}</span>
                        </div>
                        <div class="divide-y divide-gray-50 dark:divide-[#1b2e4b]">
                            @foreach($docs as $docKey => $doc)
                                <a href="{{ route('admin.documents.index', ['document' => $docKey]) }}"
                                   @class([
                                       'flex items-start gap-3 px-5 py-3 transition-colors',
                                       'bg-primary/5 border-l-2 border-l-primary' => $key === $docKey,
                                       'hover:bg-gray-50 dark:hover:bg-[#1b2e4b]' => $key !== $docKey,
                                   ])>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-sm {{ $key === $docKey ? 'text-primary' : '' }}">{{ $doc['name'] }}</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $doc['blurb'] }}</div>
                                    </div>
                                    @php
                                        $scopeLabel = match ($doc['scope']) {
                                            'record' => 'Per record', 'period' => 'Per period', default => 'Register',
                                        };
                                    @endphp
                                    <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-[#1b2e4b] text-gray-500">{{ $scopeLabel }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Right: the generator for the selected document. --}}
            <div>
                <div class="panel p-5 sticky top-4">
                    @if(! $document)
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary grid place-content-center mx-auto mb-3">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" stroke-linejoin="round"/>
                                    <path d="M14 3v5h5" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="font-semibold">Choose a document</div>
                            <p class="text-xs text-gray-500 mt-1">Pick one from the list to set its options and generate the PDF.</p>
                        </div>
                    @else
                        <h3 class="font-bold">{{ $document['name'] }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5 mb-4">{{ $document['blurb'] }}</p>

                        <form method="GET" action="{{ route('admin.documents.render', $key) }}" target="_blank" class="space-y-4">
                            {{-- Per-record documents need the record chosen. --}}
                            @if($document['scope'] === 'record')
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">
                                        {{ ucfirst(str_replace('_', ' ', $document['subject'])) }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="record" required class="form-select">
                                        <option value="">Select…</option>
                                        @forelse($options as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @empty
                                            <option value="" disabled>No records available</option>
                                        @endforelse
                                    </select>
                                    @if($options->count() >= 200)
                                        <p class="text-[11px] text-gray-400 mt-1">Showing the 200 most recent.</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Period documents need a month and year. --}}
                            @if($document['scope'] === 'period')
                                @if($key === 'daily_attendance')
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Date</label>
                                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-input" />
                                    </div>
                                @else
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Month</label>
                                            <select name="month" class="form-select">
                                                @foreach(range(1, 12) as $m)
                                                    <option value="{{ $m }}" @selected($m === (int) now()->month)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Year</label>
                                            <select name="year" class="form-select">
                                                @foreach(range((int) now()->year, (int) now()->year - 6) as $y)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- Form 16 and the leave card are per-record but also need a year. --}}
                            @if(in_array($key, ['form16', 'leave_card'], true))
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">
                                        {{ $key === 'form16' ? 'Financial year starting' : 'Calendar year' }}
                                    </label>
                                    <select name="year" class="form-select">
                                        @foreach(range((int) now()->year, (int) now()->year - 6) as $y)
                                            <option value="{{ $y }}">{{ $key === 'form16' ? $y.'-'.substr((string) ($y + 1), 2) : $y }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Date-range documents. --}}
                            @if(in_array($key, ['customer_statement', 'vendor_statement', 'stock_ledger', 'report_expense_claims'], true))
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">From</label>
                                        <input type="date" name="from" value="{{ now()->startOfYear()->toDateString() }}" class="form-input" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">To</label>
                                        <input type="date" name="to" value="{{ now()->toDateString() }}" class="form-input" />
                                    </div>
                                </div>
                            @endif

                            {{-- Helpdesk tickets carry their own department enum, which is
                                 not the employee Department table below. --}}
                            @if(! empty($document['ticket_departments']))
                                @php $allowedDepts = auth('admin')->user()->helpdeskDepartments(); @endphp
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Ticket department</label>
                                    <select name="ticket_department" class="form-select">
                                        @if($allowedDepts === [])
                                            <option value="">All departments</option>
                                        @endif
                                        @foreach(\App\Models\InternalTicket::DEPARTMENTS as $slug => $label)
                                            @if($allowedDepts === [] || in_array($slug, $allowedDepts, true))
                                                <option value="{{ $slug }}">{{ $label }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @if($allowedDepts !== [])
                                        <p class="text-[11px] text-gray-400 mt-1">You are limited to the departments listed.</p>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Month</label>
                                        <select name="month" class="form-select">
                                            <option value="">All months</option>
                                            @foreach(range(1, 12) as $m)
                                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Year</label>
                                        <select name="year" class="form-select">
                                            <option value="">All years</option>
                                            @foreach(range((int) now()->year, (int) now()->year - 6) as $y)
                                                <option value="{{ $y }}">{{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">On date</label>
                                    <input type="date" name="date" class="form-input" />
                                    <p class="text-[11px] text-gray-400 mt-1">Tickets raised on this day. Overrides month and year.</p>
                                </div>
                            @endif

                            {{-- Department narrowing, where it makes sense. --}}
                            @if(in_array($document['scope'], ['period', 'filter'], true) && empty($document['ticket_departments']))
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                                    <select name="department_id" class="form-select">
                                        <option value="">All departments</option>
                                        @foreach($departments as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Watermark</label>
                                <select name="watermark" class="form-select">
                                    <option value="">None</option>
                                    @foreach(['DRAFT', 'PAID', 'CANCELLED', 'DUPLICATE'] as $wm)
                                        <option value="{{ $wm }}" @selected(($document['watermark'] ?? null) === $wm)>{{ ucfirst(strtolower($wm)) }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Printed diagonally across every page.</p>
                            </div>

                            @php
                                // A document may need a further right before it can be
                                // taken out of the system at all.
                                $canExport = empty($document['export_permission'])
                                    || auth('admin')->user()->can($document['export_permission']);
                            @endphp
                            <div class="pt-2 border-t border-gray-100 dark:border-[#1b2e4b] space-y-2">
                                @if($canExport)
                                    <button type="submit" name="stream" value="1" class="btn btn-primary w-full">Preview in Browser</button>
                                    <button type="submit" class="btn btn-outline-primary w-full">Download PDF</button>
                                    @if(! empty($document['excel']))
                                        {{-- Same rows, same filters, without the letterhead. --}}
                                        <button type="submit" name="format" value="xlsx" class="btn btn-outline-success w-full">Download Excel (.xlsx)</button>
                                    @endif
                                @else
                                    <div class="p-3 rounded-lg bg-warning/10 text-warning text-[11px] font-semibold">
                                        You can see this report but not download it. Ask an administrator for the
                                        <b>Helpdesk Reports · export</b> permission.
                                    </div>
                                @endif
                            </div>
                        </form>

                        @unless($business?->signature_path)
                            <div class="mt-4 p-3 rounded-lg bg-warning/10 text-warning text-[11px] font-semibold">
                                No authorised signature uploaded yet — letters and vouchers will print with a blank
                                signature line.
                                @can('documents.configure')
                                    <a href="{{ route('admin.documents.letterhead') }}" class="underline">Upload one</a>.
                                @endcan
                            </div>
                        @endunless
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-layout.admin>
