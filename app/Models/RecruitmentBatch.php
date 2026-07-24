<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentBatch extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'institution', 'drive_date',
        'coordinator', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'drive_date' => 'date',
            'status' => 'boolean',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'batch_id');
    }
}
