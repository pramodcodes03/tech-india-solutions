<x-layout.employee title="Edit Profile">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Edit Profile</h1>
        <a href="{{ route('employee.profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>

    {{-- ── Profile Photo Upload ─────────────────────────────────────────── --}}
    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-4" x-data="{ preview: null }">
        <h3 class="font-bold mb-4">Profile Photo</h3>
        <div class="flex items-center gap-5">
            {{-- Current / preview avatar --}}
            <div class="relative shrink-0">
                <div x-show="!preview">
                    <x-employee-avatar :employee="$employee" size="w-20 h-20" textSize="text-2xl" />
                </div>
                <img x-show="preview" :src="preview" x-cloak
                     class="w-20 h-20 rounded-full object-cover ring-2 ring-primary" alt="Preview" />
            </div>

            <div class="flex-1">
                <form method="POST" action="{{ route('employee.profile.photo.upload') }}"
                      enctype="multipart/form-data" class="flex flex-col gap-2">
                    @csrf
                    <label class="text-xs text-gray-500 font-semibold">Upload new photo</label>
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"
                           class="form-input text-sm"
                           @change="const f=$event.target.files[0]; if(f){ const r=new FileReader(); r.onload=e=>preview=e.target.result; r.readAsDataURL(f); }" />
                    <p class="text-[11px] text-gray-400">JPG, PNG or WEBP · max 2 MB</p>
                    <div class="flex gap-2 mt-1">
                        <button type="submit" class="btn btn-sm btn-primary">Upload Photo</button>
                        @if($employee->profile_photo)
                            <button type="button"
                                onclick="document.getElementById('remove-photo-form').submit()"
                                class="btn btn-sm btn-outline-danger">Remove Photo</button>
                        @endif
                    </div>
                </form>

                @if($employee->profile_photo)
                    <form id="remove-photo-form" method="POST"
                          action="{{ route('employee.profile.photo.remove') }}" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endif
            </div>
        </div>
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

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('employee.profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.employee>
