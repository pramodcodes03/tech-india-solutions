<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A comment on someone's cycle. Private notes never reach the employee. */
class PerformanceFeedback extends Model
{
    use BelongsToBusiness;

    protected $table = 'performance_feedback';

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id',
        'author_admin_id', 'author_employee_id', 'stage', 'body', 'is_private',
    ];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    public function authorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_admin_id');
    }

    public function authorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'author_employee_id');
    }

    public function getAuthorNameAttribute(): string
    {
        return $this->authorEmployee?->full_name
            ?? $this->authorAdmin?->display_name
            ?? 'System';
    }
}
