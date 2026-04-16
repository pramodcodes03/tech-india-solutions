# Quotation Management

## Purpose

The Quotation module allows sales staff to create professional price quotes for customers. Quotations can be exported as PDF, cloned for quick re-use, and converted into Sales Orders when accepted.

## Fields

### Header

| Field | Type | Rules |
|-------|------|-------|
| Quotation Number | text | Auto-generated with prefix (e.g., QUO-0001) |
| Customer | select | Required, from customers list |
| Quotation Date | date | Required, defaults to today |
| Valid Until | date | Required, expiry date |
| Status | select | draft, sent, accepted, rejected, expired |
| Terms | textarea | Pre-filled from settings |
| Notes | textarea | Internal notes |

### Line Items

Each quotation has one or more line items:

| Field | Type | Rules |
|-------|------|-------|
| Product | select | Required, from products list |
| Description | text | Auto-filled from product, editable |
| Quantity | decimal | Required, > 0 |
| Unit Price | decimal | Auto-filled from product selling price, editable |
| Tax % | decimal | Auto-filled from product |
| Amount | decimal | Computed: qty x unit_price |

### Auto-Calculation

The quotation totals are computed automatically:

- **Subtotal** = Sum of all line item amounts
- **Discount** = Flat amount or percentage of subtotal
- **Tax Amount** = Tax percent applied to (subtotal - discount)
- **Grand Total** = Subtotal - Discount + Tax Amount

## PDF Export

Click the **Download PDF** button to generate a professional quotation PDF using DomPDF. The PDF includes company header (from settings), customer details, itemized table, totals, and terms & conditions.

## Clone Quotation

The **Clone** action creates a duplicate quotation with:
- A new quotation number
- Today's date
- Status reset to "draft"
- All line items copied

This is useful for creating similar quotes for different customers or updated pricing.

## Convert to Sales Order

When a quotation is accepted, click **Convert to Sales Order** to:

1. Create a new Sales Order linked to this quotation
2. Copy all line items, totals, and customer reference
3. Set the quotation status to "accepted"
4. Redirect to the new Sales Order for review

## Permissions

| Permission | Description |
|-----------|-------------|
| `quotations.view` | View quotation list and details |
| `quotations.create` | Create quotations |
| `quotations.edit` | Edit quotations |
| `quotations.delete` | Delete quotations |
| `quotations.export_pdf` | Download quotation as PDF |
