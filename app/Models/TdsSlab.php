<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class TdsSlab extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'financial_year', 'lower', 'upper', 'rate_percent', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'lower' => 'decimal:2',
            'upper' => 'decimal:2',
            'rate_percent' => 'decimal:2',
        ];
    }
}
