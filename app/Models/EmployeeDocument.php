<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'employee_id', 'doc_type', 'title',
        'file_path', 'file_mime', 'file_size',
        'expires_on', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }
}
