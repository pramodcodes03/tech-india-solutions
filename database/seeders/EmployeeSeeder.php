<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $generalShift = Shift::where('name', 'like', 'General%')->first();
            $morningShift = Shift::where('name', 'like', 'Morning%')->first();
            $eveningShift = Shift::where('name', 'like', 'Evening%')->first();

            $employees = [
                [
                    'employee_code' => 'EMP001',
                    'email' => 'rajesh.kumar@altechnics.com',
                    'personal_email' => 'rajesh.kumar@gmail.com',
                    'first_name' => 'Rajesh',
                    'last_name' => 'Kumar',
                    'phone' => '+91 98765 43210',
                    'whatsapp_number' => '+91 98765 43210',
                    'date_of_birth' => '1985-06-15',
                    'gender' => 'male',
                    'marital_status' => 'married',
                    'blood_group' => 'B+',
                    'current_address' => 'Flat 304, Sai Residency, Andheri East',
                    'permanent_address' => 'H.No. 12, Civil Lines, Lucknow',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'pincode' => '400069',
                    'department_code' => 'ENG',
                    'designation_name' => 'Engineering Manager',
                    'shift_id' => $generalShift?->id,
                    'joining_date' => '2018-04-10',
                    'confirmation_date' => '2018-10-10',
                    'employment_type' => 'full_time',
                    'work_mode' => 'hybrid',
                    'pan_number' => 'AKLPK4521J',
                    'aadhar_number' => '4521 8765 1234',
                    'uan_number' => '100123456789',
                    'pf_number' => 'MH/BAN/0012345/001/0001234',
                    'bank_name' => 'HDFC Bank',
                    'bank_account_number' => '50100123456789',
                    'bank_ifsc' => 'HDFC0001234',
                    'bank_branch' => 'Andheri East',
                    'emergency_contact_name' => 'Sunita Kumar',
                    'emergency_contact_relation' => 'Wife',
                    'emergency_contact_phone' => '+91 98765 43211',
                    'bgv_status' => 'cleared',
                    'bgv_completed_at' => '2018-04-25',
                    'status' => 'active',
                ],
                [
                    'employee_code' => 'EMP002',
                    'email' => 'priya.sharma@altechnics.com',
                    'personal_email' => 'priya.sharma@gmail.com',
                    'first_name' => 'Priya',
                    'last_name' => 'Sharma',
                    'phone' => '+91 99876 54321',
                    'whatsapp_number' => '+91 99876 54321',
                    'date_of_birth' => '1990-11-22',
                    'gender' => 'female',
                    'marital_status' => 'single',
                    'blood_group' => 'O+',
                    'current_address' => '21, Green Park Extension',
                    'permanent_address' => '21, Green Park Extension',
                    'city' => 'New Delhi',
                    'state' => 'Delhi',
                    'pincode' => '110016',
                    'department_code' => 'HR',
                    'designation_name' => 'HR Manager',
                    'shift_id' => $generalShift?->id,
                    'joining_date' => '2020-07-01',
                    'confirmation_date' => '2021-01-01',
                    'employment_type' => 'full_time',
                    'work_mode' => 'on_site',
                    'pan_number' => 'BNPPS6789K',
                    'aadhar_number' => '6789 1234 5678',
                    'uan_number' => '100234567890',
                    'pf_number' => 'DL/CPM/0023456/002/0002345',
                    'bank_name' => 'ICICI Bank',
                    'bank_account_number' => '029301234567',
                    'bank_ifsc' => 'ICIC0000293',
                    'bank_branch' => 'Green Park',
                    'emergency_contact_name' => 'Anil Sharma',
                    'emergency_contact_relation' => 'Father',
                    'emergency_contact_phone' => '+91 99876 54320',
                    'bgv_status' => 'cleared',
                    'bgv_completed_at' => '2020-07-20',
                    'status' => 'active',
                ],
                [
                    'employee_code' => 'EMP003',
                    'email' => 'arjun.patel@altechnics.com',
                    'personal_email' => 'arjun.patel@gmail.com',
                    'first_name' => 'Arjun',
                    'last_name' => 'Patel',
                    'phone' => '+91 97654 32109',
                    'whatsapp_number' => '+91 97654 32109',
                    'date_of_birth' => '1993-03-08',
                    'gender' => 'male',
                    'marital_status' => 'single',
                    'blood_group' => 'A+',
                    'current_address' => '7, Satellite Road, Jodhpur Char Rasta',
                    'permanent_address' => '14, Patel Nagar, Surat',
                    'city' => 'Ahmedabad',
                    'state' => 'Gujarat',
                    'pincode' => '380015',
                    'department_code' => 'SAL',
                    'designation_name' => 'Sales Executive',
                    'shift_id' => $generalShift?->id,
                    'joining_date' => '2023-09-15',
                    'probation_end_date' => '2024-03-15',
                    'confirmation_date' => '2024-03-15',
                    'employment_type' => 'full_time',
                    'work_mode' => 'on_site',
                    'pan_number' => 'CDPPP1234L',
                    'aadhar_number' => '1234 5678 9012',
                    'uan_number' => '100345678901',
                    'bank_name' => 'State Bank of India',
                    'bank_account_number' => '38291023456',
                    'bank_ifsc' => 'SBIN0003829',
                    'bank_branch' => 'Satellite',
                    'emergency_contact_name' => 'Rameshbhai Patel',
                    'emergency_contact_relation' => 'Father',
                    'emergency_contact_phone' => '+91 97654 32108',
                    'bgv_status' => 'cleared',
                    'bgv_completed_at' => '2023-10-05',
                    'status' => 'active',
                ],
                [
                    'employee_code' => 'EMP004',
                    'email' => 'sneha.reddy@altechnics.com',
                    'personal_email' => 'sneha.reddy@gmail.com',
                    'first_name' => 'Sneha',
                    'last_name' => 'Reddy',
                    'phone' => '+91 96543 21098',
                    'whatsapp_number' => '+91 96543 21098',
                    'date_of_birth' => '1995-09-12',
                    'gender' => 'female',
                    'marital_status' => 'single',
                    'blood_group' => 'AB+',
                    'current_address' => 'Plot 45, Jubilee Hills, Road No. 36',
                    'permanent_address' => '8-2-293, Banjara Hills',
                    'city' => 'Hyderabad',
                    'state' => 'Telangana',
                    'pincode' => '500033',
                    'department_code' => 'ENG',
                    'designation_name' => 'Software Engineer',
                    'shift_id' => $morningShift?->id,
                    'joining_date' => '2024-08-01',
                    'probation_end_date' => '2025-02-01',
                    'employment_type' => 'full_time',
                    'work_mode' => 'remote',
                    'pan_number' => 'EFGPR9876M',
                    'aadhar_number' => '9876 5432 1098',
                    'uan_number' => '100456789012',
                    'bank_name' => 'Axis Bank',
                    'bank_account_number' => '912010012345678',
                    'bank_ifsc' => 'UTIB0001234',
                    'bank_branch' => 'Jubilee Hills',
                    'emergency_contact_name' => 'Lakshmi Reddy',
                    'emergency_contact_relation' => 'Mother',
                    'emergency_contact_phone' => '+91 96543 21099',
                    'bgv_status' => 'in_progress',
                    'status' => 'probation',
                ],
                [
                    'employee_code' => 'EMP005',
                    'email' => 'vikram.singh@altechnics.com',
                    'personal_email' => 'vikram.singh@gmail.com',
                    'first_name' => 'Vikram',
                    'last_name' => 'Singh',
                    'phone' => '+91 95432 10987',
                    'whatsapp_number' => '+91 95432 10987',
                    'date_of_birth' => '1988-01-30',
                    'gender' => 'male',
                    'marital_status' => 'married',
                    'blood_group' => 'B-',
                    'current_address' => '12/3, Indiranagar, 100 Feet Road',
                    'permanent_address' => 'Village Khushalpura, Tehsil Jaipur',
                    'city' => 'Bengaluru',
                    'state' => 'Karnataka',
                    'pincode' => '560038',
                    'department_code' => 'SUP',
                    'designation_name' => 'Support Lead',
                    'shift_id' => $eveningShift?->id,
                    'joining_date' => '2019-11-20',
                    'confirmation_date' => '2020-05-20',
                    'employment_type' => 'full_time',
                    'work_mode' => 'on_site',
                    'pan_number' => 'GHIPS5432N',
                    'aadhar_number' => '5432 1098 7654',
                    'uan_number' => '100567890123',
                    'pf_number' => 'KA/BNG/0034567/003/0003456',
                    'bank_name' => 'Kotak Mahindra Bank',
                    'bank_account_number' => '7234567890',
                    'bank_ifsc' => 'KKBK0000456',
                    'bank_branch' => 'Indiranagar',
                    'emergency_contact_name' => 'Pooja Singh',
                    'emergency_contact_relation' => 'Wife',
                    'emergency_contact_phone' => '+91 95432 10986',
                    'bgv_status' => 'cleared',
                    'bgv_completed_at' => '2019-12-10',
                    'status' => 'active',
                ],
            ];

            // When seeding multiple businesses with the same demo dataset,
            // employee emails would collide (email is globally unique). Suffix
            // with the active business's slug so each business gets unique emails.
            $businessSlug = app(\App\Support\Tenancy\CurrentBusiness::class)->get()?->slug;

            foreach ($employees as $data) {
                $deptCode = $data['department_code'];
                $designationName = $data['designation_name'];
                unset($data['department_code'], $data['designation_name']);

                $department = Department::where('code', $deptCode)->first();
                $designation = $department
                    ? Designation::where('name', $designationName)->where('department_id', $department->id)->first()
                    : null;

                $data['department_id'] = $department?->id;
                $data['designation_id'] = $designation?->id;
                $data['country'] = 'India';
                $data['password'] = Hash::make('Employee@12345');

                if ($businessSlug && $businessSlug !== 'altechnics') {
                    $data['email'] = str_replace('@altechnics.com', '@'.$businessSlug.'.test', $data['email']);
                }

                Employee::firstOrCreate(
                    ['employee_code' => $data['employee_code']],
                    $data
                );
            }

            // Set Rajesh (EMP001) as the reporting manager for engineering subordinates
            $manager = Employee::where('employee_code', 'EMP001')->first();
            if ($manager) {
                Employee::where('employee_code', 'EMP004')->update(['reporting_manager_id' => $manager->id]);
            }
        });
    }
}
