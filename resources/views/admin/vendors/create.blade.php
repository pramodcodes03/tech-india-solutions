<x-layout.admin title="Add Vendor">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Vendors','url'=>route('admin.vendors.index')],['label'=>'Add Vendor']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Add Vendor</h5>
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.vendors.store') }}" method="POST">
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
                        <label for="gst_number">GST Number</label>
                        <input id="gst_number" name="gst_number" type="text" class="form-input" value="{{ old('gst_number') }}" />
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email') }}" />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone') }}" />
                    </div>
                    <div>
                        <label for="city">City</label>
                        <input id="city" name="city" type="text" class="form-input" value="{{ old('city') }}" />
                    </div>
                    <div>
                        <label for="state">State</label>
                        <input id="state" name="state" type="text" class="form-input" value="{{ old('state') }}" />
                    </div>
                    <div>
                        <label for="pincode">Pincode</label>
                        <input id="pincode" name="pincode" type="text" class="form-input" value="{{ old('pincode') }}" />
                    </div>
                    <div>
                        <label for="country">Country</label>
                        <input id="country" name="country" type="text" class="form-input" value="{{ old('country', 'India') }}" />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-input" rows="3" placeholder="Full address...">{{ old('address') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
