<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class VendorService
{
    /**
     * Generate the next vendor code in VEN-0001 format.
     */
    public function generateCode(): string
    {
        $prefix = 'VEN-';
        $last = Vendor::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = $last ? (int) substr($last->code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new vendor with an auto-generated code.
     */
    public function create(array $data): Vendor
    {
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();

        return Vendor::create($data);
    }

    /**
     * Update an existing vendor.
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        $data['updated_by'] = Auth::guard('admin')->id();
        $vendor->update($data);

        return $vendor->refresh();
    }

    /**
     * Soft-delete a vendor.
     */
    public function delete(Vendor $vendor): void
    {
        $vendor->update(['deleted_by' => Auth::guard('admin')->id()]);
        $vendor->delete();
    }
}
