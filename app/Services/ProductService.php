<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Generate the next product code in PRD-0001 format.
     */
    public function generateCode(): string
    {
        $prefix = 'PRD-';
        $last = Product::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = $last ? (int) substr($last->code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new product, handling image upload.
     */
    public function create(array $data): Product
    {
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::guard('admin')->id();

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('products', 'public');
        }

        return Product::create($data);
    }

    /**
     * Update an existing product, handling image upload/replace.
     */
    public function update(Product $product, array $data): Product
    {
        $data['updated_by'] = Auth::guard('admin')->id();

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image if it exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $data['image']->store('products', 'public');
        }

        $product->update($data);

        return $product->refresh();
    }

    /**
     * Soft-delete a product.
     */
    public function delete(Product $product): void
    {
        $product->update(['deleted_by' => Auth::guard('admin')->id()]);
        $product->delete();
    }
}
