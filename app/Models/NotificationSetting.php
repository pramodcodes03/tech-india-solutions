<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'event_key', 'is_enabled',
        'extra_recipients', 'recipient_overrides', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'extra_recipients' => 'array',
            'recipient_overrides' => 'array',
        ];
    }
}
