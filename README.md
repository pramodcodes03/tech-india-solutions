[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

# ALTechnics ERP

A comprehensive Enterprise Resource Planning system built for **Apparel & Leather Technics Pvt. Ltd.** using Laravel 13, Tailwind CSS, and Alpine.js.

## Features

- **CRM** — Customer management, lead tracking with kanban board, lead-to-customer conversion
- **Sales Pipeline** — Quotations, sales orders, PDF export, quotation cloning and conversion
- **Inventory** — Multi-warehouse stock tracking, stock movements, low stock alerts, manual adjustments
- **Purchasing** — Vendor management, purchase orders, partial goods receiving
- **Invoicing** — Invoice generation from sales orders, PDF invoices, overdue tracking
- **Payments** — Multi-mode payment recording, automatic balance recalculation
- **Service** — Service ticket lifecycle, technician assignment, threaded comments
- **Access Control** — 7 roles with 65+ granular permissions via Spatie Laravel Permission
- **Reports** — Sales, inventory, customer, purchase, and payment reports with Excel/PDF export
- **Audit Trail** — Full activity logging via Spatie Laravel Activitylog
- **Documentation** — Built-in public documentation site at `/documentation`

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Database | MySQL 8+ / SQLite |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| PDF | DomPDF |
| Excel | Maatwebsite/Excel |
| Permissions | Spatie Laravel Permission |
| Activity Log | Spatie Laravel Activitylog |
| Backup | Spatie Laravel Backup |

## Quick Start

```bash
# Clone and install
git clone https://github.com/your-org/LeatherTechnicsERP.git
cd LeatherTechnicsERP
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials

# Setup database
php artisan migrate --seed
php artisan storage:link

# Run
php artisan serve
```

Visit `http://localhost:8000/admin/login` to access the admin panel.

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@altechnics.com | Admin@12345 |
| Admin | rajesh@altechnics.com | Admin@12345 |
| Sales | priya@altechnics.com | Admin@12345 |
| Inventory | suresh@altechnics.com | Admin@12345 |
| Accounts | lakshmi@altechnics.com | Admin@12345 |
| Service | mohammed@altechnics.com | Admin@12345 |
| Viewer | anita@altechnics.com | Admin@12345 |

> **Change all default passwords immediately in production.**

## Documentation

Full documentation is available at `/documentation` when the application is running, or browse the markdown files in the [`docs/`](docs/) directory.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

Copyright (c) 2026 Apparel & Leather Technics Pvt. Ltd.
