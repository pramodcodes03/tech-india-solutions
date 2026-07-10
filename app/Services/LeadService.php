<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadStageLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    /**
     * Generate the next lead code in LEAD-0001 format.
     */
    public function generateCode(): string
    {
        $prefix = 'LEAD-';
        $last = Lead::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = $last ? (int) substr($last->code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new lead with an auto-generated code.
     */
    public function create(array $data): Lead
    {
        $data = $this->normalize($data);
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();
        $data['lead_date'] = $data['lead_date'] ?? now()->toDateString();
        $data['last_stage_changed_at'] = now();

        $lead = Lead::create($data);

        LeadStageLog::create([
            'business_id' => $lead->business_id,
            'lead_id' => $lead->id,
            'from_status' => null,
            'to_status' => $lead->status,
            'remarks' => 'Lead created',
            'duration_seconds' => 0,
            'changed_by' => Auth::guard('admin')->id(),
        ]);

        return $lead;
    }

    /**
     * Record a stage transition: append a stage log with the time spent in the
     * previous stage and the assigned employee's remark, then move the lead.
     */
    public function changeStatus(Lead $lead, string $newStatus, ?string $remarks = null): Lead
    {
        return DB::transaction(function () use ($lead, $newStatus, $remarks) {
            $from = $lead->status;
            $since = $lead->last_stage_changed_at ?? $lead->created_at;
            $duration = $since ? (int) $since->diffInSeconds(now()) : null;

            LeadStageLog::create([
                'business_id' => $lead->business_id,
                'lead_id' => $lead->id,
                'from_status' => $from,
                'to_status' => $newStatus,
                'remarks' => $remarks,
                'duration_seconds' => $duration,
                'changed_by' => Auth::guard('admin')->id(),
            ]);

            $lead->update([
                'status' => $newStatus,
                'last_stage_changed_at' => now(),
                'updated_by' => Auth::guard('admin')->id(),
            ]);

            return $lead->refresh();
        });
    }

    /**
     * Update an existing lead.
     */
    public function update(Lead $lead, array $data): Lead
    {
        $data = $this->normalize($data);
        $data['updated_by'] = Auth::guard('admin')->id();
        $lead->update($data);

        return $lead->refresh();
    }

    /**
     * Coerce optional-but-NOT-NULL columns to sensible defaults so empty form
     * fields don't blow up at the database layer. Empty strings from HTML forms
     * are normalised to null (when nullable) or to a safe default.
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('expected_value', $data) && ($data['expected_value'] === null || $data['expected_value'] === '')) {
            $data['expected_value'] = 0;
        }
        foreach (['next_follow_up_at', 'assigned_to', 'phone', 'email', 'company', 'notes', 'product_id', 'lead_date', 'city', 'state', 'bid_number', 'ra_emd'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] === '') {
                $data[$k] = null;
            }
        }

        return $data;
    }

    /**
     * Soft-delete a lead.
     */
    public function delete(Lead $lead): void
    {
        $lead->update(['deleted_by' => Auth::guard('admin')->id()]);
        $lead->delete();
    }

    /**
     * Convert a lead to a customer.
     *
     * Wraps the operation in a DB transaction: creates a customer from
     * the lead data, marks the lead as "won", and logs a LeadActivity.
     */
    public function convertToCustomer(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead) {
            $customer = $this->customerService->create([
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'notes' => $lead->notes,
                'status' => 'active',
            ]);

            $lead->update([
                'status' => 'won',
                'updated_by' => Auth::guard('admin')->id(),
            ]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'type' => 'converted',
                'description' => "Lead converted to Customer #{$customer->code}",
                'created_by' => Auth::guard('admin')->id(),
            ]);

            return $customer;
        });
    }
}
