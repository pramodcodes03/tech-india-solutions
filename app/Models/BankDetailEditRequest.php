<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDetailEditRequest extends Model
{
    use BelongsToBusiness;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'business_id',
        'employee_id', 'requested_by',
        'current_account_number', 'current_ifsc', 'current_bank_name', 'current_bank_branch',
        'requested_account_number', 'requested_ifsc', 'requested_bank_name', 'requested_bank_branch',
        'reason',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Returns the list of fields that this request actually wants changed
     * (where requested_X differs from current_X). Used by the approve view.
     */
    public function changedFields(): array
    {
        $fields = ['account_number', 'ifsc', 'bank_name', 'bank_branch'];
        $changes = [];
        foreach ($fields as $f) {
            $cur = $this->{'current_'.$f};
            $new = $this->{'requested_'.$f};
            if ($new !== null && $new !== '' && $new !== $cur) {
                $changes[$f] = ['from' => $cur, 'to' => $new];
            }
        }
        return $changes;
    }
}
