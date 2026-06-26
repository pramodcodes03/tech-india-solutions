<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Candidate extends Model
{
    use BelongsToBusiness, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_id', 'candidate_code',
        'first_name', 'last_name', 'email', 'phone', 'current_location',
        'total_experience', 'current_ctc', 'expected_ctc', 'notice_period_days', 'resume_path',
        'source', 'referred_by_employee_id', 'batch_id',
        'department_id', 'designation_id',
        'stage_id', 'status', 'applied_at', 'stage_changed_at',
        'hired_at', 'rejected_at', 'rejection_reason',
        'offer_ctc', 'offer_designation', 'offer_date', 'proposed_joining_date', 'offer_generated_at',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_experience' => 'decimal:1',
            'current_ctc' => 'decimal:2',
            'expected_ctc' => 'decimal:2',
            'offer_ctc' => 'decimal:2',
            'applied_at' => 'date',
            'offer_date' => 'date',
            'proposed_joining_date' => 'date',
            'stage_changed_at' => 'datetime',
            'hired_at' => 'datetime',
            'rejected_at' => 'datetime',
            'offer_generated_at' => 'datetime',
        ];
    }

    public const SOURCES = [
        'walkin' => 'Walk-in',
        'referral' => 'Referral',
        'campus' => 'Campus',
        'online' => 'Online / Portal',
        'agency' => 'Agency',
        'other' => 'Other',
        // Recruitment sources
        'job_board' => 'Job Board',
        'placement' => 'Placement',
        'skill_india' => 'Skill India',
        'computer_center' => 'Computer Center',
        'internal' => 'Internal',
        'iti' => 'ITI',
        'grow_center' => 'Grow Center',
        'dc_office' => 'DC Office',
        'employee_referral' => 'Employee Referral',
        'referred_ex_employee' => 'Referred by Ex-Employee & Known Person',
        'apna_app' => 'Apna App',
        'indeed' => 'Indeed',
        'workindia' => 'WorkIndia',
        'db_whatsapp_group' => 'Referred by DB WhatsApp Group',
        'instagram' => 'Instagram',
        'employment_exchange' => 'Employment Exchange',
        'job_camp' => 'Job Camp',
        'facebook' => 'Facebook',
        'rejoining' => 'Rejoining',
        'satyam_skill_center' => 'Satyam Skill Center',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Candidate was {$event}");
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst($this->source);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(RecruitmentStage::class, 'stage_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RecruitmentBatch::class, 'batch_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'referred_by_employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(CandidateStageHistory::class)->latest();
    }
}
