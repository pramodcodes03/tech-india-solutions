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
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();

        return Customer::create($data);
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        $data['updated_by'] = Auth::guard('admin')->id();
        $customer->update($data);

        return $customer->refresh();
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
