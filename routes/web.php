<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\Hr\AppraisalController as HrAppraisalController;
use App\Http\Controllers\Admin\Hr\BankEditRequestController as HrBankEditRequestController;
use App\Http\Controllers\Admin\Hr\DashboardController as HrDashboardController;
use App\Http\Controllers\Admin\Hr\AppraisalCycleController as HrAppraisalCycleController;
use App\Http\Controllers\Admin\Hr\AttendanceController as HrAttendanceController;
use App\Http\Controllers\Admin\Hr\CompOffController as HrCompOffController;
use App\Http\Controllers\Admin\Hr\DepartmentController as HrDepartmentController;
use App\Http\Controllers\Admin\Hr\DesignationController as HrDesignationController;
use App\Http\Controllers\Admin\Hr\EmployeeController as HrEmployeeController;
use App\Http\Controllers\Admin\Hr\IncrementController as HrIncrementController;
use App\Http\Controllers\Admin\Hr\FeedbackController as HrFeedbackController;
use App\Http\Controllers\Admin\Hr\HolidayController as HrHolidayController;
use App\Http\Controllers\Admin\Hr\LeaveBalanceController as HrLeaveBalanceController;
use App\Http\Controllers\Admin\Hr\LeaveController as HrLeaveController;
use App\Http\Controllers\Admin\Hr\LeaveTypeController as HrLeaveTypeController;
use App\Http\Controllers\Admin\Hr\PayrollController as HrPayrollController;
use App\Http\Controllers\Admin\Hr\PenaltyController as HrPenaltyController;
use App\Http\Controllers\Admin\Hr\RegularizationController as HrRegularizationController;
use App\Http\Controllers\Admin\Hr\CandidateController as HrCandidateController;
use App\Http\Controllers\Admin\Hr\RecruitmentStageController as HrRecruitmentStageController;
use App\Http\Controllers\Admin\Hr\RecruitmentBatchController as HrRecruitmentBatchController;
use App\Http\Controllers\Admin\Hr\RecruitmentReportController as HrRecruitmentReportController;
use App\Http\Controllers\Admin\Hr\ShiftController as HrShiftController;
use App\Http\Controllers\Admin\Hr\WarningController as HrWarningController;
use App\Http\Controllers\Admin\Hr\WeekOffController as HrWeekOffController;
use App\Http\Controllers\Employee\AttendanceController as EmpAttendanceController;
use App\Http\Controllers\Employee\AuthController as EmpAuthController;
use App\Http\Controllers\Employee\DashboardController as EmpDashboardController;
use App\Http\Controllers\Employee\FeedbackController as EmpFeedbackController;
use App\Http\Controllers\Employee\LeaveController as EmpLeaveController;
use App\Http\Controllers\Employee\PayslipController as EmpPayslipController;
use App\Http\Controllers\Employee\AppraisalController as EmpAppraisalController;
use App\Http\Controllers\Employee\PerformanceController as EmpPerformanceController;
use App\Http\Controllers\Employee\ProfileController as EmpProfileController;
use App\Http\Controllers\Employee\PenaltyController as EmpPenaltyController;
use App\Http\Controllers\Employee\WarningController as EmpWarningController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardsController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProformaInvoiceController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceTicketController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Public documentation site
Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('/search', [DocumentationController::class, 'search'])->name('search');
    Route::get('/{section}', [DocumentationController::class, 'section'])->name('section');
});

