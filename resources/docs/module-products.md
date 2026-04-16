# Product Management

## Purpose

The Product module maintains the master catalog of all goods sold and purchased by the company. Products are referenced in quotations, sales orders, purchase orders, invoices, and inventory movements.

## Categories

Products are organized into categories via the `product_categories` table. Categories can be created and managed separately and assigned to products.

- Navigate to **Categories** to manage the category list
- Each category has a name, description, and active/inactive status
- A category can be toggled on/off without deleting

## Product Fields

| Field | Type | Rules |
|-------|------|-------|
| Code | text | Auto-generated product code |
| Name | text | Required, max 255 |
| Category | select | Required, from product_categories |
| HSN Code | text | HSN/SAC code for GST classification |
| Unit | text | Unit of measure (pcs, kg, m, etc.) |
| Purchase Price | decimal | Cost/buying price |
| Selling Price | decimal | Default selling price (used in quotations) |
| MRP | decimal | Maximum retail price |
| Tax Percent | decimal | GST rate (e.g., 5, 12, 18, 28) |
| Reorder Level | integer | Low stock alert threshold |
| Description | textarea | Detailed product description |
| Image | file | Product image upload (stored in public storage) |
| Status | toggle | active / inactive |

## Image Upload

Product images are uploaded via the create/edit form and stored in `storage/app/public/products/`. The `php artisan storage:link` command must be run to make images accessible via the web.

Supported formats: JPEG, PNG, GIF, WebP. Maximum file size is determined by PHP's `upload_max_filesize` setting.

## Current Stock Accessor

The `Product` model has a computed `current_stock` attribute that calculates available stock in real-time:

```php
public function getCurrentStockAttribute(): float
{
    return (float) $this->stockMovements()
        ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('in', 'adjustment') THEN quantity ELSE -quantity END), 0) as total")
        ->value('total');
}
```

This sums all `in` and `adjustment` movements and subtracts `out` movements across all warehouses.

## Low Stock Alerts

Products whose `current_stock` falls below their `reorder_level` appear in the Inventory Low Stock report. This helps procurement teams know when to reorder.

## Permissions

| Permission | Description |
|-----------|-------------|
| `products.view` | View product list and details |
| `products.create` | Create products |
| `products.edit` | Edit products |
| `products.delete` | Delete products (soft delete) |

| Permission | Description |
|-----------|-------------|
| `categories.view` | View categories |
| `categories.create` | Create categories |
| `categories.edit` | Edit categories |
| `categories.delete` | Delete categories |
