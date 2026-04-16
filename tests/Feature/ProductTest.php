<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class ProductTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $inventoryUser;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->inventoryUser = $this->createAdminWithRole('Inventory', 'inventory@test.com');

        $this->category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function test_inventory_user_can_view_products(): void
    {
        $response = $this->actingAs($this->inventoryUser, 'admin')
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_inventory_user_can_create_product(): void
    {
        $response = $this->actingAs($this->inventoryUser, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'New Product',
                'category_id' => $this->category->id,
                'unit' => 'pcs',
                'purchase_price' => 100,
                'selling_price' => 150,
                'tax_percent' => 18,
                'reorder_level' => 10,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
        ]);
    }

    #[Test]
    public function test_product_code_is_auto_generated(): void
    {
        $this->actingAs($this->inventoryUser, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Auto Code Product',
                'category_id' => $this->category->id,
                'unit' => 'kg',
                'purchase_price' => 50,
                'selling_price' => 80,
                'tax_percent' => 12,
                'status' => 'active',
            ]);

        $product = Product::where('name', 'Auto Code Product')->first();

        $this->assertNotNull($product);
        $this->assertMatchesRegularExpression('/^PRD-\d{4}$/', $product->code);
    }

    #[Test]
    public function test_product_with_image_upload(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->inventoryUser, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Product With Image',
                'category_id' => $this->category->id,
                'unit' => 'pcs',
                'purchase_price' => 100,
                'selling_price' => 150,
                'tax_percent' => 18,
                'status' => 'active',
                'image' => UploadedFile::fake()->image('product.jpg', 400, 400),
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Product With Image')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }
}
