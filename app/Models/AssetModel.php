<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetModel extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'asset_models';

    protected $fillable = [
        'code', 'name', 'category_id', 'manufacturer', 'model_number',
        'specifications', 'description', 'image',
        'default_depreciation_method', 'default_useful_life_years',
        'default_salvage_percent', 'manufacturer_warranty_months',
        'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'default_salvage_percent' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Asset model was {$event}");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_model_id');
    }
}
