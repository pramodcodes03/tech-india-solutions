# Vendor & Purchase Management

## Purpose

This module manages suppliers/vendors and the purchase order lifecycle, including partial goods receiving. It enables the procurement team to track orders from placement through delivery.

## Vendor Management

### Vendor Fields

| Field | Type | Rules |
|-------|------|-------|
| Code | text | Auto-generated vendor code |
| Name | text | Required |
| Company | text | Optional |
| GST Number | text | Optional |
| Email | email | Optional |
| Phone | text | Optional |
| Address | text | Full address |
| City, State, Pincode, Country | text | Address components |
| Notes | textarea | Internal notes |
| Status | toggle | active / inactive |

Vendors can be toggled active/inactive. Only active vendors appear in the purchase order creation form.

## Purchase Orders

### Purchase Order Fields

| Field | Type | Rules |
|-------|------|-------|
| PO Number | text | Auto-generated |
| Vendor | select | Required |
| PO Date | date | Required |
| Expected Date | date | Expected delivery date |
| Status | select | draft, confirmed, partial, received, cancelled |
| Line Items | repeater | Product, qty, unit price, tax |
| Subtotal | decimal | Computed |
| Discount | decimal | Flat or percentage |
| Tax Amount | decimal | Computed |
| Grand Total | decimal | Computed |
| Terms, Notes | textarea | Optional |

### Purchase Order Status Flow

```
draft → confirmed → partial → received → cancelled
```

- **draft** — PO created but not yet sent to vendor
- **confirmed** — PO sent/approved, awaiting delivery
- **partial** — Some items received, others pending
- **received** — All items fully received
- **cancelled** — PO cancelled

## Goods Receipts (Partial Receiving)

When goods arrive from a vendor, create a Goods Receipt against the purchase order:

1. Open the purchase order and click **Receive Goods**
2. Enter the quantity received for each line item (can be less than ordered)
3. Submit — stock `in` movements are created for each received item
4. If all items are fully received, PO status changes to `received`
5. If partially received, PO status changes to `partial`

Multiple goods receipts can be created against a single purchase order to handle split deliveries.

## Permissions

| Permission | Description |
|-----------|-------------|
| `vendors.view` | View vendor list |
| `vendors.create` | Create vendors |
| `vendors.edit` | Edit vendors |
| `vendors.delete` | Delete vendors |
| `purchase_orders.view` | View purchase orders |
| `purchase_orders.create` | Create purchase orders |
| `purchase_orders.edit` | Edit purchase orders |
| `purchase_orders.delete` | Delete purchase orders |
| `goods_receipts.view` | View goods receipts |
| `goods_receipts.create` | Receive goods against a PO |
