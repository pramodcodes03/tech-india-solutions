<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'employee_id', 'date', 'check_in', 'check_out',
        'hours_worked', 'status', 'source', 'biometric_ref',
        'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_worked' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
