# Invoice Management

## Purpose

The Invoice module handles billing for sales orders. Invoices track amounts due, payments received, and overdue balances. Professional PDF invoices can be generated and downloaded.

## Invoice Generation

Invoices are typically generated from a Sales Order:

1. Navigate to a confirmed Sales Order
2. Click **Generate Invoice**
3. A new invoice is created with line items, totals, and customer details copied from the sales order
4. Invoice date defaults to today; due date is calculated based on payment terms

Invoices can also be created independently for ad-hoc billing.

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Invoice Number | text | Auto-generated with prefix (e.g., INV-0001) |
| Customer | select | Required |
| Sales Order | reference | Optional link to source order |
| Invoice Date | date | Required |
| Due Date | date | Required |
| Status | select | draft, sent, paid, overdue, cancelled |
| Line Items | repeater | Product, qty, price, tax |
| Subtotal | decimal | Computed |
| Discount | decimal | Flat or percentage |
| Tax Amount | decimal | Computed |
| Grand Total | decimal | Computed |
| Amount Paid | decimal | Sum of linked payments |
| Balance Due | decimal | Grand Total - Amount Paid |
| Terms | textarea | Payment terms |
| Notes | textarea | Internal notes |

## Payment Tracking

The invoice maintains running totals:

- **Amount Paid** is automatically recalculated when payments are added or deleted
- **Balance Due** = Grand Total - Amount Paid
- When Balance Due reaches zero, the invoice status is automatically set to `paid`

## Overdue Management

Invoices past their due date with a remaining balance are flagged as `overdue`. The dashboard displays overdue invoice counts and the total outstanding amount.

## PDF Export

Click **Download PDF** to generate a professional invoice PDF containing:

- Company logo and header (from settings)
- Customer billing and shipping address
- Itemized table with HSN codes, quantities, rates, and amounts
- Subtotal, discount, GST breakdown, and grand total
- Payment terms and bank details
- Footer with terms & conditions

## Permissions

| Permission | Description |
|-----------|-------------|
| `invoices.view` | View invoice list and details |
| `invoices.create` | Create invoices |
| `invoices.edit` | Edit invoices |
| `invoices.delete` | Delete invoices |
| `invoices.export_pdf` | Download invoice as PDF |
