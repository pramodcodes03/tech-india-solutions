<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentStage extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'slug', 'type', 'sort_order',
        'color', 'is_default', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'stage_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
