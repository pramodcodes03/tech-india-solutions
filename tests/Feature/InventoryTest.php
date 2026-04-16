<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class InventoryTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $inventoryUser;

    protected Product $product;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->inventoryUser = $this->createAdminWithRole('Inventory', 'inventory@test.com');

        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 100,
            'selling_price' => 150,
            'tax_percent' => 18,
            'reorder_level' => 20,
            'status' => 'active',
            'created_by' => $this->inventoryUser->id,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    #[Test]
    public function test_stock_movement_records_correctly(): void
    {
        // Record an inward movement
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'notes' => 'Initial stock',
            'created_by' => $this->inventoryUser->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 50,
        ]);

        // Check stock level via accessor
        $this->product->refresh();
        $this->assertEquals(50, $this->product->current_stock);

        // Record an outward movement
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'out',
            'quantity' => 15,
            'notes' => 'Sales order',
            'created_by' => $this->inventoryUser->id,
        ]);

        $this->product->refresh();
        $this->assertEquals(35, $this->product->current_stock);
    }

    #[Test]
    public function test_low_stock_products_are_detected(): void
    {
        // Product has reorder_level = 20, add only 10 units of stock
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'notes' => 'Low stock level',
            'created_by' => $this->inventoryUser->id,
        ]);

        $response = $this->actingAs($this->inventoryUser, 'admin')
            ->get(route('admin.inventory.low-stock'));

        $response->assertStatus(200);

        // The product should be in low stock since current_stock (10) <= reorder_level (20)
        $this->product->refresh();
        $this->assertLessThanOrEqual($this->product->reorder_level, $this->product->current_stock);
    }

    #[Test]
    public function test_stock_adjustment_works(): void
    {
        $response = $this->actingAs($this->inventoryUser, 'admin')
            ->post(route('admin.inventory.store-adjustment'), [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 25,
                'notes' => 'Physical count adjustment',
            ]);

        $response->assertRedirect(route('admin.inventory.movements'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'adjustment',
            'quantity' => 25,
        ]);

        $this->product->refresh();
        $this->assertEquals(25, $this->product->current_stock);
    }
}
