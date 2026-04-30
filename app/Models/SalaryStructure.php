<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'employee_id', 'effective_from', 'effective_to',
        'basic', 'hra', 'conveyance', 'medical', 'special', 'other_allowance',
        'gross_monthly', 'ctc_annual',
        'pf_percent', 'esi_percent', 'professional_tax', 'monthly_tds',
        'is_current', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
            'basic' => 'decimal:2',
            'hra' => 'decimal:2',
            'conveyance' => 'decimal:2',
            'medical' => 'decimal:2',
            'special' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'gross_monthly' => 'decimal:2',
            'ctc_annual' => 'decimal:2',
            'pf_percent' => 'decimal:2',
            'esi_percent' => 'decimal:2',
            'professional_tax' => 'decimal:2',
            'monthly_tds' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
