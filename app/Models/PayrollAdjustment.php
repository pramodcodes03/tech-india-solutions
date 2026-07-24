<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'employee_id', 'month', 'year',
        'component', 'amount', 'note', 'payslip_id', 'applied', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied' => 'boolean',
        ];
    }

    public const COMPONENTS = [
        'incentive' => 'Incentive / Variable Pay',
        'arrears' => 'Arrears',
        'bonus' => 'Bonus',
        'extra_deduction' => 'Extra Deduction',
    ];

    /** Components that add to earnings (vs. deductions). */
    public const EARNINGS = ['incentive', 'arrears', 'bonus'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isEarning(): bool
    {
        return in_array($this->component, self::EARNINGS);
    }
}
