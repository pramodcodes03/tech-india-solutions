<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
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
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();

        return Lead::create($data);
    }

    /**
     * Update an existing lead.
     */
    public function update(Lead $lead, array $data): Lead
    {
        $data['updated_by'] = Auth::guard('admin')->id();
        $lead->update($data);

        return $lead->refresh();
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
