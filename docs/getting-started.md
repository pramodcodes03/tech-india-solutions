# Getting Started

## Requirements

Before installing ALTechnics ERP, ensure your environment meets the following requirements:

- **PHP** 8.3 or higher
- **Composer** 2.x
- **Node.js** 18+ and npm
- **MySQL** 8.0+ (or SQLite for development)
- **Git** for version control
- PHP extensions: `mbstring`, `xml`, `curl`, `mysql`, `gd`, `zip`

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/LeatherTechnicsERP.git
cd LeatherTechnicsERP
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Frontend Dependencies

```bash
npm install && npm run build
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database connection:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=altechnics_erp
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This seeds roles, permissions, default settings, sample admin users, warehouses, categories, products, customers, vendors, and sample transaction data.

### 6. Storage Link

```bash
php artisan storage:link
```

### 7. Start the Development Server

```bash
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

> **Important:** Change all default passwords immediately after first login in a production environment.

## Quick Verification

After logging in as Super Admin, verify that:

1. The dashboard loads with widgets and charts
2. Navigate to Settings to confirm company information is seeded
3. Check Products, Customers, and Vendors for sample data
4. Visit `/documentation` in the browser to confirm this documentation site loads
