# Inventory Management

## Purpose

The Inventory module provides real-time visibility into stock levels across multiple warehouses. It tracks every stock movement with full traceability back to the source document (sales order, purchase order, or manual adjustment).

## Warehouses

The system supports multiple warehouses. Each warehouse has:

| Field | Type | Description |
|-------|------|-------------|
| Code | text | Warehouse code (e.g., WH-001) |
| Name | text | Warehouse name |
| Address | text | Physical location |
| Is Default | boolean | Default warehouse for new movements |
| Is Active | boolean | Active/inactive toggle |

One warehouse is marked as default. New stock movements default to this warehouse unless overridden.

## Stock Movements

Every inventory change is recorded as a stock movement with full audit trail:

| Field | Description |
|-------|-------------|
| Product | Which product moved |
| Warehouse | Which warehouse |
| Type | `in` (stock added), `out` (stock removed), `adjustment` (correction) |
| Quantity | Amount moved |
| Reference | Polymorphic link to source (SalesOrder, PurchaseOrder, GoodsReceipt, or null for manual) |
| Notes | Reason for the movement |
| Created By | Which admin performed it |

### Movement Sources

- **Sales Order Confirmation** — Creates `out` movements for each line item
- **Goods Receipt** — Creates `in` movements when receiving purchase order goods
- **Manual Adjustment** — Admin manually adjusts stock with a reason

## Low Stock Alerts

The **Low Stock** screen (`/admin/inventory/low-stock`) lists all products where current stock is below the product's `reorder_level`. This view helps the procurement team identify items that need reordering.

## Adjustment Workflow

To manually adjust stock:

1. Navigate to **Inventory > Adjust Stock**
2. Select the product and warehouse
3. Choose movement type (`in` or `adjustment`)
4. Enter quantity and reason
5. Submit — a new stock movement record is created

All adjustments are logged in the activity log for audit purposes.

## Inventory Reports

The inventory report (`/admin/reports/inventory`) provides:

- Current stock levels per product per warehouse
- Stock movement history with date filters
- Low stock summary
- Export to Excel

## Permissions

| Permission | Description |
|-----------|-------------|
| `inventory.view` | View stock levels and movements |
| `inventory.create` | Create stock movements |
| `inventory.adjust` | Perform manual stock adjustments |
| `warehouses.view` | View warehouses |
| `warehouses.create` | Create warehouses |
| `warehouses.edit` | Edit warehouses |
| `warehouses.delete` | Delete warehouses |
