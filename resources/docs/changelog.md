# Changelog

All notable changes to the ALTechnics ERP project are documented here.

## [1.0.0] - 2026-04-11

### Initial Release

The first production release of ALTechnics ERP with complete business management functionality.

### Features

#### Authentication & Authorization
- Custom admin authentication with `admin` guard
- 7 predefined roles: Super Admin, Admin, Sales, Inventory, Accounts, Service, Viewer
- 65+ granular permissions across 19 modules
- Role-based dashboard widgets

#### Customer Relationship Management
- Customer management with auto-generated codes
- Lead management with kanban board view
- Lead-to-customer conversion workflow
- Lead activity tracking and follow-up scheduling

#### Sales Pipeline
- Quotation management with line items and auto-calculation
- Quotation PDF export with company branding
- Quotation cloning for quick re-use
- Quotation-to-sales-order conversion
- Sales order status workflow (draft through delivered)
- Stock decrement on sales order confirmation

#### Inventory Management
- Multi-warehouse support with default warehouse
- Real-time stock tracking via stock movements
- Stock movement types: in, out, adjustment
- Manual stock adjustment with audit trail
- Low stock alerts based on reorder levels

#### Purchase Management
- Vendor management with status toggle
- Purchase order creation and management
- Partial goods receiving with multiple receipts per PO
- Automatic stock-in on goods receipt

#### Invoicing & Payments
- Invoice generation from sales orders
- Professional PDF invoices with GST details
- Payment recording with multiple modes (Cash, Bank, UPI, Cheque, Card)
- Automatic invoice balance recalculation
- Overdue invoice tracking

#### Service Management
- Service ticket creation and lifecycle management
- Priority levels: low, medium, high, critical
- Technician assignment
- Threaded comments on tickets

#### Reporting
- Sales, inventory, customer, purchase, and payment reports
- Date range and dimension filters
- Excel and PDF export

#### Settings & Configuration
- Company information management
- Document prefix configuration
- Currency symbol setting
- Default terms and conditions

#### Technical
- Spatie Activity Log on all major models
- Spatie Laravel Permission for RBAC
- DomPDF for PDF generation
- Maatwebsite/Excel for spreadsheet exports
- Spatie Laravel Backup for scheduled backups
- Public documentation site with markdown rendering
