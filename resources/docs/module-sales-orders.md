# Sales Order Management

## Purpose

Sales Orders represent confirmed customer orders. They are typically created by converting an accepted quotation but can also be created independently. Sales orders drive inventory stock decrements and invoice generation.

## Status Workflow

Sales orders progress through the following statuses:

```
draft → confirmed → processing → shipped → delivered → cancelled
```

### Status Transitions

| From | To | Trigger | Side Effects |
|------|----|---------|-------------|
| draft | confirmed | Manual status update | Stock decremented from inventory |
| confirmed | processing | Manual status update | None |
| processing | shipped | Manual status update | None |
| shipped | delivered | Manual status update | None |
| any | cancelled | Manual status update | Stock restored if previously confirmed |

## Stock Decrement on Confirmation

When a sales order moves from `draft` to `confirmed`:

1. Each line item's quantity is checked against available stock
2. Stock movement records of type `out` are created for each item
3. The product's current stock (computed from stock_movements) is reduced
4. If insufficient stock exists, the confirmation is blocked with an error

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Order Number | text | Auto-generated |
| Quotation | reference | Optional link to source quotation |
| Customer | select | Required |
| Order Date | date | Required |
| Status | select | See workflow above |
| Line Items | repeater | Product, qty, price, tax |
| Subtotal | decimal | Computed |
| Discount | decimal | Flat or percentage |
| Tax Amount | decimal | Computed |
| Grand Total | decimal | Computed |
| Terms | textarea | Optional |
| Notes | textarea | Optional |

## Invoice Generation

From a confirmed or later-stage sales order, click **Generate Invoice** to:

1. Create a new Invoice linked to this sales order
2. Copy customer, line items, and totals
3. Set invoice date to today and compute due date from terms
4. Redirect to the new invoice

A sales order can have multiple invoices (e.g., for partial invoicing).

## Permissions

| Permission | Description |
|-----------|-------------|
| `sales_orders.view` | View sales order list and details |
| `sales_orders.create` | Create sales orders |
| `sales_orders.edit` | Edit sales orders |
| `sales_orders.delete` | Delete sales orders |

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/sales-orders` | List |
| POST | `/admin/sales-orders` | Store |
| PATCH | `/admin/sales-orders/{id}/status` | Update status |
| POST | `/admin/sales-orders/{id}/invoice` | Generate invoice |
