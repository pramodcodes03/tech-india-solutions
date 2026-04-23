<x-layout.employee title="My Profile">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Profile</h1>
        <div class="flex gap-2">
            <a href="{{ route('employee.profile.edit') }}" class="btn btn-primary">Edit Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-4 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-center">
            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-3xl font-extrabold">
                {{ strtoupper(substr($employee->first_name, 0, 1).substr($employee->last_name ?? '', 0, 1)) }}
            </div>
            <div class="mt-3 text-xl font-extrabold">{{ $employee->full_name }}</div>
            <div class="text-sm text-gray-500">{{ $employee->employee_code }}</div>
            <div class="mt-2 inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                {{ $employee->designation?->name ?? 'N/A' }}
            </div>
            <div class="mt-2 text-sm text-gray-500">{{ $employee->department?->name }}</div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 text-left space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="truncate ml-2">{{ $employee->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $employee->phone ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Joined</span><span>{{ $employee->joining_date?->format('d M Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Type</span><span>{{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Mode</span><span>{{ ucfirst(str_replace('_', ' ', $employee->work_mode)) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Reporting to</span><span>{{ $employee->reportingManager?->full_name ?? '—' }}</span></div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <h3 class="font-bold mb-3">Personal Information</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><div class="text-xs text-gray-500">Date of Birth</div><div class="font-medium">{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Gender</div><div class="font-medium">{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Marital Status</div><div class="font-medium">{{ $employee->marital_status ? ucfirst($employee->marital_status) : '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Blood Group</div><div class="font-medium">{{ $employee->blood_group ?? '—' }}</div></div>
                    <div class="col-span-2"><div class="text-xs text-gray-500">Current Address</div><div class="font-medium">{{ $employee->current_address ?? '—' }}</div></div>
                    <div class="col-span-2"><div class="text-xs text-gray-500">Permanent Address</div><div class="font-medium">{{ $employee->permanent_address ?? '—' }}</div></div>
                </div>
            </div>

            <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <h3 class="font-bold mb-3">Bank & Statutory</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><div class="text-xs text-gray-500">Bank</div><div class="font-medium">{{ $employee->bank_name ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Account #</div><div class="font-medium">{{ $employee->bank_account_number ? '****'.substr($employee->bank_account_number, -4) : '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">IFSC</div><div class="font-medium">{{ $employee->bank_ifsc ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">PAN</div><div class="font-medium">{{ $employee->pan_number ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">UAN</div><div class="font-medium">{{ $employee->uan_number ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">PF Number</div><div class="font-medium">{{ $employee->pf_number ?? '—' }}</div></div>
                </div>
            </div>

            <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <h3 class="font-bold mb-3">Emergency Contact</h3>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div><div class="text-xs text-gray-500">Name</div><div class="font-medium">{{ $employee->emergency_contact_name ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Relation</div><div class="font-medium">{{ $employee->emergency_contact_relation ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Phone</div><div class="font-medium">{{ $employee->emergency_contact_phone ?? '—' }}</div></div>
                </div>
            </div>

            <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <h3 class="font-bold mb-3">Change Password</h3>
                <form method="POST" action="{{ route('employee.change-password') }}" class="grid grid-cols-3 gap-3">
                    @csrf
                    <input type="password" name="current_password" required class="form-input" placeholder="Current password" />
                    <input type="password" name="password" required minlength="6" class="form-input" placeholder="New password (min 6)" />
                    <input type="password" name="password_confirmation" required class="form-input" placeholder="Confirm" />
                    <div class="col-span-3"><button class="btn btn-sm btn-primary">Update Password</button></div>
                </form>
            </div>
        </div>
    </div>
</x-layout.employee>
