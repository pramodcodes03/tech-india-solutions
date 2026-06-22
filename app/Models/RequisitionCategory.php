<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequisitionCategory extends Model
{
    use BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id', 'key', 'label',
        'sort_order', 'is_active', 'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Active categories for the current business as a [key => label] map,
     * for feeding the requisition Category dropdown.
     */
    public static function options(): array
    {
        return static::active()->ordered()->pluck('label', 'key')->all();
    }
}
