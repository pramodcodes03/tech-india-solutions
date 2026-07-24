<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImportLog extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'type', 'file_name', 'total_rows', 'imported', 'failed', 'errors', 'admin_id',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
