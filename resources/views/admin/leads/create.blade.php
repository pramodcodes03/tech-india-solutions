<x-layout.admin title="Add Lead">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Leads','url'=>route('admin.leads.index')],['label'=>'Add Lead']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Add Lead</h5>
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

            <form action="{{ route('admin.leads.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name') }}" required />
                    </div>
                    <div>
                        <label for="company">Company</label>
                        <input id="company" name="company" type="text" class="form-input" value="{{ old('company') }}" />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone') }}" />
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email') }}" />
                    </div>
                    <div>
                        <label for="source">Source <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="source" :options="$sources" placeholder="-- Select Source --" required />
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="new" {{ old('status', 'new') === 'new' ? 'selected' : '' }}>New</option>
                            <option value="contacted" {{ old('status') === 'contacted' ? 'selected' : '' }}>Contacted</option>
                            <option value="qualified" {{ old('status') === 'qualified' ? 'selected' : '' }}>Qualified</option>
                            <option value="proposal" {{ old('status') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                            <option value="won" {{ old('status') === 'won' ? 'selected' : '' }}>Won</option>
                            <option value="lost" {{ old('status') === 'lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to">Assigned To</label>
                        <x-admin.searchable-select name="assigned_to" :options="$admins" placeholder="-- Select Admin --" />
                    </div>
                    <div>
                        <label for="expected_value">Expected Value</label>
                        <input id="expected_value" name="expected_value" type="number" step="0.01" class="form-input" value="{{ old('expected_value') }}" />
                    </div>
                    <div>
                        <label for="next_follow_up">Next Follow-up Date</label>
                        <input id="next_follow_up" name="next_follow_up" type="date" class="form-input" value="{{ old('next_follow_up') }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
