<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryTemplate extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'level', 'department_id', 'employee_category',
        'basic', 'hra', 'conveyance', 'medical', 'special', 'other_allowance',
        'pf_percent', 'esi_percent', 'professional_tax', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2', 'hra' => 'decimal:2', 'conveyance' => 'decimal:2',
            'medical' => 'decimal:2', 'special' => 'decimal:2', 'other_allowance' => 'decimal:2',
            'pf_percent' => 'decimal:2', 'esi_percent' => 'decimal:2', 'professional_tax' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getGrossMonthlyAttribute(): float
    {
        return (float) ($this->basic + $this->hra + $this->conveyance + $this->medical + $this->special + $this->other_allowance);
    }
}
