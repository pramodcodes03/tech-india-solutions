<x-layout.employee title="Edit Profile">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Edit Profile</h1>
        <a href="{{ route('employee.profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>

    <form method="POST" action="{{ route('employee.profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-4">Contact Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 font-semibold">Primary Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-input mt-1" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-semibold">Alternate Phone</label>
                    <input type="text" name="alt_phone" value="{{ old('alt_phone', $employee->alt_phone) }}" class="form-input mt-1" />
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 font-semibold">Current Address</label>
                    <textarea name="current_address" rows="2" class="form-input mt-1">{{ old('current_address', $employee->current_address) }}</textarea>
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 font-semibold">Permanent Address</label>
                    <textarea name="permanent_address" rows="2" class="form-input mt-1">{{ old('permanent_address', $employee->permanent_address) }}</textarea>
                </div>
                <x-admin.india-location :state="$employee->state" :city="$employee->city" />
                <div><label class="text-xs text-gray-500 font-semibold">Pincode</label><input type="text" name="pincode" value="{{ old('pincode', $employee->pincode) }}" class="form-input mt-1" /></div>
            </div>
        </div>

        <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-4">Emergency Contact</h3>
            <div class="grid grid-cols-3 gap-4">
                <div><label class="text-xs text-gray-500 font-semibold">Name</label><input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 font-semibold">Relation</label><input type="text" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}" class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 font-semibold">Phone</label><input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="form-input mt-1" /></div>
            </div>
        </div>

        <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-4">Bank Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500 font-semibold">Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 font-semibold">Account Number</label><input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 font-semibold">IFSC</label><input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}" class="form-input mt-1" /></div>
                <div><label class="text-xs text-gray-500 font-semibold">Branch</label><input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch) }}" class="form-input mt-1" /></div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('employee.profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.employee>
