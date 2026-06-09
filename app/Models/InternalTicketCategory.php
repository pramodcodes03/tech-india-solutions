<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class InternalTicketCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'department', 'name', 'tat_hours', 'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }
}