// Admin auth routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/signin', [AuthController::class, 'signin'])->name('signin');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware(['auth:admin', 'business'])->group(function () {
        // Common welcome page — every authenticated admin lands here on
        // login. Doesn't require any module permission, so even users with
        // very narrow roles can see SOMETHING instead of bouncing into a
        // dashboard they can't access.
        Route::get('/welcome', [AuthController::class, 'welcome'])->name('welcome');
        Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

        // Business management (Super Admin)
        Route::get('select-business', [BusinessController::class, 'selector'])->name('businesses.select');
        Route::post('businesses/{business}/switch', [BusinessController::class, 'switch'])->name('businesses.switch');
        Route::post('businesses/{business}/admins', [BusinessController::class, 'storeAdmin'])->name('businesses.admins.store');
        Route::put('businesses/{business}/admins/{admin}', [BusinessController::class, 'updateAdmin'])->name('businesses.admins.update');
        Route::delete('businesses/{business}/admins/{admin}', [BusinessController::class, 'destroyAdmin'])->name('businesses.admins.destroy');
        Route::resource('businesses', BusinessController::class);

        // Specialised dashboards
        Route::prefix('dashboards')->name('dashboards.')->group(function () {
            Route::get('sales',      [DashboardsController::class, 'sales'])->name('sales');
            Route::get('service',    [DashboardsController::class, 'service'])->name('service');
            Route::get('inventory',  [DashboardsController::class, 'inventory'])->name('inventory');
            Route::get('purchase',   [DashboardsController::class, 'purchase'])->name('purchase');
            Route::get('customers',  [DashboardsController::class, 'customers'])->name('customers');
            Route::get('executive',  [DashboardsController::class, 'executive'])->name('executive');
        });

        // Admin User Management
        Route::resource('admin-users', AdminUserController::class)->parameters(['admin-users' => 'admin_user']);
        Route::patch('admin-users/{admin_user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin-users.toggle-status');
        Route::post('change-password', [AdminUserController::class, 'changePassword'])->name('change-password');

        // Role & Permission Management
        Route::resource('roles', RoleController::class);

        // Location Management
        Route::get('locations/cities', [StateController::class, 'cities'])->name('locations.cities');
        Route::resource('states', StateController::class);
        Route::patch('states/{state}/toggle-status', [StateController::class, 'toggleStatus'])->name('states.toggle-status');
        Route::resource('cities', CityController::class);
        Route::patch('cities/{city}/toggle-status', [CityController::class, 'toggleStatus'])->name('cities.toggle-status');

        // Customer Management
        Route::resource('customers', CustomerController::class);
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

        // Lead Management
        Route::get('leads/kanban', [LeadController::class, 'kanban'])->name('leads.kanban');
        Route::get('leads/report', [LeadController::class, 'report'])->name('leads.report');
        // Bulk operations + CSV import (declared before the resource route so
        // these paths aren't captured as a {lead} show parameter).
        Route::get('leads/import', [LeadController::class, 'importForm'])->name('leads.import.form');
        Route::post('leads/import', [LeadController::class, 'import'])->name('leads.import');
        Route::get('leads/import/template', [LeadController::class, 'importTemplate'])->name('leads.import.template');
        Route::post('leads/bulk-delete', [LeadController::class, 'bulkDelete'])->name('leads.bulk-delete');
        Route::post('leads/bulk-update', [LeadController::class, 'bulkUpdate'])->name('leads.bulk-update');
        Route::resource('leads', LeadController::class);
        Route::post('leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])->name('leads.convert');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');

        // Quotation Management
        Route::resource('quotations', QuotationController::class);
        Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
        Route::post('quotations/{quotation}/clone', [QuotationController::class, 'clone'])->name('quotations.clone');
        Route::post('quotations/{quotation}/convert-to-order', [QuotationController::class, 'convertToOrder'])->name('quotations.convert-to-order');
        Route::patch('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.update-status');

        // Proforma Invoice Management
        Route::resource('proforma-invoices', ProformaInvoiceController::class)->parameters(['proforma-invoices' => 'proforma_invoice']);
        Route::get('proforma-invoices/{proforma_invoice}/pdf', [ProformaInvoiceController::class, 'pdf'])->name('proforma-invoices.pdf');
        Route::post('proforma-invoices/{proforma_invoice}/clone', [ProformaInvoiceController::class, 'clone'])->name('proforma-invoices.clone');
        Route::post('proforma-invoices/{proforma_invoice}/convert-to-invoice', [ProformaInvoiceController::class, 'convertToInvoice'])->name('proforma-invoices.convert-to-invoice');
        Route::patch('proforma-invoices/{proforma_invoice}/status', [ProformaInvoiceController::class, 'updateStatus'])->name('proforma-invoices.update-status');

        // Sales Order Management
        Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'sales_order']);
        Route::patch('sales-orders/{sales_order}/status', [SalesOrderController::class, 'updateStatus'])->name('sales-orders.update-status');
        Route::post('sales-orders/{sales_order}/generate-invoice', [SalesOrderController::class, 'generateInvoice'])->name('sales-orders.generate-invoice');

        // Product Management
        Route::resource('products', ProductController::class);
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

        // Category Management
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

        // Inventory Management
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::post('inventory/adjust', [InventoryController::class, 'storeAdjustment'])->name('inventory.store-adjustment');

        // Warehouse Management
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::patch('warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('warehouses.toggle-status');

        // Vendor Management
        Route::resource('vendors', VendorController::class);
        Route::patch('vendors/{vendor}/toggle-status', [VendorController::class, 'toggleStatus'])->name('vendors.toggle-status');

        // Purchase Order Management
        Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase-orders' => 'purchase_order']);
        Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receiveGoods'])->name('purchase-orders.receive');

        // Invoice Management
        Route::resource('invoices', InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Payment Management
        Route::resource('payments', PaymentController::class)->except(['edit', 'update']);

        // Expense Management
        Route::resource('expense-categories', ExpenseCategoryController::class)->parameters(['expense-categories' => 'expense_category']);
        Route::post('expense-categories/{expense_category}/subcategories', [ExpenseCategoryController::class, 'storeSubcategory'])->name('expense-categories.subcategories.store');
        Route::put('expense-categories/{expense_category}/subcategories/{subcategory}', [ExpenseCategoryController::class, 'updateSubcategory'])->name('expense-categories.subcategories.update');
        Route::delete('expense-categories/{expense_category}/subcategories/{subcategory}', [ExpenseCategoryController::class, 'destroySubcategory'])->name('expense-categories.subcategories.destroy');

        Route::get('expense-categories/{expense_category}/subcategories-json', [ExpenseController::class, 'subcategories'])->name('expenses.subcategories.json');
        Route::get('expenses/{expense}/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
        Route::resource('expenses', ExpenseController::class);
        Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');

        // Standardized bulk import framework (validate → preview → confirm → log)
        Route::get('imports', [\App\Http\Controllers\Admin\BulkImportController::class, 'index'])->name('imports.index');
        Route::get('imports/log/{log}/errors', [\App\Http\Controllers\Admin\BulkImportController::class, 'errorReport'])->name('imports.errors');
        Route::get('imports/{key}', [\App\Http\Controllers\Admin\BulkImportController::class, 'form'])->name('imports.form');
        Route::get('imports/{key}/template', [\App\Http\Controllers\Admin\BulkImportController::class, 'template'])->name('imports.template');
        Route::post('imports/{key}/preview', [\App\Http\Controllers\Admin\BulkImportController::class, 'preview'])->name('imports.preview');
        Route::post('imports/{key}/confirm', [\App\Http\Controllers\Admin\BulkImportController::class, 'confirm'])->name('imports.confirm');

        // Employee reimbursement claims (admin review)
        Route::get('reimbursements', [\App\Http\Controllers\Admin\ReimbursementController::class, 'index'])->name('reimbursements.index');
        Route::get('reimbursements/{reimbursement}', [\App\Http\Controllers\Admin\ReimbursementController::class, 'show'])->name('reimbursements.show');
        Route::get('reimbursements/{reimbursement}/bill', [\App\Http\Controllers\Admin\ReimbursementController::class, 'bill'])->name('reimbursements.bill');
        Route::post('reimbursements/{reimbursement}/review', [\App\Http\Controllers\Admin\ReimbursementController::class, 'review'])->name('reimbursements.review');

        // Expense budgets
        Route::get('budgets', [\App\Http\Controllers\Admin\BudgetController::class, 'index'])->name('budgets.index');
        Route::post('budgets', [\App\Http\Controllers\Admin\BudgetController::class, 'store'])->name('budgets.store');
        Route::put('budgets/{budget}', [\App\Http\Controllers\Admin\BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('budgets/{budget}', [\App\Http\Controllers\Admin\BudgetController::class, 'destroy'])->name('budgets.destroy');

        // Requisitions (purchase requests with approval chain)
        // Requisition categories (manageable dropdown options)
        Route::get('requisition-categories', [\App\Http\Controllers\Admin\RequisitionCategoryController::class, 'index'])->name('requisition-categories.index');
        Route::post('requisition-categories', [\App\Http\Controllers\Admin\RequisitionCategoryController::class, 'store'])->name('requisition-categories.store');
        Route::patch('requisition-categories/{category}', [\App\Http\Controllers\Admin\RequisitionCategoryController::class, 'update'])->name('requisition-categories.update');
        Route::patch('requisition-categories/{category}/toggle', [\App\Http\Controllers\Admin\RequisitionCategoryController::class, 'toggle'])->name('requisition-categories.toggle');
        Route::delete('requisition-categories/{category}', [\App\Http\Controllers\Admin\RequisitionCategoryController::class, 'destroy'])->name('requisition-categories.destroy');

        Route::get('requisitions/report', [\App\Http\Controllers\Admin\RequisitionController::class, 'report'])->name('requisitions.report');
        Route::resource('requisitions', \App\Http\Controllers\Admin\RequisitionController::class)->except(['edit', 'update', 'destroy']);
        Route::post('requisitions/{requisition}/approve', [\App\Http\Controllers\Admin\RequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::post('requisitions/{requisition}/reject', [\App\Http\Controllers\Admin\RequisitionController::class, 'reject'])->name('requisitions.reject');
        Route::post('requisitions/{requisition}/disburse', [\App\Http\Controllers\Admin\RequisitionController::class, 'disburse'])->name('requisitions.disburse');

        // Email Notification Settings
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::put('notifications', [NotificationController::class, 'update'])->name('notifications.update');
        Route::post('notifications/test', [NotificationController::class, 'test'])->name('notifications.test');
        Route::get('notifications/logs', [NotificationController::class, 'logs'])->name('notifications.logs');

        // In-app notification inbox
        Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
        Route::get('inbox/open/{adminNotification}', [InboxController::class, 'open'])->name('inbox.open');
        Route::post('inbox/mark-all-read', [InboxController::class, 'markAllRead'])->name('inbox.mark-all-read');
        Route::get('inbox/unread-count', [InboxController::class, 'unreadCount'])->name('inbox.unread-count');

        // Service Ticket Management
        Route::resource('service-categories', ServiceCategoryController::class)->except(['show']);
        Route::resource('service-tickets', ServiceTicketController::class)->parameters(['service-tickets' => 'service_ticket']);
        Route::post('service-tickets/{service_ticket}/comments', [ServiceTicketController::class, 'addComment'])->name('service-tickets.add-comment');
        Route::patch('service-tickets/{service_ticket}/status', [ServiceTicketController::class, 'updateStatus'])->name('service-tickets.update-status');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
        Route::get('reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
        Route::get('reports/{type}/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
        Route::get('reports/{type}/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // Custom report builder
        Route::get('report-builder', [\App\Http\Controllers\Admin\ReportBuilderController::class, 'index'])->name('report-builder.index');
        Route::post('report-builder/build', [\App\Http\Controllers\Admin\ReportBuilderController::class, 'build'])->name('report-builder.build');
        Route::post('report-builder/save', [\App\Http\Controllers\Admin\ReportBuilderController::class, 'save'])->name('report-builder.save');
        Route::get('report-builder/saved/{template}', [\App\Http\Controllers\Admin\ReportBuilderController::class, 'runSaved'])->name('report-builder.run');
        Route::delete('report-builder/saved/{template}', [\App\Http\Controllers\Admin\ReportBuilderController::class, 'destroy'])->name('report-builder.destroy');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // ════════════════════════════════════════════════════════════════
        // HR Module
        // ════════════════════════════════════════════════════════════════
        Route::prefix('hr')->name('hr.')->group(function () {
            // HR Dashboard
            Route::get('dashboard', [HrDashboardController::class, 'index'])->name('dashboard');

            // HR Reports hub (employee master, payroll, leave, attendance, expense claims)
            Route::get('reports', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/employee-master', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'employeeMaster'])->name('reports.employee-master');
            Route::get('reports/payroll', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'payroll'])->name('reports.payroll');
            Route::get('reports/leave', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'leave'])->name('reports.leave');
            Route::get('reports/attendance', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'attendance'])->name('reports.attendance');
            Route::get('reports/expense-claims', [\App\Http\Controllers\Admin\Hr\ReportController::class, 'expenseClaims'])->name('reports.expense-claims');

            // ── Recruitment & Hiring ─────────────────────────────────────
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('pipeline', [HrCandidateController::class, 'pipeline'])->name('pipeline');
                Route::get('reports', [HrRecruitmentReportController::class, 'index'])->name('reports');
                Route::get('reports/export', [HrRecruitmentReportController::class, 'export'])->name('reports.export');
                Route::get('import', [HrRecruitmentReportController::class, 'importForm'])->name('import.form');
                Route::post('import', [HrRecruitmentReportController::class, 'import'])->name('import');
                Route::get('import/template', [HrRecruitmentReportController::class, 'template'])->name('import.template');

                // Stages (configurable pipeline)
                Route::get('stages', [HrRecruitmentStageController::class, 'index'])->name('stages.index');
                Route::post('stages', [HrRecruitmentStageController::class, 'store'])->name('stages.store');
                Route::put('stages/{stage}', [HrRecruitmentStageController::class, 'update'])->name('stages.update');
                Route::delete('stages/{stage}', [HrRecruitmentStageController::class, 'destroy'])->name('stages.destroy');
                Route::post('stages/reorder', [HrRecruitmentStageController::class, 'reorder'])->name('stages.reorder');

                // Campus batches
                Route::get('batches', [HrRecruitmentBatchController::class, 'index'])->name('batches.index');
                Route::post('batches', [HrRecruitmentBatchController::class, 'store'])->name('batches.store');
                Route::put('batches/{batch}', [HrRecruitmentBatchController::class, 'update'])->name('batches.update');
                Route::delete('batches/{batch}', [HrRecruitmentBatchController::class, 'destroy'])->name('batches.destroy');

                // Candidate actions
                Route::post('bulk-action', [HrCandidateController::class, 'bulkAction'])->name('bulk-action');
                Route::post('{candidate}/move', [HrCandidateController::class, 'move'])->name('move');
                Route::post('{candidate}/note', [HrCandidateController::class, 'addNote'])->name('note');
                Route::match(['get', 'post'], '{candidate}/offer-letter', [HrCandidateController::class, 'offerLetter'])->name('offer-letter');
                Route::post('{candidate}/offer-letter/email', [HrCandidateController::class, 'emailOfferLetter'])->name('offer-letter.email');
            });
            Route::resource('recruitment', HrCandidateController::class)->parameters(['recruitment' => 'candidate']);

            // Employees
            Route::post('employees/bulk-action', [HrEmployeeController::class, 'bulkAction'])->name('employees.bulk-action');
            Route::get('employees/export', [HrEmployeeController::class, 'export'])->name('employees.export');
            Route::get('reporting-managers', [\App\Http\Controllers\Admin\Hr\ReportingManagerController::class, 'index'])->name('reporting-managers.index');
            Route::resource('employees', HrEmployeeController::class);
            Route::post('employees/{employee}/reset-password', [HrEmployeeController::class, 'resetPassword'])->name('employees.reset-password');
            Route::post('employees/{employee}/toggle-status', [HrEmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');

            // Employee documents (upload, verify/reject, audit, bulk ZIP)
            Route::get('employees/{employee}/documents', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'index'])->name('employees.documents.index');
            Route::post('employees/{employee}/documents', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'store'])->name('employees.documents.store');
            Route::get('employees/{employee}/documents/download-all', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'bulkDownload'])->name('employees.documents.bulk-download');
            Route::get('employee-documents/{document}/view', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'view'])->name('employee-documents.view');
            Route::get('employee-documents/{document}/download', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'download'])->name('employee-documents.download');
            Route::post('employee-documents/{document}/verify', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'verify'])->name('employee-documents.verify');
            Route::post('employee-documents/{document}/reject', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'reject'])->name('employee-documents.reject');
            Route::delete('employee-documents/{document}', [\App\Http\Controllers\Admin\Hr\DocumentController::class, 'destroy'])->name('employee-documents.destroy');

            // Departments & Designations
            Route::resource('departments', HrDepartmentController::class)->except(['show']);
            Route::resource('designations', HrDesignationController::class)->except(['show']);

            // Shifts
            Route::resource('shifts', HrShiftController::class)->except(['show']);

            // Holidays
            Route::resource('holidays', HrHolidayController::class)->except(['show']);

            // Week-Off Configuration
            Route::get('week-off', [HrWeekOffController::class, 'index'])->name('week-off.index');
            Route::post('week-off', [HrWeekOffController::class, 'save'])->name('week-off.save');

            // Comp-Off (Dynamic Week-Off)
            Route::get('comp-off', [HrCompOffController::class, 'index'])->name('comp-off.index');
            Route::post('comp-off/{compOff}/approve', [HrCompOffController::class, 'approve'])->name('comp-off.approve');
            Route::post('comp-off/{compOff}/reject', [HrCompOffController::class, 'reject'])->name('comp-off.reject');

            // Leave Types
            Route::resource('leave-types', HrLeaveTypeController::class)->except(['show'])->parameters(['leave-types' => 'leaveType']);

            // Attendance
            Route::get('attendance', [HrAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('attendance/export', [HrAttendanceController::class, 'export'])->name('attendance.export');
            Route::get('attendance/monthly', [HrAttendanceController::class, 'monthly'])->name('attendance.monthly');
            Route::get('attendance/monthly/export', [HrAttendanceController::class, 'exportMonthly'])->name('attendance.monthly-export');
            Route::get('attendance/create', [HrAttendanceController::class, 'create'])->name('attendance.create');
            Route::post('attendance', [HrAttendanceController::class, 'store'])->name('attendance.store');
            Route::get('attendance/import', [HrAttendanceController::class, 'importForm'])->name('attendance.import-form');
            Route::post('attendance/import', [HrAttendanceController::class, 'import'])->name('attendance.import');
            Route::post('attendance/biometric-sync', [\App\Http\Controllers\Admin\Hr\BiometricSyncController::class, 'sync'])->name('attendance.biometric-sync');
            // Edit a single attendance row. Declared after the specific URLs
            // above so /attendance/monthly|create|import etc. still resolve.
            Route::get('attendance/{attendance}/edit', [HrAttendanceController::class, 'edit'])->name('attendance.edit');
            Route::patch('attendance/{attendance}', [HrAttendanceController::class, 'update'])->name('attendance.update');

            // Internal helpdesk (employee tickets → department workflow)
            Route::get('helpdesk', [\App\Http\Controllers\Admin\Hr\InternalTicketController::class, 'index'])->name('internal-tickets.index');
            Route::get('helpdesk/config', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'index'])->name('internal-tickets.config');
            Route::get('helpdesk/config/admins-by-role', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'adminsByRole'])->name('internal-tickets.admins-by-role');
            Route::post('helpdesk/config/categories', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'storeCategory'])->name('internal-tickets.categories.store');
            Route::delete('helpdesk/config/categories/{category}', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'destroyCategory'])->name('internal-tickets.categories.destroy');
            Route::post('helpdesk/config/levels', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'storeLevel'])->name('internal-tickets.levels.store');
            Route::delete('helpdesk/config/levels/{level}', [\App\Http\Controllers\Admin\Hr\HelpdeskConfigController::class, 'destroyLevel'])->name('internal-tickets.levels.destroy');
            Route::get('helpdesk/{internalTicket}', [\App\Http\Controllers\Admin\Hr\InternalTicketController::class, 'show'])->name('internal-tickets.show');
            Route::post('helpdesk/{internalTicket}/assign', [\App\Http\Controllers\Admin\Hr\InternalTicketController::class, 'assign'])->name('internal-tickets.assign');
            Route::post('helpdesk/{internalTicket}/status', [\App\Http\Controllers\Admin\Hr\InternalTicketController::class, 'status'])->name('internal-tickets.status');
            Route::post('helpdesk/{internalTicket}/comment', [\App\Http\Controllers\Admin\Hr\InternalTicketController::class, 'comment'])->name('internal-tickets.comment');

            // Attendance regularization (employee-raised corrections → HR review)
            Route::get('regularizations', [HrRegularizationController::class, 'index'])->name('regularizations.index');
            Route::get('regularizations/{regularization}', [HrRegularizationController::class, 'show'])->name('regularizations.show');
            Route::post('regularizations/{regularization}/approve', [HrRegularizationController::class, 'approve'])->name('regularizations.approve');
            Route::post('regularizations/{regularization}/reject', [HrRegularizationController::class, 'reject'])->name('regularizations.reject');

            // Leave settings (probation, accrual, backdated window, policy doc)
            Route::get('leave-settings', [\App\Http\Controllers\Admin\Hr\LeaveSettingsController::class, 'index'])->name('leave-settings.index');
            Route::post('leave-settings', [\App\Http\Controllers\Admin\Hr\LeaveSettingsController::class, 'update'])->name('leave-settings.update');
            Route::post('leave-settings/run', [\App\Http\Controllers\Admin\Hr\LeaveSettingsController::class, 'run'])->name('leave-settings.run');

            // Leaves
            Route::get('leaves', [HrLeaveController::class, 'index'])->name('leaves.index');
            Route::get('leave-balances', [HrLeaveBalanceController::class, 'index'])->name('leave-balances.index');
            Route::post('leave-balances/bulk-allocate', [HrLeaveBalanceController::class, 'bulkAllocate'])->name('leave-balances.bulk-allocate');
            Route::get('leave-balances/{employee}', [HrLeaveBalanceController::class, 'edit'])->name('leave-balances.edit');
            Route::put('leave-balances/{employee}', [HrLeaveBalanceController::class, 'update'])->name('leave-balances.update');
            Route::get('leaves/{leaveRequest}', [HrLeaveController::class, 'show'])->name('leaves.show');
            Route::post('leaves/{leaveRequest}/approve', [HrLeaveController::class, 'approve'])->name('leaves.approve');
            Route::post('leaves/{leaveRequest}/reject', [HrLeaveController::class, 'reject'])->name('leaves.reject');

            // ── Payroll add-ons: templates, adjustments, statutory ──────────
            // Salary templates (dept / category level) + bulk assignment
            Route::get('salary-templates', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'index'])->name('salary-templates.index');
            Route::post('salary-templates', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'store'])->name('salary-templates.store');
            Route::put('salary-templates/{template}', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'update'])->name('salary-templates.update');
            Route::delete('salary-templates/{template}', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'destroy'])->name('salary-templates.destroy');
            Route::get('salary-templates/{template}/assign', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'assignForm'])->name('salary-templates.assign-form');
            Route::post('salary-templates/{template}/assign', [\App\Http\Controllers\Admin\Hr\SalaryTemplateController::class, 'assign'])->name('salary-templates.assign');

            // Payroll adjustments (incentive / arrears / bonus / extra deduction)
            Route::get('payroll-adjustments', [\App\Http\Controllers\Admin\Hr\PayrollAdjustmentController::class, 'index'])->name('payroll-adjustments.index');
            Route::post('payroll-adjustments', [\App\Http\Controllers\Admin\Hr\PayrollAdjustmentController::class, 'store'])->name('payroll-adjustments.store');
            Route::post('payroll-adjustments/arrears', [\App\Http\Controllers\Admin\Hr\PayrollAdjustmentController::class, 'generateArrears'])->name('payroll-adjustments.arrears');
            Route::delete('payroll-adjustments/{adjustment}', [\App\Http\Controllers\Admin\Hr\PayrollAdjustmentController::class, 'destroy'])->name('payroll-adjustments.destroy');

            // Statutory: compliance registers, challans, bank export, TDS slabs, Form 16
            Route::get('statutory', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'register'])->name('statutory.register');
            Route::get('statutory/export', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'export'])->name('statutory.export');
            Route::get('statutory/bank-transfer', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'bankTransfer'])->name('statutory.bank-transfer');
            Route::get('statutory/settings', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'settings'])->name('statutory.settings');
            Route::post('statutory/settings', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'saveSettings'])->name('statutory.settings.save');
            Route::post('statutory/slabs', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'storeSlab'])->name('statutory.slabs.store');
            Route::delete('statutory/slabs/{slab}', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'destroySlab'])->name('statutory.slabs.destroy');
            Route::get('statutory/form16', [\App\Http\Controllers\Admin\Hr\StatutoryController::class, 'form16'])->name('statutory.form16');

            // Payroll
            Route::get('payroll', [HrPayrollController::class, 'index'])->name('payroll.index');
            Route::get('payroll/generate', [HrPayrollController::class, 'generateForm'])->name('payroll.generate-form');
            Route::post('payroll/generate', [HrPayrollController::class, 'generate'])->name('payroll.generate');
            Route::post('payroll/preview-structure', [HrPayrollController::class, 'previewStructure'])->name('payroll.preview-structure');

            // Salary structure approvals (Admin / Super Admin only) — must
            // come BEFORE the `payroll/{payslip}` wildcard, otherwise
            // /payroll/approvals binds "approvals" as a payslip id and 404s.
            Route::get('payroll/approvals', [HrPayrollController::class, 'pendingApprovals'])->name('payroll.approvals.index');
            Route::post('payroll/approvals/{salaryStructure}/approve', [HrPayrollController::class, 'approveStructure'])->name('payroll.approvals.approve');
            Route::post('payroll/approvals/{salaryStructure}/reject', [HrPayrollController::class, 'rejectStructure'])->name('payroll.approvals.reject');

            Route::get('payroll/{payslip}', [HrPayrollController::class, 'show'])->name('payroll.show');
            Route::get('payroll/{payslip}/pdf', [HrPayrollController::class, 'pdf'])->name('payroll.pdf');
            Route::post('payroll/{payslip}/mark-paid', [HrPayrollController::class, 'markPaid'])->name('payroll.mark-paid');
            Route::get('employees/{employee}/salary', [HrPayrollController::class, 'salaryForm'])->name('salary.form');
            Route::post('employees/{employee}/salary', [HrPayrollController::class, 'salaryStore'])->name('salary.store');

            // Bank-detail edit requests
            Route::post('employees/{employee}/bank-edit-requests', [HrBankEditRequestController::class, 'store'])->name('employees.bank-edit-requests.store');
            Route::get('bank-edit-requests', [HrBankEditRequestController::class, 'index'])->name('bank-edit-requests.index');
            Route::post('bank-edit-requests/{bankEditRequest}/approve', [HrBankEditRequestController::class, 'approve'])->name('bank-edit-requests.approve');
            Route::post('bank-edit-requests/{bankEditRequest}/reject', [HrBankEditRequestController::class, 'reject'])->name('bank-edit-requests.reject');

            // Simple per-employee increment / appraisal
            Route::get('employees/{employee}/increments/create', [HrIncrementController::class, 'create'])->name('employees.increments.create');
            Route::post('employees/{employee}/increments', [HrIncrementController::class, 'store'])->name('employees.increments.store');

            // Warnings
            Route::get('warnings', [HrWarningController::class, 'index'])->name('warnings.index');
            Route::get('warnings/create', [HrWarningController::class, 'create'])->name('warnings.create');
            Route::post('warnings', [HrWarningController::class, 'store'])->name('warnings.store');
            Route::get('warnings/{warning}', [HrWarningController::class, 'show'])->name('warnings.show');
            Route::post('warnings/{warning}/withdraw', [HrWarningController::class, 'withdraw'])->name('warnings.withdraw');

            // Penalties
            Route::get('penalties', [HrPenaltyController::class, 'index'])->name('penalties.index');
            Route::get('penalties/create', [HrPenaltyController::class, 'create'])->name('penalties.create');
            Route::post('penalties', [HrPenaltyController::class, 'store'])->name('penalties.store');
            Route::post('penalties/{penalty}/reduce', [HrPenaltyController::class, 'reduce'])->name('penalties.reduce');
            Route::get('penalty-types', [HrPenaltyController::class, 'types'])->name('penalty-types.index');
            Route::post('penalty-types', [HrPenaltyController::class, 'storeType'])->name('penalty-types.store');
            Route::put('penalty-types/{type}', [HrPenaltyController::class, 'updateType'])->name('penalty-types.update');

            // Feedback
            Route::get('feedback', [HrFeedbackController::class, 'index'])->name('feedback.index');
            Route::get('feedback/{feedback}', [HrFeedbackController::class, 'show'])->name('feedback.show');

            // Appraisals (per-employee increment history)
            Route::get('appraisals', [HrAppraisalController::class, 'index'])->name('appraisals.index');
            Route::get('appraisals/{appraisal}', [HrAppraisalController::class, 'show'])->name('appraisals.show');
            Route::get('appraisals/{appraisal}/edit', [HrAppraisalController::class, 'edit'])->name('appraisals.edit');
            Route::put('appraisals/{appraisal}', [HrAppraisalController::class, 'update'])->name('appraisals.update');
            Route::delete('appraisals/{appraisal}', [HrAppraisalController::class, 'destroy'])->name('appraisals.destroy');
            Route::get('appraisals/{appraisal}/pdf', [HrAppraisalController::class, 'pdf'])->name('appraisals.pdf');
        });

        // ════════════════════════════════════════════════════════════════
        // Asset Management
        // ════════════════════════════════════════════════════════════════
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Admin\Asset\DashboardController::class, 'index'])->name('dashboard');

            Route::resource('categories', \App\Http\Controllers\Admin\Asset\CategoryController::class)->except(['show']);
            Route::resource('locations', \App\Http\Controllers\Admin\Asset\LocationController::class)->except(['show']);

            // Dynamic dropdown lookups — admins can add their own values
            // beyond the seeded defaults. Each list is a single-page CRUD
            // (no separate create/edit screens); the index view does it all.
            Route::get('statuses', [\App\Http\Controllers\Admin\Asset\StatusController::class, 'index'])->name('statuses.index');
            Route::post('statuses', [\App\Http\Controllers\Admin\Asset\StatusController::class, 'store'])->name('statuses.store');
            Route::patch('statuses/{status}', [\App\Http\Controllers\Admin\Asset\StatusController::class, 'update'])->name('statuses.update');
            Route::patch('statuses/{status}/toggle', [\App\Http\Controllers\Admin\Asset\StatusController::class, 'toggle'])->name('statuses.toggle');
            Route::delete('statuses/{status}', [\App\Http\Controllers\Admin\Asset\StatusController::class, 'destroy'])->name('statuses.destroy');

            Route::get('maintenance-types', [\App\Http\Controllers\Admin\Asset\MaintenanceTypeController::class, 'index'])->name('maintenance-types.index');
            Route::post('maintenance-types', [\App\Http\Controllers\Admin\Asset\MaintenanceTypeController::class, 'store'])->name('maintenance-types.store');
            Route::patch('maintenance-types/{type}', [\App\Http\Controllers\Admin\Asset\MaintenanceTypeController::class, 'update'])->name('maintenance-types.update');
            Route::patch('maintenance-types/{type}/toggle', [\App\Http\Controllers\Admin\Asset\MaintenanceTypeController::class, 'toggle'])->name('maintenance-types.toggle');
            Route::delete('maintenance-types/{type}', [\App\Http\Controllers\Admin\Asset\MaintenanceTypeController::class, 'destroy'])->name('maintenance-types.destroy');

            Route::resource('models', \App\Http\Controllers\Admin\Asset\ModelController::class)->parameters(['models' => 'model']);
            Route::post('models/{model}/discontinue', [\App\Http\Controllers\Admin\Asset\ModelController::class, 'discontinue'])->name('models.discontinue');

            Route::get('assets/export', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'export'])->name('assets.export');
            Route::get('assets/import', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'importForm'])->name('assets.import-form');
            Route::post('assets/import', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'import'])->name('assets.import');
            Route::get('assets/import/template', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'importTemplate'])->name('assets.import-template');

            // Bulk operations (multi-select assign / change / transfer / delete)
            Route::get('bulk', [\App\Http\Controllers\Admin\Asset\BulkController::class, 'index'])->name('bulk.index');
            Route::post('bulk/apply', [\App\Http\Controllers\Admin\Asset\BulkController::class, 'apply'])->name('bulk.apply');

            // Extended reports
            Route::get('reports/employee-assets', [\App\Http\Controllers\Admin\Asset\ReportController::class, 'employeeAssets'])->name('reports.employee-assets');
            Route::get('reports/dimension', [\App\Http\Controllers\Admin\Asset\ReportController::class, 'dimension'])->name('reports.dimension');
            Route::get('reports/asset/{asset}/history', [\App\Http\Controllers\Admin\Asset\ReportController::class, 'assetHistory'])->name('reports.asset-history');

            Route::resource('assets', \App\Http\Controllers\Admin\Asset\AssetController::class);
            Route::post('assets/{asset}/dispose', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'dispose'])->name('assets.dispose');
            Route::post('assets/{asset}/mark-lost', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'markLost'])->name('assets.mark-lost');
            Route::post('assets/{asset}/toggle-non-repairable', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'toggleNonRepairable'])->name('assets.toggle-non-repairable');

            // Repair Approval Management
            Route::get('repair', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'index'])->name('repair.index');
            Route::get('repair/create', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'create'])->name('repair.create');
            Route::post('repair', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'store'])->name('repair.store');
            Route::get('repair/{repair}', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'show'])->name('repair.show');
            Route::post('repair/{repair}/approve', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'approve'])->name('repair.approve');
            Route::post('repair/{repair}/reject', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'reject'])->name('repair.reject');
            Route::post('repair/{repair}/request-costing', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'requestCosting'])->name('repair.request-costing');
            Route::post('repair/{repair}/approve-costing', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'approveCosting'])->name('repair.approve-costing');
            Route::post('repair/{repair}/reject-costing', [\App\Http\Controllers\Admin\Asset\RepairController::class, 'rejectCosting'])->name('repair.reject-costing');

            Route::get('assignments', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'index'])->name('assignments.index');
            Route::get('assignments/export', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'export'])->name('assignments.export');
            Route::get('assignments/create', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'create'])->name('assignments.create');
            Route::post('assignments', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'store'])->name('assignments.store');
            Route::post('assignments/{assignment}/return', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'returnAsset'])->name('assignments.return');
            Route::post('assignments/transfer', [\App\Http\Controllers\Admin\Asset\AssignmentController::class, 'transfer'])->name('assignments.transfer');

            Route::get('maintenance/export', [\App\Http\Controllers\Admin\Asset\MaintenanceController::class, 'export'])->name('maintenance.export');
            Route::resource('maintenance', \App\Http\Controllers\Admin\Asset\MaintenanceController::class);

            Route::get('depreciation', [\App\Http\Controllers\Admin\Asset\DepreciationController::class, 'index'])->name('depreciation.index');
            Route::post('depreciation/post', [\App\Http\Controllers\Admin\Asset\DepreciationController::class, 'post'])->name('depreciation.post');
        });
    });
});

