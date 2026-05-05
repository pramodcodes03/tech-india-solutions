@props(['employee' => null, 'departments', 'designations', 'shifts', 'managers'])

@php $emp = $employee; @endphp

<div class="space-y-5">

    <div class="panel p-5">
        <h3 class="font-bold mb-4 text-lg border-b border-gray-200 dark:border-gray-700 pb-2">Personal Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">
                    Employee Code @if($emp) <span class="text-danger">*</span> @endif
                </label>
                <input type="text" name="employee_code"
                    value="{{ old('employee_code', $emp?->employee_code) }}"
                    {{ $emp ? 'required' : '' }}
                    pattern="[A-Za-z0-9\-_]+"
                    class="form-input mt-1 font-mono"
                    placeholder="{{ $emp ? '' : 'Auto-generate if left blank' }}" />
                <p class="text-[11px] text-gray-500 mt-1">
                    @if($emp)
                        Editable. Must be unique within this business.
                    @else
                        Leave blank to auto-generate. Letters, digits, dashes, underscores only.
                    @endif
                </p>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $emp?->first_name) }}" required class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $emp?->last_name) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Official Email <span class="text-danger">*</span></label>
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
                <label class="text-xs font-semibold text-gray-500 uppercase">Joining Date <span class="text-danger">*</span></label>
                <input type="date" name="joining_date" value="{{ old('joining_date', $emp?->joining_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Probation End</label>
                <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $emp?->probation_end_date?->format('Y-m-d')) }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Employment Type <span class="text-danger">*</span></label>
                <select name="employment_type" required class="form-select mt-1">
                    @foreach(['full_time','part_time','contract','intern'] as $t)
                        <option value="{{ $t }}" @selected(old('employment_type', $emp?->employment_type ?? 'full_time') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Work Mode <span class="text-danger">*</span></label>
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
            <x-admin.india-location :state="$emp?->state" :city="$emp?->city" />
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Pincode</label><input type="text" name="pincode" value="{{ old('pincode', $emp?->pincode) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Country</label><input type="text" name="country" value="{{ old('country', $emp?->country ?? 'India') }}" class="form-input mt-1" /></div>
        </div>
    </div>

    @php
        // Bank-detail field locking:
        // - On CREATE (no $emp), HR fills everything freely.
        // - On EDIT, account_number + IFSC are read-only for HR (only Admin /
        //   Super Admin can edit directly). HR uses the "Request Edit" flow.
        $currentAdmin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        $isAdminApprover = $currentAdmin && (
            $currentAdmin->isSuperAdmin()
            || $currentAdmin->hasAnyRole(['Admin', 'Business Admin'])
        );
        $bankLocked = $emp && ! $isAdminApprover;
        $pendingBankRequest = $emp
            ? \App\Models\BankDetailEditRequest::where('employee_id', $emp->id)
                ->where('status', 'pending')->latest()->first()
            : null;
    @endphp

    <div class="panel p-5">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
            <h3 class="font-bold text-lg">Bank & Statutory</h3>
            @if($bankLocked)
                <span class="text-xs text-gray-500 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12V8a4 4 0 00-8 0v4 M5 12h14v9H5z"/></svg>
                    Account &amp; IFSC are locked &mdash; use <strong>Request Edit</strong> below
                </span>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="text-xs font-semibold text-gray-500 uppercase">PAN</label><input type="text" name="pan_number" value="{{ old('pan_number', $emp?->pan_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Aadhar</label><input type="text" name="aadhar_number" value="{{ old('aadhar_number', $emp?->aadhar_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">UAN</label><input type="text" name="uan_number" value="{{ old('uan_number', $emp?->uan_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">PF Number</label><input type="text" name="pf_number" value="{{ old('pf_number', $emp?->pf_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">ESI Number</label><input type="text" name="esi_number" value="{{ old('esi_number', $emp?->esi_number) }}" class="form-input mt-1" /></div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Bank Name</label><input type="text" name="bank_name" value="{{ old('bank_name', $emp?->bank_name) }}" class="form-input mt-1" /></div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Account #</label>
                <input type="text" name="bank_account_number"
                       value="{{ old('bank_account_number', $emp?->bank_account_number) }}"
                       class="form-input mt-1 {{ $bankLocked ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : '' }}"
                       {{ $bankLocked ? 'readonly' : '' }} />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">IFSC</label>
                <input type="text" name="bank_ifsc"
                       value="{{ old('bank_ifsc', $emp?->bank_ifsc) }}"
                       class="form-input mt-1 {{ $bankLocked ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : '' }}"
                       {{ $bankLocked ? 'readonly' : '' }} />
            </div>
            <div><label class="text-xs font-semibold text-gray-500 uppercase">Branch</label><input type="text" name="bank_branch" value="{{ old('bank_branch', $emp?->bank_branch) }}" class="form-input mt-1" /></div>
        </div>

        {{-- Bank-edit request UI. Inputs use HTML5 form="bank-edit-request-form"
             so they belong to the OUTSIDE form rendered in edit.blade.php after
             the parent employee-update form. This avoids invalid nested forms. --}}
        @if($emp && $bankLocked)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                 x-data="{ open: false }">
                @if($pendingBankRequest)
                    <div class="bg-warning/10 text-warning border border-warning/30 rounded p-3 text-sm">
                        <strong>Pending request:</strong> A bank-detail change request is awaiting Admin approval (submitted {{ $pendingBankRequest->created_at->diffForHumans() }}). You can submit a new request only after the current one is approved or rejected.
                    </div>
                @else
                    <button type="button" class="btn btn-outline-warning btn-sm" @click="open = !open">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Request Edit (Account / IFSC / Bank)
                    </button>
                    <div x-show="open" x-cloak class="mt-4 bg-blue-50 dark:bg-blue-900/10 border-l-4 border-primary p-4 rounded">
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                            Enter the new value(s) you want to apply. Leave a field blank to keep its current value. Admin / Super Admin will review and approve before changes take effect.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500">New Account #</label>
                                <input type="text" form="bank-edit-request-form" name="requested_account_number"
                                       class="form-input form-input-sm" placeholder="Current: {{ $emp->bank_account_number ?: '—' }}">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">New IFSC</label>
                                <input type="text" form="bank-edit-request-form" name="requested_ifsc"
                                       class="form-input form-input-sm" placeholder="Current: {{ $emp->bank_ifsc ?: '—' }}">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">New Bank Name (optional)</label>
                                <input type="text" form="bank-edit-request-form" name="requested_bank_name"
                                       class="form-input form-input-sm" placeholder="Current: {{ $emp->bank_name ?: '—' }}">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">New Branch (optional)</label>
                                <input type="text" form="bank-edit-request-form" name="requested_bank_branch"
                                       class="form-input form-input-sm" placeholder="Current: {{ $emp->bank_branch ?: '—' }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Reason for change <span class="text-danger">*</span></label>
                                <textarea form="bank-edit-request-form" name="reason" required minlength="10" rows="2"
                                          class="form-input form-input-sm" placeholder="e.g. Employee changed to a new bank — provided new passbook copy"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="open = false">Cancel</button>
                            <button type="submit" form="bank-edit-request-form" class="btn btn-warning btn-sm">Submit Request</button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
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
