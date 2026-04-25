@props(['employee' => null, 'departments', 'designations', 'shifts', 'managers'])

@php $emp = $employee; @endphp

<div class="space-y-5">

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Personal Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $emp?->first_name) }}" required class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $emp?->last_name) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Official Email *</label>
                <input type="email" name="email" value="{{ old('email', $emp?->email) }}" required class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Personal Email</label>
                <input type="email" name="personal_email" value="{{ old('personal_email', $emp?->personal_email) }}" class="form-input mt-1" />
            </div>
            @php
                $mobileVal = old('phone', $emp?->phone);
                $waVal = old('whatsapp_number', $emp?->whatsapp_number);
                $waSame = old('whatsapp_same_as_mobile', $emp ? ($waVal !== null && $waVal === $mobileVal ? '1' : '0') : '1');
            @endphp
            <div x-data="{ same: {{ $waSame ? 'true' : 'false' }} }">
                <label class="text-xs font-semibold text-gray-500 uppercase">Mobile</label>
                <input type="text" name="phone" value="{{ $mobileVal }}" class="form-input mt-1" />
                <label class="inline-flex items-center gap-2 mt-2 text-xs text-gray-600 dark:text-gray-300">
                    <input type="checkbox" class="form-checkbox" x-model="same" />
                    WhatsApp number is same as Mobile
                </label>
                <input type="hidden" name="whatsapp_same_as_mobile" :value="same ? 1 : 0" />
                <div x-show="!same" x-cloak class="mt-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" value="{{ $waVal }}" :disabled="same" class="form-input mt-1" />
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Alt Mobile</label>
                <input type="text" name="alt_phone" value="{{ old('alt_phone', $emp?->alt_phone) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $emp?->date_of_birth?->format('Y-m-d')) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Gender</label>
                <select name="gender" class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach(['male','female','other'] as $g)
                        <option value="{{ $g }}" @selected(old('gender', $emp?->gender) === $g)>{{ ucfirst($g) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Marital Status</label>
                <select name="marital_status" class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach(['single','married','divorced','widowed'] as $m)
                        <option value="{{ $m }}" @selected(old('marital_status', $emp?->marital_status) === $m)>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Blood Group</label>
                <input type="text" name="blood_group" value="{{ old('blood_group', $emp?->blood_group) }}" maxlength="5" class="form-input mt-1" />
            </div>
        </div>
    </div>

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Employment</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Department</label>
                <select name="department_id" class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(old('department_id', $emp?->department_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Designation</label>
                <select name="designation_id" class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach($designations as $d)
                        <option value="{{ $d->id }}" @selected(old('designation_id', $emp?->designation_id) == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Shift</label>
                <select name="shift_id" class="form-select mt-1">
                    <option value="">Select</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" @selected(old('shift_id', $emp?->shift_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Reporting Manager</label>
                <select name="reporting_manager_id" class="form-select mt-1">
                    <option value="">None</option>
                    @foreach($managers as $m)
                        <option value="{{ $m->id }}" @selected(old('reporting_manager_id', $emp?->reporting_manager_id) == $m->id)>{{ $m->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Joining Date *</label>
                <input type="date" name="joining_date" value="{{ old('joining_date', $emp?->joining_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Probation End</label>
                <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $emp?->probation_end_date?->format('Y-m-d')) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Employment Type *</label>
                <select name="employment_type" required class="form-select mt-1">
                    @foreach(['full_time','part_time','contract','intern'] as $t)
                        <option value="{{ $t }}" @selected(old('employment_type', $emp?->employment_type ?? 'full_time') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Work Mode *</label>
                <select name="work_mode" required class="form-select mt-1">
                    @foreach(['on_site','remote','hybrid'] as $t)
                        <option value="{{ $t }}" @selected(old('work_mode', $emp?->work_mode ?? 'on_site') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Status</label>
                <select name="status" class="form-select mt-1">
                    @foreach(['active','probation','on_notice','terminated','resigned','absconded','inactive'] as $t)
                        <option value="{{ $t }}" @selected(old('status', $emp?->status ?? 'probation') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Address</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">Current Address</label><textarea name="current_address" rows="2" class="form-input mt-1">{{ old('current_address', $emp?->current_address) }}</textarea></div>
            <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">Permanent Address</label><textarea name="permanent_address" rows="2" class="form-input mt-1">{{ old('permanent_address', $emp?->permanent_address) }}</textarea></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">City</label><input type="text" name="city" value="{{ old('city', $emp?->city) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">State</label><input type="text" name="state" value="{{ old('state', $emp?->state) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Pincode</label><input type="text" name="pincode" value="{{ old('pincode', $emp?->pincode) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Country</label><input type="text" name="country" value="{{ old('country', $emp?->country ?? 'India') }}" class="form-input mt-1" /></div>
        </div>
    </div>

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Bank & Statutory</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="text-xs font-semibold text-gray-500 uppercase">PAN</label><input type="text" name="pan_number" value="{{ old('pan_number', $emp?->pan_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Aadhar</label><input type="text" name="aadhar_number" value="{{ old('aadhar_number', $emp?->aadhar_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">UAN</label><input type="text" name="uan_number" value="{{ old('uan_number', $emp?->uan_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">PF Number</label><input type="text" name="pf_number" value="{{ old('pf_number', $emp?->pf_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">ESI Number</label><input type="text" name="esi_number" value="{{ old('esi_number', $emp?->esi_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name', $emp?->bank_name) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Account #</label><input type="text" name="bank_account_number" value="{{ old('bank_account_number', $emp?->bank_account_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">IFSC</label><input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $emp?->bank_ifsc) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Branch</label><input type="text" name="bank_branch" value="{{ old('bank_branch', $emp?->bank_branch) }}" class="form-input mt-1" /></div>
        </div>
    </div>

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Emergency Contact & BGV</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="text-xs font-semibold text-gray-500 uppercase">EC Name</label><input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $emp?->emergency_contact_name) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">EC Relation</label><input type="text" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $emp?->emergency_contact_relation) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">EC Phone</label><input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $emp?->emergency_contact_phone) }}" class="form-input mt-1" /></div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">BGV Status</label>
                <select name="bgv_status" class="form-select mt-1">
                    @foreach(['pending','in_progress','cleared','failed'] as $t)
                        <option value="{{ $t }}" @selected(old('bgv_status', $emp?->bgv_status ?? 'pending') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2"><label class="text-xs font-semibold text-gray-500 uppercase">BGV Notes</label><input type="text" name="bgv_notes" value="{{ old('bgv_notes', $emp?->bgv_notes) }}" class="form-input mt-1" /></div>
        </div>
    </div>

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Login Credentials</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">{{ $emp ? 'Reset Password' : 'Password' }} (optional)</label>
                <input type="password" name="password" minlength="6" class="form-input mt-1" placeholder="{{ $emp ? 'Leave blank to keep unchanged' : 'Leave blank to use employee code as password' }}" />
                <p class="text-[11px] text-gray-400 mt-1">If left blank{{ $emp ? ', password won\'t change' : ', the employee code will be used as password (e.g. EMP-0001)' }}. Employees can change password from their portal.</p>
            </div>
        </div>
    </div>
</div>
