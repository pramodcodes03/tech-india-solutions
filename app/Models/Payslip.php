<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payslip_code', 'employee_id', 'month', 'year',
        'period_start', 'period_end',
        'working_days', 'paid_days', 'lop_days',
        'basic', 'hra', 'conveyance', 'medical', 'special',
        'other_allowance', 'bonus', 'gross_earnings',
        'pf', 'esi', 'professional_tax', 'tds',
        'penalty_deduction', 'lop_deduction', 'other_deductions', 'total_deductions',
        'net_pay',
        'status', 'paid_on', 'payment_reference', 'notes', 'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_on' => 'date',
            'paid_days' => 'decimal:1',
            'lop_days' => 'decimal:1',
            'basic' => 'decimal:2',
            'hra' => 'decimal:2',
            'conveyance' => 'decimal:2',
            'medical' => 'decimal:2',
            'special' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'bonus' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'pf' => 'decimal:2',
            'esi' => 'decimal:2',
            'professional_tax' => 'decimal:2',
            'tds' => 'decimal:2',
            'penalty_deduction' => 'decimal:2',
            'lop_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }
}
