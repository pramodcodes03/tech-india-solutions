# Roles & Permissions

## Purpose

The Roles & Permissions module manages access control across the entire ERP. It uses Spatie Laravel Permission to assign granular permissions to roles, which are then assigned to users.

## How Permissions Work

Permissions follow a `module.action` naming convention. For example, `customers.create` grants the ability to create new customers. Each role is a named collection of permissions.

### Permission Check Flow

1. A user logs in and their role is loaded
2. On each request, middleware or Blade directives check `@can('module.action')`
3. Super Admin bypasses all checks via `Gate::before` returning `true`
4. Other roles are checked against their explicitly assigned permissions

## Default Roles

The system ships with 7 predefined roles:

| Role | Description |
|------|-------------|
| **Super Admin** | Full access to everything. Bypasses all permission checks. |
| **Admin** | All permissions except `users.delete` and `roles.delete` |
| **Sales** | Customers, leads, quotations, sales orders + read-only invoices/payments |
| **Inventory** | Products, categories, inventory, warehouses, purchase orders, goods receipts |
| **Accounts** | Invoices, payments, read-only customers, reports, settings view |
| **Service** | Service tickets + read-only customers and products |
| **Viewer** | Read-only access to all modules (only `*.view` permissions) |

## Creating Custom Roles

Navigate to **Roles > Create** and:

1. Enter a role name
2. Select individual permissions from the grouped checkbox list
3. Save the role
4. Assign it to users via User Management

## Permission Modules

Permissions are organized into these modules:

- `dashboard` — view
- `users` — view, create, edit, delete
- `roles` — view, create, edit, delete
- `customers` — view, create, edit, delete
- `leads` — view, create, edit, delete, convert
- `quotations` — view, create, edit, delete, export_pdf
- `sales_orders` — view, create, edit, delete
- `products` — view, create, edit, delete
- `categories` — view, create, edit, delete
- `inventory` — view, create, adjust
- `warehouses` — view, create, edit, delete
- `vendors` — view, create, edit, delete
- `purchase_orders` — view, create, edit, delete
- `goods_receipts` — view, create
- `invoices` — view, create, edit, delete, export_pdf
- `payments` — view, create, delete
- `service_tickets` — view, create, edit, delete
- `reports` — view, export
- `settings` — view, edit

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/roles` | List roles |
| GET | `/admin/roles/create` | Create form |
| POST | `/admin/roles` | Store role |
| GET | `/admin/roles/{id}/edit` | Edit form |
| PUT | `/admin/roles/{id}` | Update role |
| DELETE | `/admin/roles/{id}` | Delete role |
