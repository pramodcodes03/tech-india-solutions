# Customer Management

## Purpose

The Customer Management module maintains a centralized database of all business customers. Customers are linked to quotations, sales orders, invoices, payments, and service tickets, forming the backbone of the ERP's transactional data.

## Screens

- **Customer List** — Searchable, paginated table with filters for status
- **Create Customer** — Form with all customer fields
- **Edit Customer** — Modify existing customer details
- **Customer Detail** — View customer info with related quotations, orders, invoices, and payments
- **Toggle Status** — Activate or deactivate a customer

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Code | text | Auto-generated, read-only |
| Name | text | Required, max 255 |
| Company | text | Optional |
| GST Number | text | Optional, validated format |
| Email | email | Optional |
| Phone | text | Optional |
| Billing Address | textarea | Optional |
| Shipping Address | textarea | Optional |
| City | text | Optional |
| State | text | Optional |
| Pincode | text | Optional |
| Country | text | Defaults to India |
| Credit Limit | decimal | Optional, defaults to 0.00 |
| Notes | textarea | Internal notes |
| Status | toggle | active / inactive |

## Customer Code Auto-Generation

Customer codes are automatically generated when a new customer is created. The format follows a sequential pattern (e.g., `CUST-0001`, `CUST-0002`). The code is read-only and cannot be changed after creation.

## Customer History

The customer detail page displays a complete history of all transactions:

- **Quotations** sent to this customer
- **Sales Orders** placed by this customer
- **Invoices** generated for this customer
- **Payments** received from this customer
- **Service Tickets** raised for this customer

## Credit Limit

Each customer has an optional credit limit field. This value is stored for reference and can be used by business logic to warn when a customer's outstanding balance approaches their limit.

## Permissions

| Permission | Description |
|-----------|-------------|
| `customers.view` | View customer list and details |
| `customers.create` | Create new customers |
| `customers.edit` | Edit existing customers |
| `customers.delete` | Delete customers (soft delete) |

## Related Modules

Customers created here appear as selectable options in Quotations, Sales Orders, Invoices, and Service Tickets. Customers can also be auto-created from Lead conversion.
