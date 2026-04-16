# Routes Reference

## Overview

All routes are defined in `routes/web.php`. The application has two main route groups: public documentation routes and authenticated admin routes.

## Public Routes

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/` | - | Redirects to admin login |
| GET | `/documentation/{page?}` | documentation | DocumentationController@show |

## Admin Authentication

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/admin/login` | admin.login | AuthController@showLoginForm |
| POST | `/admin/signin` | admin.signin | AuthController@signin |
| POST | `/admin/logout` | admin.logout | AuthController@logout |

## Admin Panel (Protected by `auth:admin`)

### Dashboard

| Method | URI | Name |
|--------|-----|------|
| GET | `/admin/dashboard` | admin.dashboard |

### Admin Users

| Method | URI | Name |
|--------|-----|------|
| GET | `/admin/admin-users` | admin.admin-users.index |
| GET | `/admin/admin-users/create` | admin.admin-users.create |
| POST | `/admin/admin-users` | admin.admin-users.store |
| GET | `/admin/admin-users/{id}/edit` | admin.admin-users.edit |
| PUT | `/admin/admin-users/{id}` | admin.admin-users.update |
| DELETE | `/admin/admin-users/{id}` | admin.admin-users.destroy |
| PATCH | `/admin/admin-users/{id}/toggle-status` | admin.admin-users.toggle-status |
| POST | `/admin/change-password` | admin.change-password |

### Roles

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/roles[/{id}]` | admin.roles.* |

### Customers

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/customers[/{id}]` | admin.customers.* |
| PATCH | `/admin/customers/{id}/toggle-status` | admin.customers.toggle-status |

### Leads

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/leads[/{id}]` | admin.leads.* |
| GET | `/admin/leads/kanban` | admin.leads.kanban |
| POST | `/admin/leads/{id}/convert` | admin.leads.convert |
| PATCH | `/admin/leads/{id}/status` | admin.leads.update-status |

### Quotations

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/quotations[/{id}]` | admin.quotations.* |
| GET | `/admin/quotations/{id}/pdf` | admin.quotations.pdf |
| POST | `/admin/quotations/{id}/clone` | admin.quotations.clone |
| POST | `/admin/quotations/{id}/convert` | admin.quotations.convert |

### Sales Orders

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/sales-orders[/{id}]` | admin.sales-orders.* |
| PATCH | `/admin/sales-orders/{id}/status` | admin.sales-orders.update-status |
| POST | `/admin/sales-orders/{id}/invoice` | admin.sales-orders.generate-invoice |

### Products & Categories

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/products[/{id}]` | admin.products.* |
| PATCH | `/admin/products/{id}/toggle-status` | admin.products.toggle-status |
| GET/POST/PUT/DELETE | `/admin/categories[/{id}]` | admin.categories.* |

### Inventory & Warehouses

| Method | URI | Name |
|--------|-----|------|
| GET | `/admin/inventory` | admin.inventory.index |
| GET | `/admin/inventory/movements` | admin.inventory.movements |
| GET | `/admin/inventory/low-stock` | admin.inventory.low-stock |
| GET | `/admin/inventory/adjust` | admin.inventory.adjust |
| POST | `/admin/inventory/adjust` | admin.inventory.store-adjustment |
| GET/POST/PUT/DELETE | `/admin/warehouses[/{id}]` | admin.warehouses.* |

### Vendors & Purchase Orders

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/vendors[/{id}]` | admin.vendors.* |
| GET/POST/PUT/DELETE | `/admin/purchase-orders[/{id}]` | admin.purchase-orders.* |
| POST | `/admin/purchase-orders/{id}/receive` | admin.purchase-orders.receive |

### Invoices & Payments

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/invoices[/{id}]` | admin.invoices.* |
| GET | `/admin/invoices/{id}/pdf` | admin.invoices.pdf |
| GET/POST/DELETE | `/admin/payments[/{id}]` | admin.payments.* |

### Service Tickets

| Method | URI | Name |
|--------|-----|------|
| GET/POST/PUT/DELETE | `/admin/service-tickets[/{id}]` | admin.service-tickets.* |
| POST | `/admin/service-tickets/{id}/comments` | admin.service-tickets.add-comment |

### Reports & Settings

| Method | URI | Name |
|--------|-----|------|
| GET | `/admin/reports` | admin.reports.index |
| GET | `/admin/reports/{type}` | admin.reports.{type} |
| GET | `/admin/reports/{type}/export-excel` | admin.reports.export-excel |
| GET | `/admin/reports/{type}/export-pdf` | admin.reports.export-pdf |
| GET | `/admin/settings` | admin.settings.index |
| PUT | `/admin/settings` | admin.settings.update |
