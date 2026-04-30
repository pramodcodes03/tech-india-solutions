<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Employee extends Authenticatable
{
    use BelongsToBusiness, LogsActivity, Notifiable, SoftDeletes;

    protected $guard_name = 'employee';

    protected $fillable = [
        'business_id',
        'employee_code', 'email', 'personal_email', 'password', 'last_login_at',
        'first_name', 'last_name', 'phone', 'alt_phone', 'whatsapp_number',
        'date_of_birth', 'gender', 'marital_status', 'blood_group', 'profile_photo',
        'current_address', 'permanent_address', 'city', 'state', 'pincode', 'country',
        'department_id', 'designation_id', 'shift_id', 'reporting_manager_id',
        'joining_date', 'probation_end_date', 'confirmation_date',
        'resignation_date', 'last_working_date',
        'employment_type', 'work_mode',
        'pan_number', 'aadhar_number', 'pf_number', 'uan_number', 'esi_number',
        'bank_name', 'bank_account_number', 'bank_ifsc', 'bank_branch',
        'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone',
        'bgv_status', 'bgv_completed_at', 'bgv_notes',
        'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'resignation_date' => 'date',
            'last_working_date' => 'date',
            'bgv_completed_at' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['password', 'remember_token'])
            ->setDescriptionForEvent(fn (string $event) => "Employee was {$event}");
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->where('is_current', true);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function feedbackGiven(): HasMany
    {
        return $this->hasMany(DepartmentFeedback::class);
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
