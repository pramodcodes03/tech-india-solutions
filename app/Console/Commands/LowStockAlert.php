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
        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'reorder_level')->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low-stock products found.');

            return Command::SUCCESS;
        }

        $rows = $lowStockProducts->map(function ($product) {
            return [
                'ID' => $product->id,
                'Code' => $product->product_code,
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
