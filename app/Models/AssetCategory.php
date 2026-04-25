<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetCategory extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'default_depreciation_method',
        'default_useful_life_years', 'default_salvage_percent',
        'description', 'status',
        'created_by', 'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Asset category was {$event}");
    }

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class, 'category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
