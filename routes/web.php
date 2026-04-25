<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\Hr\AppraisalController as HrAppraisalController;
use App\Http\Controllers\Admin\Hr\DashboardController as HrDashboardController;
use App\Http\Controllers\Admin\Hr\AppraisalCycleController as HrAppraisalCycleController;
use App\Http\Controllers\Admin\Hr\AttendanceController as HrAttendanceController;
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
use App\Http\Controllers\Admin\Hr\ShiftController as HrShiftController;
use App\Http\Controllers\Admin\Hr\WarningController as HrWarningController;
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
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

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

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // ════════════════════════════════════════════════════════════════
        // HR Module
        // ════════════════════════════════════════════════════════════════
        Route::prefix('hr')->name('hr.')->group(function () {
            // HR Dashboard
            Route::get('dashboard', [HrDashboardController::class, 'index'])->name('dashboard');

            // Employees
            Route::resource('employees', HrEmployeeController::class);
            Route::post('employees/{employee}/reset-password', [HrEmployeeController::class, 'resetPassword'])->name('employees.reset-password');

            // Departments & Designations
            Route::resource('departments', HrDepartmentController::class)->except(['show']);
            Route::resource('designations', HrDesignationController::class)->except(['show']);

            // Shifts
            Route::resource('shifts', HrShiftController::class)->except(['show']);

            // Holidays
            Route::resource('holidays', HrHolidayController::class)->except(['show']);

            // Leave Types
            Route::resource('leave-types', HrLeaveTypeController::class)->except(['show'])->parameters(['leave-types' => 'leaveType']);

            // Attendance
            Route::get('attendance', [HrAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('attendance/monthly', [HrAttendanceController::class, 'monthly'])->name('attendance.monthly');
            Route::get('attendance/create', [HrAttendanceController::class, 'create'])->name('attendance.create');
            Route::post('attendance', [HrAttendanceController::class, 'store'])->name('attendance.store');
            Route::get('attendance/import', [HrAttendanceController::class, 'importForm'])->name('attendance.import-form');
            Route::post('attendance/import', [HrAttendanceController::class, 'import'])->name('attendance.import');

            // Leaves
            Route::get('leaves', [HrLeaveController::class, 'index'])->name('leaves.index');
            Route::get('leave-balances', [HrLeaveBalanceController::class, 'index'])->name('leave-balances.index');
            Route::post('leave-balances/bulk-allocate', [HrLeaveBalanceController::class, 'bulkAllocate'])->name('leave-balances.bulk-allocate');
            Route::get('leave-balances/{employee}', [HrLeaveBalanceController::class, 'edit'])->name('leave-balances.edit');
            Route::put('leave-balances/{employee}', [HrLeaveBalanceController::class, 'update'])->name('leave-balances.update');
            Route::get('leaves/{leaveRequest}', [HrLeaveController::class, 'show'])->name('leaves.show');
            Route::post('leaves/{leaveRequest}/approve', [HrLeaveController::class, 'approve'])->name('leaves.approve');
            Route::post('leaves/{leaveRequest}/reject', [HrLeaveController::class, 'reject'])->name('leaves.reject');

            // Payroll
            Route::get('payroll', [HrPayrollController::class, 'index'])->name('payroll.index');
            Route::get('payroll/generate', [HrPayrollController::class, 'generateForm'])->name('payroll.generate-form');
            Route::post('payroll/generate', [HrPayrollController::class, 'generate'])->name('payroll.generate');
            Route::get('payroll/{payslip}', [HrPayrollController::class, 'show'])->name('payroll.show');
            Route::get('payroll/{payslip}/pdf', [HrPayrollController::class, 'pdf'])->name('payroll.pdf');
            Route::post('payroll/{payslip}/mark-paid', [HrPayrollController::class, 'markPaid'])->name('payroll.mark-paid');
            Route::post('payroll/preview-structure', [HrPayrollController::class, 'previewStructure'])->name('payroll.preview-structure');
            Route::get('employees/{employee}/salary', [HrPayrollController::class, 'salaryForm'])->name('salary.form');
            Route::post('employees/{employee}/salary', [HrPayrollController::class, 'salaryStore'])->name('salary.store');

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

            Route::resource('models', \App\Http\Controllers\Admin\Asset\ModelController::class)->parameters(['models' => 'model']);
            Route::post('models/{model}/discontinue', [\App\Http\Controllers\Admin\Asset\ModelController::class, 'discontinue'])->name('models.discontinue');

            Route::get('assets/export', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'export'])->name('assets.export');
            Route::resource('assets', \App\Http\Controllers\Admin\Asset\AssetController::class);
            Route::post('assets/{asset}/dispose', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'dispose'])->name('assets.dispose');
            Route::post('assets/{asset}/mark-lost', [\App\Http\Controllers\Admin\Asset\AssetController::class, 'markLost'])->name('assets.mark-lost');

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

    Route::middleware('auth:employee')->group(function () {
        Route::get('/dashboard', [EmpDashboardController::class, 'index'])->name('dashboard');
        Route::post('change-password', [EmpAuthController::class, 'changePassword'])->name('change-password');

        // Profile
        Route::get('profile', [EmpProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/edit', [EmpProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [EmpProfileController::class, 'update'])->name('profile.update');

        // Attendance
        Route::get('attendance', [EmpAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/punch', [EmpAttendanceController::class, 'punch'])->name('attendance.punch');

        // Leaves
        Route::get('leaves', [EmpLeaveController::class, 'index'])->name('leaves.index');
        Route::get('leaves/apply', [EmpLeaveController::class, 'create'])->name('leaves.create');
        Route::post('leaves', [EmpLeaveController::class, 'store'])->name('leaves.store');
        Route::get('leaves/{leaveRequest}', [EmpLeaveController::class, 'show'])->name('leaves.show');
        Route::post('leaves/{leaveRequest}/cancel', [EmpLeaveController::class, 'cancel'])->name('leaves.cancel');

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
