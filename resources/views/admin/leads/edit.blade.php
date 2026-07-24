<x-layout.admin title="Edit Lead">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Leads','url'=>route('admin.leads.index')],['label'=>'Edit Lead']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Lead</h5>
            <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <div class="panel">
            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.leads.update', $lead->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $lead->name) }}" required />
                    </div>
                    <div>
                        <label for="company">Company</label>
                        <input id="company" name="company" type="text" class="form-input" value="{{ old('company', $lead->company) }}" />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone', $lead->phone) }}" />
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $lead->email) }}" />
                    </div>
                    <x-admin.india-location :city="$lead->city" :state="$lead->state" />
                    <div>
                        <label for="bid_number">Bid Number</label>
                        <input id="bid_number" name="bid_number" type="text" class="form-input" value="{{ old('bid_number', $lead->bid_number) }}" />
                    </div>
                    <div>
                        <label for="ra_emd">RA/EMD</label>
                        <input id="ra_emd" name="ra_emd" type="text" class="form-input" value="{{ old('ra_emd', $lead->ra_emd) }}" />
                    </div>
                    <div>
                        <label for="source">Source <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="source" :options="$sources" :selected="$lead->source" placeholder="-- Select Source --" required />
                    </div>
                    <div>
                        <label for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="form-select">
                            <option value="">-- Select Product --</option>
                            @foreach($products as $p)<option value="{{ $p->id }}" {{ old('product_id', $lead->product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="lead_date">Lead Received Date</label>
                        <input id="lead_date" name="lead_date" type="date" class="form-input" value="{{ old('lead_date', optional($lead->lead_date)->format('Y-m-d')) }}" />
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach(\App\Models\Lead::STATUSES as $v => $l)
                                <option value="{{ $v }}" {{ old('status', $lead->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to">Assigned To</label>
                        <x-admin.searchable-select name="assigned_to" :options="$admins" :selected="$lead->assigned_to" placeholder="-- Select Admin --" />
                    </div>
                    <div>
                        <label for="expected_value">Expected Value</label>
                        <input id="expected_value" name="expected_value" type="number" step="0.01" class="form-input" value="{{ old('expected_value', $lead->expected_value) }}" />
                    </div>
                    <div>
                        <label for="next_follow_up_at">Next Follow-up Date</label>
                        <input id="next_follow_up_at" name="next_follow_up_at" type="date" class="form-input" value="{{ old('next_follow_up_at', $lead->next_follow_up_at?->format('Y-m-d')) }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3">{{ old('notes', $lead->notes) }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Lead</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
