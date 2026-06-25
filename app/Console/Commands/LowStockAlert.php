<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LowStockAlert extends Command
{
    protected $signature = 'stock:low-stock-alert';

    protected $description = 'Find products with stock at or below reorder level and log an alert';

    public function handle()
    {
        // current_stock is a computed accessor (sum of stock movements), not a
        // DB column, so it can't be used in a WHERE/whereColumn. The scheduler
        // also has no active business, so drop the tenant scope and scan every
        // business's products, then filter in PHP by the accessor.
        $lowStockProducts = Product::withoutGlobalScope(\App\Support\Tenancy\BusinessScope::class)
            ->where('reorder_level', '>', 0)
            ->get()
            ->filter(fn ($product) => $product->current_stock <= $product->reorder_level)
            ->values();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low-stock products found.');

            return Command::SUCCESS;
        }

        $rows = $lowStockProducts->map(function ($product) {
            return [
                'ID' => $product->id,
                'Code' => $product->code,
                'Name' => $product->name,
                'Current Stock' => $product->current_stock,
                'Reorder Level' => $product->reorder_level,
            ];
        });

        Log::warning('Low stock alert: '.$lowStockProducts->count().' product(s) at or below reorder level.', $rows->toArray());

        $this->table(['ID', 'Code', 'Name', 'Current Stock', 'Reorder Level'], $rows->toArray());
        $this->warn("Found {$lowStockProducts->count()} product(s) with low stock.");

        return Command::SUCCESS;
    }
}
