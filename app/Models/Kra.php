<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * KRA master — a Key Result Area template. Assigning it to an employee for a
 * cycle creates an EmployeeKra; this row is never scored directly.
 */
class Kra extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'code', 'name', 'description',
        'department_id', 'designation_id', 'weightage', 'review_frequency',
        'manager_id', 'status', 'start_date', 'end_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weightage' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "KRA was {$e}");
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class);
    }

    public function activeKpis(): HasMany
    {
        return $this->hasMany(Kpi::class)->where('status', 'active')->orderBy('name');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function employeeKras(): HasMany
    {
        return $this->hasMany(EmployeeKra::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * KRAs that apply to a given employee: those with no department /
     * designation restriction, plus those matching theirs.
     */
    public function scopeForEmployee(Builder $query, Employee $employee): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $employee->department_id))
            ->where(fn ($q) => $q->whereNull('designation_id')->orWhere('designation_id', $employee->designation_id));
    }

    /** Next code in the KRA-0001 series, per business. */
    public static function nextCode(?int $businessId = null): string
    {
        $businessId ??= app(CurrentBusiness::class)->id();

        $last = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('code', 'like', 'KRA-%')
            ->orderByDesc('code')
            ->value('code');

        return sprintf('KRA-%04d', $last ? ((int) substr($last, 4)) + 1 : 1);
    }

    /** "Sales · Manager" — who this template is aimed at. */
    public function getScopeLabelAttribute(): string
    {
        $parts = array_filter([$this->department?->name, $this->designation?->name]);

        return $parts ? implode(' · ', $parts) : 'All departments';
    }
}
