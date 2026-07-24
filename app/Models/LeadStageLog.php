<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStageLog extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'lead_id', 'from_status', 'to_status',
        'remarks', 'duration_seconds', 'changed_by',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }

    public function getDurationHumanAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }
        $d = (int) $this->duration_seconds;
        if ($d < 3600) {
            return round($d / 60).'m';
        }
        if ($d < 86400) {
            return round($d / 3600, 1).'h';
        }

        return round($d / 86400, 1).'d';
    }
}
