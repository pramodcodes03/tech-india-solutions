<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToBusiness;

    protected $table = 'attendance';

    protected $fillable = [
        'business_id',
        'employee_id', 'date', 'check_in', 'check_out',
        'hours_worked', 'status', 'source', 'biometric_ref',
        'remarks', 'created_by',
        'shift', 'start_time',
        'late_hours', 'early_hours', 'over_time',
        'in_temp', 'out_temp', 'card_no',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_worked' => 'decimal:2',
            'in_temp' => 'decimal:2',
            'out_temp' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
