<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\PenaltyType;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDepartments();
        $this->seedDesignations();
        $this->seedShifts();
        $this->seedLeaveTypes();
        $this->seedHolidays();
        $this->seedPenaltyTypes();
    }

    private function seedDepartments(): void
    {
        $departments = [
            ['code' => 'HR', 'name' => 'Human Resources'],
            ['code' => 'ENG', 'name' => 'Engineering'],
            ['code' => 'SAL', 'name' => 'Sales'],
            ['code' => 'MKT', 'name' => 'Marketing'],
            ['code' => 'FIN', 'name' => 'Finance & Accounts'],
            ['code' => 'OPS', 'name' => 'Operations'],
            ['code' => 'SUP', 'name' => 'Support'],
        ];

        foreach ($departments as $d) {
            Department::firstOrCreate(['code' => $d['code']], $d + ['status' => 'active']);
        }
    }

    private function seedDesignations(): void
    {
        $map = [
            'HR' => [['Executive', 'Junior'], ['HR Manager', 'Senior'], ['HR Director', 'Lead']],
            'ENG' => [['Software Engineer', 'Junior'], ['Senior Engineer', 'Senior'], ['Tech Lead', 'Lead'], ['Engineering Manager', 'Lead']],
            'SAL' => [['Sales Executive', 'Junior'], ['Sales Manager', 'Senior'], ['Sales Director', 'Lead']],
            'MKT' => [['Marketing Executive', 'Junior'], ['Marketing Manager', 'Senior']],
            'FIN' => [['Accountant', 'Junior'], ['Finance Manager', 'Senior'], ['CFO', 'Lead']],
            'OPS' => [['Operations Executive', 'Junior'], ['Operations Manager', 'Senior']],
            'SUP' => [['Support Executive', 'Junior'], ['Support Lead', 'Senior']],
        ];

        foreach ($map as $deptCode => $list) {
            $dept = Department::where('code', $deptCode)->first();
            if (! $dept) {
                continue;
            }
            foreach ($list as [$name, $level]) {
                Designation::firstOrCreate(
                    ['name' => $name, 'department_id' => $dept->id],
                    ['level' => $level, 'status' => 'active']
                );
            }
        }
    }

    private function seedShifts(): void
    {
        $shifts = [
            ['name' => 'General (9:30 – 6:30)', 'start_time' => '09:30:00', 'end_time' => '18:30:00', 'grace_minutes' => 10],
            ['name' => 'Morning (7:00 – 3:00)', 'start_time' => '07:00:00', 'end_time' => '15:00:00', 'grace_minutes' => 10],
            ['name' => 'Evening (2:00 – 10:00)', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'grace_minutes' => 10],
            ['name' => 'Night (10:00 – 6:00)', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'grace_minutes' => 10],
        ];

        foreach ($shifts as $s) {
            Shift::firstOrCreate(['name' => $s['name']], $s + ['half_day_after_minutes' => 120, 'status' => 'active']);
        }
    }

    private function seedLeaveTypes(): void
    {
        $types = [
            ['code' => 'CL', 'name' => 'Casual Leave', 'annual_quota' => 12, 'is_paid' => true, 'carry_forward' => false, 'color' => '#3b82f6'],
            ['code' => 'SL', 'name' => 'Sick Leave', 'annual_quota' => 10, 'is_paid' => true, 'carry_forward' => true, 'max_carry_forward' => 10, 'color' => '#ef4444'],
            ['code' => 'PL', 'name' => 'Privilege Leave', 'annual_quota' => 15, 'is_paid' => true, 'carry_forward' => true, 'max_carry_forward' => 30, 'encashable' => true, 'color' => '#10b981'],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'annual_quota' => 180, 'is_paid' => true, 'color' => '#ec4899'],
            ['code' => 'PTL', 'name' => 'Paternity Leave', 'annual_quota' => 15, 'is_paid' => true, 'color' => '#8b5cf6'],
            ['code' => 'COMP', 'name' => 'Compensatory Off', 'annual_quota' => 0, 'is_paid' => true, 'color' => '#f59e0b'],
            ['code' => 'LWP', 'name' => 'Leave Without Pay', 'annual_quota' => 0, 'is_paid' => false, 'color' => '#6b7280'],
        ];

        foreach ($types as $t) {
            LeaveType::firstOrCreate(['code' => $t['code']], array_merge([
                'max_carry_forward' => 0, 'encashable' => false, 'carry_forward' => false,
                'status' => 'active',
            ], $t));
        }
    }

    private function seedHolidays(): void
    {
        $year = (int) date('Y');
        $holidays = [
            ['name' => 'Republic Day', 'date' => "$year-01-26"],
            ['name' => 'Holi', 'date' => "$year-03-14"],
            ['name' => 'Good Friday', 'date' => "$year-04-03"],
            ['name' => 'May Day', 'date' => "$year-05-01"],
            ['name' => 'Independence Day', 'date' => "$year-08-15"],
            ['name' => 'Gandhi Jayanti', 'date' => "$year-10-02"],
            ['name' => 'Dussehra', 'date' => "$year-10-12"],
            ['name' => 'Diwali', 'date' => "$year-11-01"],
            ['name' => 'Christmas', 'date' => "$year-12-25"],
        ];

        foreach ($holidays as $h) {
            Holiday::firstOrCreate(['date' => $h['date']], $h + ['type' => 'public']);
        }
    }

    private function seedPenaltyTypes(): void
    {
        $types = [
            ['name' => 'ID Card not worn', 'default_amount' => 100],
            ['name' => 'Mobile phone on work floor', 'default_amount' => 200],
            ['name' => 'Late arrival (beyond grace)', 'default_amount' => 100],
            ['name' => 'Absent without leave', 'default_amount' => 500],
            ['name' => 'Dress code violation', 'default_amount' => 150],
            ['name' => 'Misconduct', 'default_amount' => 1000],
        ];

        foreach ($types as $t) {
            PenaltyType::firstOrCreate(['name' => $t['name']], $t + ['status' => 'active']);
        }
    }
}
