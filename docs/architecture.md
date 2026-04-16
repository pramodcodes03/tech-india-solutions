# Architecture Overview

## Technology Stack

ALTechnics ERP is a monolithic Laravel application following the MVC pattern with an optional service layer for complex business logic.

- **Backend:** Laravel 13 on PHP 8.3+
- **Database:** MySQL 8+ (SQLite supported for dev/testing)
- **Frontend:** Blade templates, Tailwind CSS, Alpine.js for interactivity
- **Authentication:** Laravel's built-in auth with a custom `admin` guard
- **Authorization:** Spatie Laravel Permission (roles and permissions)
- **Audit Trail:** Spatie Laravel Activitylog on all major models
- **PDF Generation:** barryvdh/laravel-dompdf
- **Excel Export:** Maatwebsite/Excel
- **Charts:** Chart.js rendered client-side

## Folder Structure

```
LeatherTechnicsERP/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # All admin panel controllers
│   │   │   └── DocumentationController.php
│   │   ├── Middleware/
│   │   └── Requests/           # Form request validation classes
│   ├── Models/                 # Eloquent models (25+ models)
│   └── Services/               # Business logic services
├── config/
│   ├── permission.php          # Spatie permission config
│   └── activitylog.php         # Spatie activity log config
├── database/
│   ├── migrations/             # 23+ migration files
│   └── seeders/                # Role, admin, and sample data seeders
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin panel Blade views
│   │   ├── components/         # Reusable Blade components
│   │   └── documentation/      # Public documentation view
│   └── docs/                   # Markdown documentation source files
├── routes/
│   └── web.php                 # All web routes (admin + docs)
├── docs/                       # Duplicate docs for GitHub rendering
└── public/                     # Public assets
```

## Request Lifecycle

Every request in the admin panel follows this flow:

```
Browser Request
    → Route (web.php)
        → Middleware (auth:admin)
            → Controller (Admin/*.php)
                → Form Request (validation)
                    → Service Layer (optional, for complex logic)
                        → Eloquent Model (database operations)
                            → Blade View (HTML response)
```

## Authentication Architecture

The application uses a custom `admin` guard backed by the `admins` table and `Admin` model. This is separate from Laravel's default `users` table, allowing the ERP admin panel to be fully independent.

## Service Layer

Controllers handling complex business logic (such as stock decrements on sales order confirmation, or invoice balance recalculation on payment) delegate to service classes in `app/Services/`. Simple CRUD operations are handled directly in controllers.

## Authorization Model

Spatie Laravel Permission provides role-based access. The `Super Admin` role bypasses all permission checks via a `Gate::before` callback. All other roles have explicit permission assignments. Permissions follow the `module.action` naming convention (e.g., `customers.create`, `invoices.export_pdf`).

## Activity Logging

Every model that uses the `LogsActivity` trait automatically records create, update, and delete events. The activity log powers the "Recent Activity" widget on the dashboard and provides a full audit trail.