// ════════════════════════════════════════════════════════════════════════
// Employee Portal
// ════════════════════════════════════════════════════════════════════════
Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('/login', [EmpAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [EmpAuthController::class, 'login'])->name('signin');
    Route::post('/logout', [EmpAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth:employee', 'business'])->group(function () {
        Route::get('/dashboard', [EmpDashboardController::class, 'index'])->name('dashboard');
        Route::post('change-password', [EmpAuthController::class, 'changePassword'])->name('change-password');

        // Profile
        Route::get('profile', [EmpProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/edit', [EmpProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [EmpProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/photo', [EmpProfileController::class, 'uploadPhoto'])->name('profile.photo.upload');
        Route::delete('profile/photo', [EmpProfileController::class, 'removePhoto'])->name('profile.photo.remove');

        // Attendance
        Route::get('attendance', [EmpAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/punch', [EmpAttendanceController::class, 'punch'])->name('attendance.punch');

        // My referrals (candidates this employee referred + status)
        Route::get('referrals', [\App\Http\Controllers\Employee\ReferralController::class, 'index'])->name('referrals.index');

        // Internal helpdesk tickets (self-service)
        Route::get('tickets', [\App\Http\Controllers\Employee\TicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [\App\Http\Controllers\Employee\TicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [\App\Http\Controllers\Employee\TicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [\App\Http\Controllers\Employee\TicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/comment', [\App\Http\Controllers\Employee\TicketController::class, 'comment'])->name('tickets.comment');

        // Reimbursement claims (self-service)
        Route::get('reimbursements', [\App\Http\Controllers\Employee\ReimbursementController::class, 'index'])->name('reimbursements.index');
        Route::get('reimbursements/create', [\App\Http\Controllers\Employee\ReimbursementController::class, 'create'])->name('reimbursements.create');
        Route::post('reimbursements', [\App\Http\Controllers\Employee\ReimbursementController::class, 'store'])->name('reimbursements.store');
        Route::get('reimbursements/{reimbursement}', [\App\Http\Controllers\Employee\ReimbursementController::class, 'show'])->name('reimbursements.show');

        // My Documents (self-upload + verification status)
        Route::get('documents', [\App\Http\Controllers\Employee\DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [\App\Http\Controllers\Employee\DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/view', [\App\Http\Controllers\Employee\DocumentController::class, 'view'])->name('documents.view');
        Route::get('documents/{document}/download', [\App\Http\Controllers\Employee\DocumentController::class, 'download'])->name('documents.download');
        Route::delete('documents/{document}', [\App\Http\Controllers\Employee\DocumentController::class, 'destroy'])->name('documents.destroy');

        // Attendance regularization (self-service correction requests)
        Route::get('regularizations', [\App\Http\Controllers\Employee\RegularizationController::class, 'index'])->name('regularizations.index');
        Route::get('regularizations/create', [\App\Http\Controllers\Employee\RegularizationController::class, 'create'])->name('regularizations.create');
        Route::post('regularizations', [\App\Http\Controllers\Employee\RegularizationController::class, 'store'])->name('regularizations.store');
        Route::post('regularizations/{regularization}/cancel', [\App\Http\Controllers\Employee\RegularizationController::class, 'cancel'])->name('regularizations.cancel');

        // Leaves
        Route::get('leaves', [EmpLeaveController::class, 'index'])->name('leaves.index');
        Route::get('leaves/policy', [EmpLeaveController::class, 'policy'])->name('leaves.policy');
        Route::get('leaves/apply', [EmpLeaveController::class, 'create'])->name('leaves.create');
        Route::post('leaves', [EmpLeaveController::class, 'store'])->name('leaves.store');
        Route::get('leaves/{leaveRequest}', [EmpLeaveController::class, 'show'])->name('leaves.show');
        Route::post('leaves/{leaveRequest}/cancel', [EmpLeaveController::class, 'cancel'])->name('leaves.cancel');

        // My Budget — budgets sanctioned to this employee
        Route::get('budget', [\App\Http\Controllers\Employee\BudgetController::class, 'index'])->name('budget.index');
        Route::post('budget/{budget}/utilize', [\App\Http\Controllers\Employee\BudgetExpenseController::class, 'store'])->name('budget.utilize');

        // Team Leaves — Department Head approvals (scoped to departments they head)
        Route::get('team-leaves', [\App\Http\Controllers\Employee\TeamLeaveController::class, 'index'])->name('team-leaves.index');
        Route::post('team-leaves/{leaveRequest}/approve', [\App\Http\Controllers\Employee\TeamLeaveController::class, 'approve'])->name('team-leaves.approve');
        Route::post('team-leaves/{leaveRequest}/reject', [\App\Http\Controllers\Employee\TeamLeaveController::class, 'reject'])->name('team-leaves.reject');
        // Team Comp-Off — reporting-manager approvals
        Route::get('team-comp-off', [\App\Http\Controllers\Employee\TeamCompOffController::class, 'index'])->name('team-comp-off.index');
        Route::post('team-comp-off/{compOff}/approve', [\App\Http\Controllers\Employee\TeamCompOffController::class, 'approve'])->name('team-comp-off.approve');
        Route::post('team-comp-off/{compOff}/reject', [\App\Http\Controllers\Employee\TeamCompOffController::class, 'reject'])->name('team-comp-off.reject');

        // Comp-Off
        Route::get('comp-off', [\App\Http\Controllers\Employee\CompOffController::class, 'index'])->name('comp-off.index');
        Route::post('comp-off', [\App\Http\Controllers\Employee\CompOffController::class, 'store'])->name('comp-off.store');
        Route::delete('comp-off/{compOff}', [\App\Http\Controllers\Employee\CompOffController::class, 'cancel'])->name('comp-off.cancel');

        // Payslips
        Route::get('payslips', [EmpPayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}', [EmpPayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [EmpPayslipController::class, 'pdf'])->name('payslips.pdf');

        // Warnings
        Route::get('warnings', [EmpWarningController::class, 'index'])->name('warnings.index');
        Route::post('warnings/{warning}/acknowledge', [EmpWarningController::class, 'acknowledge'])->name('warnings.acknowledge');

        Route::get('penalties', [EmpPenaltyController::class, 'index'])->name('penalties.index');
        Route::get('penalties/{penalty}', [EmpPenaltyController::class, 'show'])->name('penalties.show');

        // Feedback
        Route::get('feedback', [EmpFeedbackController::class, 'index'])->name('feedback.index');
        Route::post('feedback', [EmpFeedbackController::class, 'store'])->name('feedback.store');

        // Performance
        Route::get('performance', [EmpPerformanceController::class, 'index'])->name('performance.index');

        // Appraisals (my increment history)
        Route::get('appraisals', [EmpAppraisalController::class, 'index'])->name('appraisals.index');
        Route::get('appraisals/{appraisal}', [EmpAppraisalController::class, 'show'])->name('appraisals.show');
        Route::get('appraisals/{appraisal}/pdf', [EmpAppraisalController::class, 'pdf'])->name('appraisals.pdf');
    });
});
