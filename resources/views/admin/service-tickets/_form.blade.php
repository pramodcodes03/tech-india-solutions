@props(['ticket' => null, 'customers', 'products', 'categories', 'admins'])

<div class="panel p-6 space-y-5"
     x-data="{
         ticketMode: '{{ old('ticket_mode', $ticket && $ticket->category_id ? 'service' : 'product') }}',
     }">

    {{-- Ticket type toggle --}}
    <div class="p-3 rounded-lg bg-gray-50 dark:bg-dark-light/20 border border-gray-200 dark:border-gray-700">
        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Ticket Type</div>
        <div class="flex gap-2">
            <label class="flex-1 cursor-pointer">
                <input type="radio" x-model="ticketMode" value="product" class="hidden peer" name="ticket_mode" />
                <div class="p-3 rounded-lg border-2 text-center transition"
                     :class="ticketMode === 'product' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'">
                    <div class="text-lg">📦</div>
                    <div class="font-semibold text-sm">Product Issue</div>
                    <div class="text-[11px] text-gray-500">Issue with a sold product</div>
                </div>
            </label>
            <label class="flex-1 cursor-pointer">
                <input type="radio" x-model="ticketMode" value="service" class="hidden peer" name="ticket_mode" />
                <div class="p-3 rounded-lg border-2 text-center transition"
                     :class="ticketMode === 'service' ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'">
                    <div class="text-lg">🛠️</div>
                    <div class="font-semibold text-sm">Service Work</div>
                    <div class="text-[11px] text-gray-500">Electrician, plumber, etc.</div>
                </div>
            </label>
        </div>
    </div>

    {{-- Customer (always) --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label>Customer <span class="text-danger">*</span></label>
            <x-admin.searchable-select name="customer_id" :options="$customers" :selected="$ticket?->customer_id" placeholder="-- Select Customer --" required />
        </div>

        {{-- Product — shown when Product Issue --}}
        <div x-show="ticketMode === 'product'">
            <label>Product <span class="text-danger">*</span></label>
            <x-admin.searchable-select name="product_id" :options="$products" :selected="$ticket?->product_id" placeholder="-- Select Product --" />
        </div>

        {{-- Category — shown when Service Work --}}
        <div x-show="ticketMode === 'service'" x-cloak>
            <label>Service Category <span class="text-danger">*</span></label>
            @php $catOptions = collect($categories)->map(fn($c) => ['id' => $c->id, 'name' => trim(($c->icon ?? '').' '.$c->name)])->values(); @endphp
            <x-admin.searchable-select name="category_id" :options="$catOptions" :selected="$ticket?->category_id" placeholder="-- Select Category --" />
        </div>

        <div>
            <label for="priority">Priority <span class="text-danger">*</span></label>
            <select id="priority" name="priority" class="form-select" required>
                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('priority', $ticket?->priority ?? 'medium') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="assigned_to">Assigned To</label>
            <x-admin.searchable-select name="assigned_to" :options="$admins" :selected="$ticket?->assigned_to" placeholder="-- Select Admin --" />
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" class="form-select">
                @foreach(['open' => 'Open', 'assigned' => 'Assigned', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('status', $ticket?->status ?? 'open') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        {{-- Service-work-specific site fields --}}
        <div x-show="ticketMode === 'service'" x-cloak>
            <label>Site Location</label>
            <input type="text" name="site_location" value="{{ old('site_location', $ticket?->site_location) }}" maxlength="255" class="form-input" placeholder="Address or landmark where work is needed" />
        </div>

        <div x-show="ticketMode === 'service'" x-cloak>
            <label>Scheduled Date/Time</label>
            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $ticket?->scheduled_at?->format('Y-m-d\TH:i')) }}" class="form-input" />
        </div>

        <div x-show="ticketMode === 'service'" x-cloak>
            <label>On-site Contact Name</label>
            <input type="text" name="contact_name" value="{{ old('contact_name', $ticket?->contact_name) }}" maxlength="100" class="form-input" />
        </div>

        <div x-show="ticketMode === 'service'" x-cloak>
            <label>On-site Contact Phone</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $ticket?->contact_phone) }}" maxlength="20" class="form-input" />
        </div>

        <div class="md:col-span-2">
            <label for="issue_description">Issue / Work Description <span class="text-danger">*</span></label>
            <textarea id="issue_description" name="issue_description" class="form-input" rows="4" required
                      placeholder="Describe the problem or the work to be done">{{ old('issue_description', $ticket?->issue_description) }}</textarea>
        </div>

        @if($ticket && $ticket->resolution_notes)
        <div class="md:col-span-2">
            <label for="resolution_notes">Resolution Notes</label>
            <textarea id="resolution_notes" name="resolution_notes" class="form-input" rows="3">{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
        </div>
        @elseif($ticket)
        <div class="md:col-span-2">
            <label for="resolution_notes">Resolution Notes</label>
            <textarea id="resolution_notes" name="resolution_notes" class="form-input" rows="3" placeholder="Fill in when resolving the ticket">{{ old('resolution_notes') }}</textarea>
        </div>
        @endif
    </div>
</div>
