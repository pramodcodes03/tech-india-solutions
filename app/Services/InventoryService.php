<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * Record a stock movement (in, out, or adjustment).
     */
    public function recordMovement(array $data): StockMovement
    {
        $data['created_by'] = $data['created_by'] ?? Auth::guard('admin')->id();

        return StockMovement::create($data);
    }

    /**
     * Get the current stock level for a product, optionally filtered by warehouse.
     */
    public function getProductStock(int $productId, ?int $warehouseId = null): float
    {
        $query = StockMovement::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->selectRaw(
            "COALESCE(SUM(CASE WHEN type IN ('in', 'adjustment') THEN quantity ELSE -quantity END), 0) as total"
        )->value('total');
    }

    /**
     * Get products where current stock is at or below the reorder level.
     */
    public function getLowStockProducts(): Collection
    {
        return Product::whereHas('stockMovements')
            ->get()
            ->filter(function (Product $product) {
                return $product->current_stock <= $product->reorder_level;
            })
            ->values();
    }

    /**
     * Adjust stock for a product at a specific warehouse.
     */
    public function adjustStock(int $productId, int $warehouseId, float $quantity, string $notes): StockMovement
    {
        return $this->recordMovement([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => 'adjustment',
            'quantity' => $quantity,
            'notes' => $notes,
            'created_by' => Auth::guard('admin')->id(),
        ]);
    }
}
