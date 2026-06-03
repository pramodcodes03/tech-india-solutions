<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Expense extends Model
{
    use BelongsToBusiness, LogsActivity, SoftDeletes;

    public const TYPE_RECURRING = 'recurring';
    public const TYPE_ONE_OFF = 'one_off';

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const FREQ_WEEKLY      = 'weekly';
    public const FREQ_MONTHLY     = 'monthly';
    public const FREQ_QUARTERLY   = 'quarterly';
    public const FREQ_HALF_YEARLY = 'half_yearly';
    public const FREQ_YEARLY      = 'yearly';

    /**
     * Recurrence frequency label map. Key = DB enum value, value = UI label.
     * Order here is the order users see in the dropdown.
     */
    public const RECURRENCES = [
        self::FREQ_WEEKLY      => 'Weekly',
        self::FREQ_MONTHLY     => 'Monthly',
        self::FREQ_QUARTERLY   => 'Quarterly (every 3 months)',
        self::FREQ_HALF_YEARLY => 'Half-Yearly (every 6 months)',
        self::FREQ_YEARLY      => 'Yearly (Annual)',
    ];

    protected $fillable = [
        'business_id', 'expense_code',
        'expense_category_id', 'expense_subcategory_id',
        'type',
        'title', 'description', 'amount',
        'expense_date', 'due_date', 'paid_date',
        'due_day_of_month', 'recurrence_frequency', 'recurring_template_id',
        'last_reminder_sent_at', 'last_reminder_stage',
        'status', 'payment_method', 'payment_reference',
        'attachment',
        'paid_by_admin_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'last_reminder_sent_at' => 'datetime',
            'due_day_of_month' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Expense was {$e}");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseSubcategory::class, 'expense_subcategory_id');
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurring_template_id');
    }

    public function generatedInstances(): HasMany
    {
        return $this->hasMany(self::class, 'recurring_template_id');
    }

    public function paidByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by_admin_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function isRecurring(): bool
    {
        return $this->type === self::TYPE_RECURRING;
    }

    /**
     * Human-readable recurrence label, e.g. "Monthly Recurring",
     * "Weekly Recurring", "Yearly Recurring". Falls back gracefully for
     * legacy rows that don't have a frequency set.
     */
    public function recurrenceLabel(): string
    {
        if (! $this->isRecurring()) {
            return 'One-off';
        }
        $freq = $this->recurrence_frequency ?? self::FREQ_MONTHLY;
        $name = self::RECURRENCES[$freq] ?? ucwords(str_replace('_', ' ', $freq));

        // The map already says "Monthly", "Weekly" etc. — append "Recurring".
        return $name.' Recurring';
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid()
            && $this->due_date
            && $this->due_date->isPast();
    }
}
