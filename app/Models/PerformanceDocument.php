<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evidence attached to a KRA during self-assessment. */
class PerformanceDocument extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id', 'employee_kra_id',
        'file_path', 'original_name', 'mime_type', 'size_bytes',
        'uploaded_by_employee_id', 'uploaded_by_admin_id',
    ];

    public function employeeKra(): BelongsTo
    {
        return $this->belongsTo(EmployeeKra::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->size_bytes;

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
