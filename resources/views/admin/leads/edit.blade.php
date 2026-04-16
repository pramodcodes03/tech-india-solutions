<x-layout.admin>
    <div>
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
                    <div>
                        <label for="source">Source <span class="text-danger">*</span></label>
                        <select id="source" name="source" class="form-select" required>
                            <option value="">-- Select Source --</option>
                            @foreach($sources as $source)
                                <option value="{{ $source }}" {{ old('source', $lead->source) === $source ? 'selected' : '' }}>{{ ucfirst($source) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="new" {{ old('status', $lead->status) === 'new' ? 'selected' : '' }}>New</option>
                            <option value="contacted" {{ old('status', $lead->status) === 'contacted' ? 'selected' : '' }}>Contacted</option>
                            <option value="qualified" {{ old('status', $lead->status) === 'qualified' ? 'selected' : '' }}>Qualified</option>
                            <option value="proposal" {{ old('status', $lead->status) === 'proposal' ? 'selected' : '' }}>Proposal</option>
                            <option value="won" {{ old('status', $lead->status) === 'won' ? 'selected' : '' }}>Won</option>
                            <option value="lost" {{ old('status', $lead->status) === 'lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to">Assigned To</label>
                        <select id="assigned_to" name="assigned_to" class="form-select">
                            <option value="">-- Select Admin --</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ old('assigned_to', $lead->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="expected_value">Expected Value</label>
                        <input id="expected_value" name="expected_value" type="number" step="0.01" class="form-input" value="{{ old('expected_value', $lead->expected_value) }}" />
                    </div>
                    <div>
                        <label for="next_follow_up">Next Follow-up Date</label>
                        <input id="next_follow_up" name="next_follow_up" type="date" class="form-input" value="{{ old('next_follow_up', $lead->next_follow_up?->format('Y-m-d')) }}" />
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
