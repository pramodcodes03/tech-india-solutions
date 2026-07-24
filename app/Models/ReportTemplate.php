<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'module', 'columns', 'filters', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'filters' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
