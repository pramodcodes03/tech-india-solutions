<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Create Service Ticket</h5>
            <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.service-tickets.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="customer_id">Customer <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="customer_id" :options="$customers" placeholder="-- Select Customer --" />
                    </div>
                    <div>
                        <label for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="form-select">
                            <option value="">-- Select Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="priority">Priority <span class="text-danger">*</span></label>
                        <select id="priority" name="priority" class="form-select" required>
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to">Assigned To</label>
                        <select id="assigned_to" name="assigned_to" class="form-select">
                            <option value="">-- Select Admin --</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ old('assigned_to') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="open" {{ old('status', 'open') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="assigned" {{ old('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ old('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="description">Issue Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-input" rows="4" required>{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
