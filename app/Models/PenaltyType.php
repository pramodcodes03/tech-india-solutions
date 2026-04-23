<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenaltyType extends Model
{
    protected $fillable = ['name', 'description', 'default_amount', 'status'];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
        ];
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }
}
