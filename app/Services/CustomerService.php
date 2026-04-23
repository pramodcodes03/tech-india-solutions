<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class CustomerService
{
    /**
     * Generate the next customer code in CUST-0001 format.
     */
    public function generateCode(): string
    {
        $prefix = 'CUST-';
        $last = Customer::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = $last ? (int) substr($last->code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new customer with an auto-generated code.
     */
    public function create(array $data): Customer
    {
        $data = $this->normalize($data);
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();

        return Customer::create($data);
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        $data = $this->normalize($data);
        $data['updated_by'] = Auth::guard('admin')->id();
        $customer->update($data);

        return $customer->refresh();
    }

    /**
     * Coerce optional-but-NOT-NULL columns to sensible defaults so empty form
     * fields don't blow up at the database layer.
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('credit_limit', $data) && ($data['credit_limit'] === null || $data['credit_limit'] === '')) {
            $data['credit_limit'] = 0;
        }
        if (array_key_exists('country', $data) && ($data['country'] === null || $data['country'] === '')) {
            $data['country'] = 'India';
        }
        if (array_key_exists('status', $data) && ($data['status'] === null || $data['status'] === '')) {
            $data['status'] = 'active';
        }

        return $data;
    }

    /**
     * Soft-delete a customer.
     */
    public function delete(Customer $customer): void
    {
        $customer->update(['deleted_by' => Auth::guard('admin')->id()]);
        $customer->delete();
    }
}
