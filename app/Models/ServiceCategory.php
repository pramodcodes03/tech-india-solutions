<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name', 'slug', 'description', 'icon', 'color',
        'status', 'sort_order', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceCategory $c) {
            if (empty($c->slug)) {
                $c->slug = self::uniqueSlug($c->name);
            }
        });
        static::updating(function (ServiceCategory $c) {
            if ($c->isDirty('name') && ! $c->isDirty('slug')) {
                $c->slug = self::uniqueSlug($c->name, $c->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;
        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
