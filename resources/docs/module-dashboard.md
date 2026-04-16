# Dashboard & Reports

## Purpose

The Dashboard provides a real-time overview of business performance with role-aware widgets, charts, and summary tables. The Reports module offers detailed, filterable, and exportable reports.

## Dashboard Widgets

The dashboard displays different widgets based on the logged-in user's role:

### All Roles
- **Welcome message** with user name and role

### Sales / Admin / Super Admin
- Total Customers count
- Total Quotations and their value
- Total Sales Orders and their value
- Monthly sales trend (current year)

### Accounts / Admin / Super Admin
- Total Invoices and outstanding amount
- Total Payments received (current month)
- Overdue invoices count and value
- Payment collection trend chart

### Inventory / Admin / Super Admin
- Total Products count
- Low stock alerts count
- Recent stock movements
- Warehouse utilization

### Service
- Open tickets count
- Tickets by priority breakdown
- Average resolution time
- Tickets assigned to current user

## Charts

Charts are rendered using Chart.js on the client side:

- **Sales Trend** — Line chart showing monthly sales over the current year
- **Top Products** — Bar chart of best-selling products by quantity
- **Invoice Status** — Doughnut chart showing draft/sent/paid/overdue distribution
- **Lead Pipeline** — Funnel-style bar chart of leads by status

## Top Tables

- **Top 5 Customers** — By total sales value
- **Recent Orders** — Last 10 sales orders with status
- **Recent Activity** — Last 20 activity log entries from Spatie Activitylog

## Reports Module

Navigate to **Reports** for detailed reporting:

| Report | Description | Filters |
|--------|-------------|---------|
| Sales Report | Sales orders by date range, customer, status | Date range, customer, status |
| Inventory Report | Current stock levels, movement history | Product, warehouse, date range |
| Customer Report | Customer-wise sales summary | Customer, date range |
| Purchase Report | Purchase orders and goods receipts | Vendor, date range, status |
| Payment Report | Payment collection summary | Date range, payment mode |

### Export

All reports support export to:
- **Excel** — Via Maatwebsite/Excel package
- **PDF** — Via DomPDF

Export routes follow the pattern: `/admin/reports/{type}/export-excel` and `/admin/reports/{type}/export-pdf`.

## Permissions

| Permission | Description |
|-----------|-------------|
| `dashboard.view` | Access the dashboard |
| `reports.view` | View reports |
| `reports.export` | Export reports to Excel/PDF |
