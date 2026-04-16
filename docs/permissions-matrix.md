# Permissions Matrix

## Overview

This table shows which permissions are assigned to each of the 7 default roles. Super Admin bypasses all permission checks via `Gate::before`.

## Full Matrix

| Permission | Super Admin | Admin | Sales | Inventory | Accounts | Service | Viewer |
|-----------|:-----------:|:-----:|:-----:|:---------:|:--------:|:-------:|:------:|
| dashboard.view | * | Y | Y | Y | Y | Y | Y |
| users.view | * | Y | - | - | - | - | Y |
| users.create | * | Y | - | - | - | - | - |
| users.edit | * | Y | - | - | - | - | - |
| users.delete | * | - | - | - | - | - | - |
| roles.view | * | Y | - | - | - | - | Y |
| roles.create | * | Y | - | - | - | - | - |
| roles.edit | * | Y | - | - | - | - | - |
| roles.delete | * | - | - | - | - | - | - |
| customers.view | * | Y | Y | - | Y | Y | Y |
| customers.create | * | Y | Y | - | - | - | - |
| customers.edit | * | Y | Y | - | - | - | - |
| customers.delete | * | Y | Y | - | - | - | - |
| leads.view | * | Y | Y | - | - | - | Y |
| leads.create | * | Y | Y | - | - | - | - |
| leads.edit | * | Y | Y | - | - | - | - |
| leads.delete | * | Y | Y | - | - | - | - |
| leads.convert | * | Y | Y | - | - | - | - |
| quotations.view | * | Y | Y | - | - | - | Y |
| quotations.create | * | Y | Y | - | - | - | - |
| quotations.edit | * | Y | Y | - | - | - | - |
| quotations.delete | * | Y | Y | - | - | - | - |
| quotations.export_pdf | * | Y | Y | - | - | - | - |
| sales_orders.view | * | Y | Y | - | - | - | Y |
| sales_orders.create | * | Y | Y | - | - | - | - |
| sales_orders.edit | * | Y | Y | - | - | - | - |
| sales_orders.delete | * | Y | Y | - | - | - | - |
| products.view | * | Y | - | Y | - | Y | Y |
| products.create | * | Y | - | Y | - | - | - |
| products.edit | * | Y | - | Y | - | - | - |
| products.delete | * | Y | - | Y | - | - | - |
| categories.view | * | Y | - | Y | - | - | Y |
| categories.create | * | Y | - | Y | - | - | - |
| categories.edit | * | Y | - | Y | - | - | - |
| categories.delete | * | Y | - | Y | - | - | - |
| inventory.view | * | Y | - | Y | - | - | Y |
| inventory.create | * | Y | - | Y | - | - | - |
| inventory.adjust | * | Y | - | Y | - | - | - |
| warehouses.view | * | Y | - | Y | - | - | Y |
| warehouses.create | * | Y | - | Y | - | - | - |
| warehouses.edit | * | Y | - | Y | - | - | - |
| warehouses.delete | * | Y | - | Y | - | - | - |
| vendors.view | * | Y | - | Y | - | - | Y |
| vendors.create | * | Y | - | Y | - | - | - |
| vendors.edit | * | Y | - | Y | - | - | - |
| vendors.delete | * | Y | - | Y | - | - | - |
| purchase_orders.view | * | Y | - | Y | - | - | Y |
| purchase_orders.create | * | Y | - | Y | - | - | - |
| purchase_orders.edit | * | Y | - | Y | - | - | - |
| purchase_orders.delete | * | Y | - | Y | - | - | - |
| goods_receipts.view | * | Y | - | Y | - | - | Y |
| goods_receipts.create | * | Y | - | Y | - | - | - |
| invoices.view | * | Y | Y | - | Y | - | Y |
| invoices.create | * | Y | - | - | Y | - | - |
| invoices.edit | * | Y | - | - | Y | - | - |
| invoices.delete | * | Y | - | - | Y | - | - |
| invoices.export_pdf | * | Y | - | - | Y | - | - |
| payments.view | * | Y | Y | - | Y | - | Y |
| payments.create | * | Y | - | - | Y | - | - |
| payments.delete | * | Y | - | - | Y | - | - |
| service_tickets.view | * | Y | - | - | - | Y | Y |
| service_tickets.create | * | Y | - | - | - | Y | - |
| service_tickets.edit | * | Y | - | - | - | Y | - |
| service_tickets.delete | * | Y | - | - | - | Y | - |
| reports.view | * | Y | Y | Y | Y | - | Y |
| reports.export | * | Y | - | - | Y | - | - |
| settings.view | * | Y | - | - | Y | - | Y |
| settings.edit | * | Y | - | - | - | - | - |

**Legend:** `*` = Bypasses checks (Super Admin), `Y` = Granted, `-` = Not granted
