<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocument extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'employee_id', 'doc_type', 'title',
        'file_path', 'file_mime', 'file_size',
        'expires_on', 'uploaded_by', 'employee_uploaded_by',
        'verification_status', 'verified_by', 'verified_at', 'verification_remarks',
    ];

    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public const DOC_TYPES = [
        'aadhar' => 'Aadhaar',
        'pan' => 'PAN Card',
        'degree' => 'Educational Certificate',
        'offer_letter' => 'Offer Letter',
        'experience' => 'Experience Letter',
        'resume' => 'Resume / CV',
        'bank' => 'Bank Proof',
        'other' => 'Other',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(EmployeeDocumentVerification::class)->latest();
    }

    public function getDocTypeLabelAttribute(): string
    {
        return self::DOC_TYPES[$this->doc_type] ?? ucfirst($this->doc_type);
    }
}
