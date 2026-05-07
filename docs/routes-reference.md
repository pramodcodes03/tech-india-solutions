# Routes Reference

> Auto-generated on 2026-05-07 00:00:01

## Admin Admin-Users

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/admin-users/create | admin.admin-users.create | AdminUserController@create |
| DELETE | /admin/admin-users/{admin_user} | admin.admin-users.destroy | AdminUserController@destroy |
| GET|HEAD | /admin/admin-users/{admin_user}/edit | admin.admin-users.edit | AdminUserController@edit |
| GET|HEAD | /admin/admin-users | admin.admin-users.index | AdminUserController@index |
| GET|HEAD | /admin/admin-users/{admin_user} | admin.admin-users.show | AdminUserController@show |
| POST | /admin/admin-users | admin.admin-users.store | AdminUserController@store |
| PATCH | /admin/admin-users/{admin_user}/toggle-status | admin.admin-users.toggle-status | AdminUserController@toggleStatus |
| PUT|PATCH | /admin/admin-users/{admin_user} | admin.admin-users.update | AdminUserController@update |

## Admin Assets

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/assets/assets/create | admin.assets.assets.create | AssetController@create |
| DELETE | /admin/assets/assets/{asset} | admin.assets.assets.destroy | AssetController@destroy |
| POST | /admin/assets/assets/{asset}/dispose | admin.assets.assets.dispose | AssetController@dispose |
| GET|HEAD | /admin/assets/assets/{asset}/edit | admin.assets.assets.edit | AssetController@edit |
| GET|HEAD | /admin/assets/assets/export | admin.assets.assets.export | AssetController@export |
| GET|HEAD | /admin/assets/assets | admin.assets.assets.index | AssetController@index |
| POST | /admin/assets/assets/{asset}/mark-lost | admin.assets.assets.mark-lost | AssetController@markLost |
| GET|HEAD | /admin/assets/assets/{asset} | admin.assets.assets.show | AssetController@show |
| POST | /admin/assets/assets | admin.assets.assets.store | AssetController@store |
| PUT|PATCH | /admin/assets/assets/{asset} | admin.assets.assets.update | AssetController@update |
| GET|HEAD | /admin/assets/assignments/create | admin.assets.assignments.create | AssignmentController@create |
| GET|HEAD | /admin/assets/assignments/export | admin.assets.assignments.export | AssignmentController@export |
| GET|HEAD | /admin/assets/assignments | admin.assets.assignments.index | AssignmentController@index |
| POST | /admin/assets/assignments/{assignment}/return | admin.assets.assignments.return | AssignmentController@returnAsset |
| POST | /admin/assets/assignments | admin.assets.assignments.store | AssignmentController@store |
| POST | /admin/assets/assignments/transfer | admin.assets.assignments.transfer | AssignmentController@transfer |
| GET|HEAD | /admin/assets/categories/create | admin.assets.categories.create | CategoryController@create |
| DELETE | /admin/assets/categories/{category} | admin.assets.categories.destroy | CategoryController@destroy |
| GET|HEAD | /admin/assets/categories/{category}/edit | admin.assets.categories.edit | CategoryController@edit |
| GET|HEAD | /admin/assets/categories | admin.assets.categories.index | CategoryController@index |
| POST | /admin/assets/categories | admin.assets.categories.store | CategoryController@store |
| PUT|PATCH | /admin/assets/categories/{category} | admin.assets.categories.update | CategoryController@update |
| GET|HEAD | /admin/assets/dashboard | admin.assets.dashboard | DashboardController@index |
| GET|HEAD | /admin/assets/depreciation | admin.assets.depreciation.index | DepreciationController@index |
| POST | /admin/assets/depreciation/post | admin.assets.depreciation.post | DepreciationController@post |
| GET|HEAD | /admin/assets/locations/create | admin.assets.locations.create | LocationController@create |
| DELETE | /admin/assets/locations/{location} | admin.assets.locations.destroy | LocationController@destroy |
| GET|HEAD | /admin/assets/locations/{location}/edit | admin.assets.locations.edit | LocationController@edit |
| GET|HEAD | /admin/assets/locations | admin.assets.locations.index | LocationController@index |
| POST | /admin/assets/locations | admin.assets.locations.store | LocationController@store |
| PUT|PATCH | /admin/assets/locations/{location} | admin.assets.locations.update | LocationController@update |
| GET|HEAD | /admin/assets/maintenance/create | admin.assets.maintenance.create | MaintenanceController@create |
| DELETE | /admin/assets/maintenance/{maintenance} | admin.assets.maintenance.destroy | MaintenanceController@destroy |
| GET|HEAD | /admin/assets/maintenance/{maintenance}/edit | admin.assets.maintenance.edit | MaintenanceController@edit |
| GET|HEAD | /admin/assets/maintenance/export | admin.assets.maintenance.export | MaintenanceController@export |
| GET|HEAD | /admin/assets/maintenance | admin.assets.maintenance.index | MaintenanceController@index |
| GET|HEAD | /admin/assets/maintenance/{maintenance} | admin.assets.maintenance.show | MaintenanceController@show |
| POST | /admin/assets/maintenance | admin.assets.maintenance.store | MaintenanceController@store |
| PUT|PATCH | /admin/assets/maintenance/{maintenance} | admin.assets.maintenance.update | MaintenanceController@update |
| GET|HEAD | /admin/assets/models/create | admin.assets.models.create | ModelController@create |
| DELETE | /admin/assets/models/{model} | admin.assets.models.destroy | ModelController@destroy |
| POST | /admin/assets/models/{model}/discontinue | admin.assets.models.discontinue | ModelController@discontinue |
| GET|HEAD | /admin/assets/models/{model}/edit | admin.assets.models.edit | ModelController@edit |
| GET|HEAD | /admin/assets/models | admin.assets.models.index | ModelController@index |
| GET|HEAD | /admin/assets/models/{model} | admin.assets.models.show | ModelController@show |
| POST | /admin/assets/models | admin.assets.models.store | ModelController@store |
| PUT|PATCH | /admin/assets/models/{model} | admin.assets.models.update | ModelController@update |

## Admin Businesses

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| DELETE | /admin/businesses/{business}/admins/{admin} | admin.businesses.admins.destroy | BusinessController@destroyAdmin |
| POST | /admin/businesses/{business}/admins | admin.businesses.admins.store | BusinessController@storeAdmin |
| PUT | /admin/businesses/{business}/admins/{admin} | admin.businesses.admins.update | BusinessController@updateAdmin |
| GET|HEAD | /admin/businesses/create | admin.businesses.create | BusinessController@create |
| DELETE | /admin/businesses/{business} | admin.businesses.destroy | BusinessController@destroy |
| GET|HEAD | /admin/businesses/{business}/edit | admin.businesses.edit | BusinessController@edit |
| GET|HEAD | /admin/businesses | admin.businesses.index | BusinessController@index |
| GET|HEAD | /admin/select-business | admin.businesses.select | BusinessController@selector |
| GET|HEAD | /admin/businesses/{business} | admin.businesses.show | BusinessController@show |
| POST | /admin/businesses | admin.businesses.store | BusinessController@store |
| POST | /admin/businesses/{business}/switch | admin.businesses.switch | BusinessController@switch |
| PUT|PATCH | /admin/businesses/{business} | admin.businesses.update | BusinessController@update |

## Admin Categories

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/categories/create | admin.categories.create | CategoryController@create |
| DELETE | /admin/categories/{category} | admin.categories.destroy | CategoryController@destroy |
| GET|HEAD | /admin/categories/{category}/edit | admin.categories.edit | CategoryController@edit |
| GET|HEAD | /admin/categories | admin.categories.index | CategoryController@index |
| POST | /admin/categories | admin.categories.store | CategoryController@store |
| PATCH | /admin/categories/{category}/toggle-status | admin.categories.toggle-status | CategoryController@toggleStatus |
| PUT|PATCH | /admin/categories/{category} | admin.categories.update | CategoryController@update |

## Admin Change-Password

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/change-password | admin.change-password | AdminUserController@changePassword |

## Admin Cities

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/cities/create | admin.cities.create | CityController@create |
| DELETE | /admin/cities/{city} | admin.cities.destroy | CityController@destroy |
| GET|HEAD | /admin/cities/{city}/edit | admin.cities.edit | CityController@edit |
| GET|HEAD | /admin/cities | admin.cities.index | CityController@index |
| GET|HEAD | /admin/cities/{city} | admin.cities.show | CityController@show |
| POST | /admin/cities | admin.cities.store | CityController@store |
| PATCH | /admin/cities/{city}/toggle-status | admin.cities.toggle-status | CityController@toggleStatus |
| PUT|PATCH | /admin/cities/{city} | admin.cities.update | CityController@update |

## Admin Customers

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/customers/create | admin.customers.create | CustomerController@create |
| DELETE | /admin/customers/{customer} | admin.customers.destroy | CustomerController@destroy |
| GET|HEAD | /admin/customers/{customer}/edit | admin.customers.edit | CustomerController@edit |
| GET|HEAD | /admin/customers | admin.customers.index | CustomerController@index |
| GET|HEAD | /admin/customers/{customer} | admin.customers.show | CustomerController@show |
| POST | /admin/customers | admin.customers.store | CustomerController@store |
| PATCH | /admin/customers/{customer}/toggle-status | admin.customers.toggle-status | CustomerController@toggleStatus |
| PUT|PATCH | /admin/customers/{customer} | admin.customers.update | CustomerController@update |

## Admin Dashboard

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/dashboard | admin.dashboard | AuthController@dashboard |

## Admin Dashboards

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/dashboards/customers | admin.dashboards.customers | DashboardsController@customers |
| GET|HEAD | /admin/dashboards/executive | admin.dashboards.executive | DashboardsController@executive |
| GET|HEAD | /admin/dashboards/inventory | admin.dashboards.inventory | DashboardsController@inventory |
| GET|HEAD | /admin/dashboards/purchase | admin.dashboards.purchase | DashboardsController@purchase |
| GET|HEAD | /admin/dashboards/sales | admin.dashboards.sales | DashboardsController@sales |
| GET|HEAD | /admin/dashboards/service | admin.dashboards.service | DashboardsController@service |

## Admin Expense-Categories

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/expense-categories/create | admin.expense-categories.create | ExpenseCategoryController@create |
| DELETE | /admin/expense-categories/{expense_category} | admin.expense-categories.destroy | ExpenseCategoryController@destroy |
| GET|HEAD | /admin/expense-categories/{expense_category}/edit | admin.expense-categories.edit | ExpenseCategoryController@edit |
| GET|HEAD | /admin/expense-categories | admin.expense-categories.index | ExpenseCategoryController@index |
| GET|HEAD | /admin/expense-categories/{expense_category} | admin.expense-categories.show | ExpenseCategoryController@show |
| POST | /admin/expense-categories | admin.expense-categories.store | ExpenseCategoryController@store |
| DELETE | /admin/expense-categories/{expense_category}/subcategories/{subcategory} | admin.expense-categories.subcategories.destroy | ExpenseCategoryController@destroySubcategory |
| POST | /admin/expense-categories/{expense_category}/subcategories | admin.expense-categories.subcategories.store | ExpenseCategoryController@storeSubcategory |
| PUT | /admin/expense-categories/{expense_category}/subcategories/{subcategory} | admin.expense-categories.subcategories.update | ExpenseCategoryController@updateSubcategory |
| PUT|PATCH | /admin/expense-categories/{expense_category} | admin.expense-categories.update | ExpenseCategoryController@update |

## Admin Expenses

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/expenses/create | admin.expenses.create | ExpenseController@create |
| DELETE | /admin/expenses/{expense} | admin.expenses.destroy | ExpenseController@destroy |
| GET|HEAD | /admin/expenses/{expense}/edit | admin.expenses.edit | ExpenseController@edit |
| GET|HEAD | /admin/expenses | admin.expenses.index | ExpenseController@index |
| POST | /admin/expenses/{expense}/mark-paid | admin.expenses.mark-paid | ExpenseController@markPaid |
| GET|HEAD | /admin/expenses/{expense}/pdf | admin.expenses.pdf | ExpenseController@pdf |
| GET|HEAD | /admin/expenses/{expense} | admin.expenses.show | ExpenseController@show |
| POST | /admin/expenses | admin.expenses.store | ExpenseController@store |
| GET|HEAD | /admin/expense-categories/{expense_category}/subcategories-json | admin.expenses.subcategories.json | ExpenseController@subcategories |
| PUT|PATCH | /admin/expenses/{expense} | admin.expenses.update | ExpenseController@update |

## Admin Hr

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| DELETE | /admin/hr/appraisals/{appraisal} | admin.hr.appraisals.destroy | AppraisalController@destroy |
| GET|HEAD | /admin/hr/appraisals/{appraisal}/edit | admin.hr.appraisals.edit | AppraisalController@edit |
| GET|HEAD | /admin/hr/appraisals | admin.hr.appraisals.index | AppraisalController@index |
| GET|HEAD | /admin/hr/appraisals/{appraisal}/pdf | admin.hr.appraisals.pdf | AppraisalController@pdf |
| GET|HEAD | /admin/hr/appraisals/{appraisal} | admin.hr.appraisals.show | AppraisalController@show |
| PUT | /admin/hr/appraisals/{appraisal} | admin.hr.appraisals.update | AppraisalController@update |
| GET|HEAD | /admin/hr/attendance/create | admin.hr.attendance.create | AttendanceController@create |
| POST | /admin/hr/attendance/import | admin.hr.attendance.import | AttendanceController@import |
| GET|HEAD | /admin/hr/attendance/import | admin.hr.attendance.import-form | AttendanceController@importForm |
| GET|HEAD | /admin/hr/attendance | admin.hr.attendance.index | AttendanceController@index |
| GET|HEAD | /admin/hr/attendance/monthly | admin.hr.attendance.monthly | AttendanceController@monthly |
| POST | /admin/hr/attendance | admin.hr.attendance.store | AttendanceController@store |
| POST | /admin/hr/bank-edit-requests/{bankEditRequest}/approve | admin.hr.bank-edit-requests.approve | BankEditRequestController@approve |
| GET|HEAD | /admin/hr/bank-edit-requests | admin.hr.bank-edit-requests.index | BankEditRequestController@index |
| POST | /admin/hr/bank-edit-requests/{bankEditRequest}/reject | admin.hr.bank-edit-requests.reject | BankEditRequestController@reject |
| GET|HEAD | /admin/hr/dashboard | admin.hr.dashboard | DashboardController@index |
| GET|HEAD | /admin/hr/departments/create | admin.hr.departments.create | DepartmentController@create |
| DELETE | /admin/hr/departments/{department} | admin.hr.departments.destroy | DepartmentController@destroy |
| GET|HEAD | /admin/hr/departments/{department}/edit | admin.hr.departments.edit | DepartmentController@edit |
| GET|HEAD | /admin/hr/departments | admin.hr.departments.index | DepartmentController@index |
| POST | /admin/hr/departments | admin.hr.departments.store | DepartmentController@store |
| PUT|PATCH | /admin/hr/departments/{department} | admin.hr.departments.update | DepartmentController@update |
| GET|HEAD | /admin/hr/designations/create | admin.hr.designations.create | DesignationController@create |
| DELETE | /admin/hr/designations/{designation} | admin.hr.designations.destroy | DesignationController@destroy |
| GET|HEAD | /admin/hr/designations/{designation}/edit | admin.hr.designations.edit | DesignationController@edit |
| GET|HEAD | /admin/hr/designations | admin.hr.designations.index | DesignationController@index |
| POST | /admin/hr/designations | admin.hr.designations.store | DesignationController@store |
| PUT|PATCH | /admin/hr/designations/{designation} | admin.hr.designations.update | DesignationController@update |
| POST | /admin/hr/employees/{employee}/bank-edit-requests | admin.hr.employees.bank-edit-requests.store | BankEditRequestController@store |
| GET|HEAD | /admin/hr/employees/create | admin.hr.employees.create | EmployeeController@create |
| DELETE | /admin/hr/employees/{employee} | admin.hr.employees.destroy | EmployeeController@destroy |
| GET|HEAD | /admin/hr/employees/{employee}/edit | admin.hr.employees.edit | EmployeeController@edit |
| GET|HEAD | /admin/hr/employees/{employee}/increments/create | admin.hr.employees.increments.create | IncrementController@create |
| POST | /admin/hr/employees/{employee}/increments | admin.hr.employees.increments.store | IncrementController@store |
| GET|HEAD | /admin/hr/employees | admin.hr.employees.index | EmployeeController@index |
| POST | /admin/hr/employees/{employee}/reset-password | admin.hr.employees.reset-password | EmployeeController@resetPassword |
| GET|HEAD | /admin/hr/employees/{employee} | admin.hr.employees.show | EmployeeController@show |
| POST | /admin/hr/employees | admin.hr.employees.store | EmployeeController@store |
| PUT|PATCH | /admin/hr/employees/{employee} | admin.hr.employees.update | EmployeeController@update |
| GET|HEAD | /admin/hr/feedback | admin.hr.feedback.index | FeedbackController@index |
| GET|HEAD | /admin/hr/feedback/{feedback} | admin.hr.feedback.show | FeedbackController@show |
| GET|HEAD | /admin/hr/holidays/create | admin.hr.holidays.create | HolidayController@create |
| DELETE | /admin/hr/holidays/{holiday} | admin.hr.holidays.destroy | HolidayController@destroy |
| GET|HEAD | /admin/hr/holidays/{holiday}/edit | admin.hr.holidays.edit | HolidayController@edit |
| GET|HEAD | /admin/hr/holidays | admin.hr.holidays.index | HolidayController@index |
| POST | /admin/hr/holidays | admin.hr.holidays.store | HolidayController@store |
| PUT|PATCH | /admin/hr/holidays/{holiday} | admin.hr.holidays.update | HolidayController@update |
| POST | /admin/hr/leave-balances/bulk-allocate | admin.hr.leave-balances.bulk-allocate | LeaveBalanceController@bulkAllocate |
| GET|HEAD | /admin/hr/leave-balances/{employee} | admin.hr.leave-balances.edit | LeaveBalanceController@edit |
| GET|HEAD | /admin/hr/leave-balances | admin.hr.leave-balances.index | LeaveBalanceController@index |
| PUT | /admin/hr/leave-balances/{employee} | admin.hr.leave-balances.update | LeaveBalanceController@update |
| GET|HEAD | /admin/hr/leave-types/create | admin.hr.leave-types.create | LeaveTypeController@create |
| DELETE | /admin/hr/leave-types/{leaveType} | admin.hr.leave-types.destroy | LeaveTypeController@destroy |
| GET|HEAD | /admin/hr/leave-types/{leaveType}/edit | admin.hr.leave-types.edit | LeaveTypeController@edit |
| GET|HEAD | /admin/hr/leave-types | admin.hr.leave-types.index | LeaveTypeController@index |
| POST | /admin/hr/leave-types | admin.hr.leave-types.store | LeaveTypeController@store |
| PUT|PATCH | /admin/hr/leave-types/{leaveType} | admin.hr.leave-types.update | LeaveTypeController@update |
| POST | /admin/hr/leaves/{leaveRequest}/approve | admin.hr.leaves.approve | LeaveController@approve |
| GET|HEAD | /admin/hr/leaves | admin.hr.leaves.index | LeaveController@index |
| POST | /admin/hr/leaves/{leaveRequest}/reject | admin.hr.leaves.reject | LeaveController@reject |
| GET|HEAD | /admin/hr/leaves/{leaveRequest} | admin.hr.leaves.show | LeaveController@show |
| POST | /admin/hr/payroll/approvals/{salaryStructure}/approve | admin.hr.payroll.approvals.approve | PayrollController@approveStructure |
| GET|HEAD | /admin/hr/payroll/approvals | admin.hr.payroll.approvals.index | PayrollController@pendingApprovals |
| POST | /admin/hr/payroll/approvals/{salaryStructure}/reject | admin.hr.payroll.approvals.reject | PayrollController@rejectStructure |
| POST | /admin/hr/payroll/generate | admin.hr.payroll.generate | PayrollController@generate |
| GET|HEAD | /admin/hr/payroll/generate | admin.hr.payroll.generate-form | PayrollController@generateForm |
| GET|HEAD | /admin/hr/payroll | admin.hr.payroll.index | PayrollController@index |
| POST | /admin/hr/payroll/{payslip}/mark-paid | admin.hr.payroll.mark-paid | PayrollController@markPaid |
| GET|HEAD | /admin/hr/payroll/{payslip}/pdf | admin.hr.payroll.pdf | PayrollController@pdf |
| POST | /admin/hr/payroll/preview-structure | admin.hr.payroll.preview-structure | PayrollController@previewStructure |
| GET|HEAD | /admin/hr/payroll/{payslip} | admin.hr.payroll.show | PayrollController@show |
| GET|HEAD | /admin/hr/penalties/create | admin.hr.penalties.create | PenaltyController@create |
| GET|HEAD | /admin/hr/penalties | admin.hr.penalties.index | PenaltyController@index |
| POST | /admin/hr/penalties/{penalty}/reduce | admin.hr.penalties.reduce | PenaltyController@reduce |
| POST | /admin/hr/penalties | admin.hr.penalties.store | PenaltyController@store |
| GET|HEAD | /admin/hr/penalty-types | admin.hr.penalty-types.index | PenaltyController@types |
| POST | /admin/hr/penalty-types | admin.hr.penalty-types.store | PenaltyController@storeType |
| PUT | /admin/hr/penalty-types/{type} | admin.hr.penalty-types.update | PenaltyController@updateType |
| GET|HEAD | /admin/hr/employees/{employee}/salary | admin.hr.salary.form | PayrollController@salaryForm |
| POST | /admin/hr/employees/{employee}/salary | admin.hr.salary.store | PayrollController@salaryStore |
| GET|HEAD | /admin/hr/shifts/create | admin.hr.shifts.create | ShiftController@create |
| DELETE | /admin/hr/shifts/{shift} | admin.hr.shifts.destroy | ShiftController@destroy |
| GET|HEAD | /admin/hr/shifts/{shift}/edit | admin.hr.shifts.edit | ShiftController@edit |
| GET|HEAD | /admin/hr/shifts | admin.hr.shifts.index | ShiftController@index |
| POST | /admin/hr/shifts | admin.hr.shifts.store | ShiftController@store |
| PUT|PATCH | /admin/hr/shifts/{shift} | admin.hr.shifts.update | ShiftController@update |
| GET|HEAD | /admin/hr/warnings/create | admin.hr.warnings.create | WarningController@create |
| GET|HEAD | /admin/hr/warnings | admin.hr.warnings.index | WarningController@index |
| GET|HEAD | /admin/hr/warnings/{warning} | admin.hr.warnings.show | WarningController@show |
| POST | /admin/hr/warnings | admin.hr.warnings.store | WarningController@store |
| POST | /admin/hr/warnings/{warning}/withdraw | admin.hr.warnings.withdraw | WarningController@withdraw |

## Admin Inbox

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/inbox | admin.inbox.index | InboxController@index |
| POST | /admin/inbox/mark-all-read | admin.inbox.mark-all-read | InboxController@markAllRead |
| GET|HEAD | /admin/inbox/open/{adminNotification} | admin.inbox.open | InboxController@open |
| GET|HEAD | /admin/inbox/unread-count | admin.inbox.unread-count | InboxController@unreadCount |

## Admin Inventory

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/inventory/adjust | admin.inventory.adjust | InventoryController@adjust |
| GET|HEAD | /admin/inventory | admin.inventory.index | InventoryController@index |
| GET|HEAD | /admin/inventory/low-stock | admin.inventory.low-stock | InventoryController@lowStock |
| GET|HEAD | /admin/inventory/movements | admin.inventory.movements | InventoryController@movements |
| POST | /admin/inventory/adjust | admin.inventory.store-adjustment | InventoryController@storeAdjustment |

## Admin Invoices

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/invoices/create | admin.invoices.create | InvoiceController@create |
| DELETE | /admin/invoices/{invoice} | admin.invoices.destroy | InvoiceController@destroy |
| GET|HEAD | /admin/invoices/{invoice}/edit | admin.invoices.edit | InvoiceController@edit |
| GET|HEAD | /admin/invoices | admin.invoices.index | InvoiceController@index |
| GET|HEAD | /admin/invoices/{invoice}/pdf | admin.invoices.pdf | InvoiceController@pdf |
| GET|HEAD | /admin/invoices/{invoice} | admin.invoices.show | InvoiceController@show |
| POST | /admin/invoices | admin.invoices.store | InvoiceController@store |
| PUT|PATCH | /admin/invoices/{invoice} | admin.invoices.update | InvoiceController@update |

## Admin Leads

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/leads/{lead}/convert | admin.leads.convert | LeadController@convertToCustomer |
| GET|HEAD | /admin/leads/create | admin.leads.create | LeadController@create |
| DELETE | /admin/leads/{lead} | admin.leads.destroy | LeadController@destroy |
| GET|HEAD | /admin/leads/{lead}/edit | admin.leads.edit | LeadController@edit |
| GET|HEAD | /admin/leads | admin.leads.index | LeadController@index |
| GET|HEAD | /admin/leads/kanban | admin.leads.kanban | LeadController@kanban |
| GET|HEAD | /admin/leads/{lead} | admin.leads.show | LeadController@show |
| POST | /admin/leads | admin.leads.store | LeadController@store |
| PUT|PATCH | /admin/leads/{lead} | admin.leads.update | LeadController@update |
| PATCH | /admin/leads/{lead}/status | admin.leads.update-status | LeadController@updateStatus |

## Admin Locations

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/locations/cities | admin.locations.cities | StateController@cities |

## Admin Login

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/login | admin.login | AuthController@showLoginForm |

## Admin Logout

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/logout | admin.logout | AuthController@logout |

## Admin Notifications

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/notifications | admin.notifications.index | NotificationController@index |
| GET|HEAD | /admin/notifications/logs | admin.notifications.logs | NotificationController@logs |
| POST | /admin/notifications/test | admin.notifications.test | NotificationController@test |
| PUT | /admin/notifications | admin.notifications.update | NotificationController@update |

## Admin Payments

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/payments/create | admin.payments.create | PaymentController@create |
| DELETE | /admin/payments/{payment} | admin.payments.destroy | PaymentController@destroy |
| GET|HEAD | /admin/payments | admin.payments.index | PaymentController@index |
| GET|HEAD | /admin/payments/{payment} | admin.payments.show | PaymentController@show |
| POST | /admin/payments | admin.payments.store | PaymentController@store |

## Admin Products

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/products/create | admin.products.create | ProductController@create |
| DELETE | /admin/products/{product} | admin.products.destroy | ProductController@destroy |
| GET|HEAD | /admin/products/{product}/edit | admin.products.edit | ProductController@edit |
| GET|HEAD | /admin/products | admin.products.index | ProductController@index |
| GET|HEAD | /admin/products/{product} | admin.products.show | ProductController@show |
| POST | /admin/products | admin.products.store | ProductController@store |
| PATCH | /admin/products/{product}/toggle-status | admin.products.toggle-status | ProductController@toggleStatus |
| PUT|PATCH | /admin/products/{product} | admin.products.update | ProductController@update |

## Admin Proforma-Invoices

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/proforma-invoices/{proforma_invoice}/clone | admin.proforma-invoices.clone | ProformaInvoiceController@clone |
| POST | /admin/proforma-invoices/{proforma_invoice}/convert-to-invoice | admin.proforma-invoices.convert-to-invoice | ProformaInvoiceController@convertToInvoice |
| GET|HEAD | /admin/proforma-invoices/create | admin.proforma-invoices.create | ProformaInvoiceController@create |
| DELETE | /admin/proforma-invoices/{proforma_invoice} | admin.proforma-invoices.destroy | ProformaInvoiceController@destroy |
| GET|HEAD | /admin/proforma-invoices/{proforma_invoice}/edit | admin.proforma-invoices.edit | ProformaInvoiceController@edit |
| GET|HEAD | /admin/proforma-invoices | admin.proforma-invoices.index | ProformaInvoiceController@index |
| GET|HEAD | /admin/proforma-invoices/{proforma_invoice}/pdf | admin.proforma-invoices.pdf | ProformaInvoiceController@pdf |
| GET|HEAD | /admin/proforma-invoices/{proforma_invoice} | admin.proforma-invoices.show | ProformaInvoiceController@show |
| POST | /admin/proforma-invoices | admin.proforma-invoices.store | ProformaInvoiceController@store |
| PUT|PATCH | /admin/proforma-invoices/{proforma_invoice} | admin.proforma-invoices.update | ProformaInvoiceController@update |
| PATCH | /admin/proforma-invoices/{proforma_invoice}/status | admin.proforma-invoices.update-status | ProformaInvoiceController@updateStatus |

## Admin Purchase-Orders

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/purchase-orders/create | admin.purchase-orders.create | PurchaseOrderController@create |
| DELETE | /admin/purchase-orders/{purchase_order} | admin.purchase-orders.destroy | PurchaseOrderController@destroy |
| GET|HEAD | /admin/purchase-orders/{purchase_order}/edit | admin.purchase-orders.edit | PurchaseOrderController@edit |
| GET|HEAD | /admin/purchase-orders | admin.purchase-orders.index | PurchaseOrderController@index |
| POST | /admin/purchase-orders/{purchase_order}/receive | admin.purchase-orders.receive | PurchaseOrderController@receiveGoods |
| GET|HEAD | /admin/purchase-orders/{purchase_order} | admin.purchase-orders.show | PurchaseOrderController@show |
| POST | /admin/purchase-orders | admin.purchase-orders.store | PurchaseOrderController@store |
| PUT|PATCH | /admin/purchase-orders/{purchase_order} | admin.purchase-orders.update | PurchaseOrderController@update |

## Admin Quotations

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/quotations/{quotation}/clone | admin.quotations.clone | QuotationController@clone |
| POST | /admin/quotations/{quotation}/convert-to-order | admin.quotations.convert-to-order | QuotationController@convertToOrder |
| GET|HEAD | /admin/quotations/create | admin.quotations.create | QuotationController@create |
| DELETE | /admin/quotations/{quotation} | admin.quotations.destroy | QuotationController@destroy |
| GET|HEAD | /admin/quotations/{quotation}/edit | admin.quotations.edit | QuotationController@edit |
| GET|HEAD | /admin/quotations | admin.quotations.index | QuotationController@index |
| GET|HEAD | /admin/quotations/{quotation}/pdf | admin.quotations.pdf | QuotationController@pdf |
| GET|HEAD | /admin/quotations/{quotation} | admin.quotations.show | QuotationController@show |
| POST | /admin/quotations | admin.quotations.store | QuotationController@store |
| PUT|PATCH | /admin/quotations/{quotation} | admin.quotations.update | QuotationController@update |
| PATCH | /admin/quotations/{quotation}/status | admin.quotations.update-status | QuotationController@updateStatus |

## Admin Reports

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/reports/customers | admin.reports.customers | ReportController@customers |
| GET|HEAD | /admin/reports/{type}/export-excel | admin.reports.export-excel | ReportController@exportExcel |
| GET|HEAD | /admin/reports/{type}/export-pdf | admin.reports.export-pdf | ReportController@exportPdf |
| GET|HEAD | /admin/reports | admin.reports.index | ReportController@index |
| GET|HEAD | /admin/reports/inventory | admin.reports.inventory | ReportController@inventory |
| GET|HEAD | /admin/reports/payments | admin.reports.payments | ReportController@payments |
| GET|HEAD | /admin/reports/purchases | admin.reports.purchases | ReportController@purchases |
| GET|HEAD | /admin/reports/sales | admin.reports.sales | ReportController@sales |

## Admin Roles

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/roles/create | admin.roles.create | RoleController@create |
| DELETE | /admin/roles/{role} | admin.roles.destroy | RoleController@destroy |
| GET|HEAD | /admin/roles/{role}/edit | admin.roles.edit | RoleController@edit |
| GET|HEAD | /admin/roles | admin.roles.index | RoleController@index |
| GET|HEAD | /admin/roles/{role} | admin.roles.show | RoleController@show |
| POST | /admin/roles | admin.roles.store | RoleController@store |
| PUT|PATCH | /admin/roles/{role} | admin.roles.update | RoleController@update |

## Admin Sales-Orders

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/sales-orders/create | admin.sales-orders.create | SalesOrderController@create |
| DELETE | /admin/sales-orders/{sales_order} | admin.sales-orders.destroy | SalesOrderController@destroy |
| GET|HEAD | /admin/sales-orders/{sales_order}/edit | admin.sales-orders.edit | SalesOrderController@edit |
| POST | /admin/sales-orders/{sales_order}/generate-invoice | admin.sales-orders.generate-invoice | SalesOrderController@generateInvoice |
| GET|HEAD | /admin/sales-orders | admin.sales-orders.index | SalesOrderController@index |
| GET|HEAD | /admin/sales-orders/{sales_order} | admin.sales-orders.show | SalesOrderController@show |
| POST | /admin/sales-orders | admin.sales-orders.store | SalesOrderController@store |
| PUT|PATCH | /admin/sales-orders/{sales_order} | admin.sales-orders.update | SalesOrderController@update |
| PATCH | /admin/sales-orders/{sales_order}/status | admin.sales-orders.update-status | SalesOrderController@updateStatus |

## Admin Service-Categories

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/service-categories/create | admin.service-categories.create | ServiceCategoryController@create |
| DELETE | /admin/service-categories/{service_category} | admin.service-categories.destroy | ServiceCategoryController@destroy |
| GET|HEAD | /admin/service-categories/{service_category}/edit | admin.service-categories.edit | ServiceCategoryController@edit |
| GET|HEAD | /admin/service-categories | admin.service-categories.index | ServiceCategoryController@index |
| POST | /admin/service-categories | admin.service-categories.store | ServiceCategoryController@store |
| PUT|PATCH | /admin/service-categories/{service_category} | admin.service-categories.update | ServiceCategoryController@update |

## Admin Service-Tickets

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/service-tickets/{service_ticket}/comments | admin.service-tickets.add-comment | ServiceTicketController@addComment |
| GET|HEAD | /admin/service-tickets/create | admin.service-tickets.create | ServiceTicketController@create |
| DELETE | /admin/service-tickets/{service_ticket} | admin.service-tickets.destroy | ServiceTicketController@destroy |
| GET|HEAD | /admin/service-tickets/{service_ticket}/edit | admin.service-tickets.edit | ServiceTicketController@edit |
| GET|HEAD | /admin/service-tickets | admin.service-tickets.index | ServiceTicketController@index |
| GET|HEAD | /admin/service-tickets/{service_ticket} | admin.service-tickets.show | ServiceTicketController@show |
| POST | /admin/service-tickets | admin.service-tickets.store | ServiceTicketController@store |
| PUT|PATCH | /admin/service-tickets/{service_ticket} | admin.service-tickets.update | ServiceTicketController@update |
| PATCH | /admin/service-tickets/{service_ticket}/status | admin.service-tickets.update-status | ServiceTicketController@updateStatus |

## Admin Settings

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/settings | admin.settings.index | SettingController@index |
| PUT | /admin/settings | admin.settings.update | SettingController@update |

## Admin Signin

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /admin/signin | admin.signin | AuthController@signin |

## Admin States

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/states/create | admin.states.create | StateController@create |
| DELETE | /admin/states/{state} | admin.states.destroy | StateController@destroy |
| GET|HEAD | /admin/states/{state}/edit | admin.states.edit | StateController@edit |
| GET|HEAD | /admin/states | admin.states.index | StateController@index |
| GET|HEAD | /admin/states/{state} | admin.states.show | StateController@show |
| POST | /admin/states | admin.states.store | StateController@store |
| PATCH | /admin/states/{state}/toggle-status | admin.states.toggle-status | StateController@toggleStatus |
| PUT|PATCH | /admin/states/{state} | admin.states.update | StateController@update |

## Admin Vendors

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/vendors/create | admin.vendors.create | VendorController@create |
| DELETE | /admin/vendors/{vendor} | admin.vendors.destroy | VendorController@destroy |
| GET|HEAD | /admin/vendors/{vendor}/edit | admin.vendors.edit | VendorController@edit |
| GET|HEAD | /admin/vendors | admin.vendors.index | VendorController@index |
| GET|HEAD | /admin/vendors/{vendor} | admin.vendors.show | VendorController@show |
| POST | /admin/vendors | admin.vendors.store | VendorController@store |
| PATCH | /admin/vendors/{vendor}/toggle-status | admin.vendors.toggle-status | VendorController@toggleStatus |
| PUT|PATCH | /admin/vendors/{vendor} | admin.vendors.update | VendorController@update |

## Admin Warehouses

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /admin/warehouses/create | admin.warehouses.create | WarehouseController@create |
| DELETE | /admin/warehouses/{warehouse} | admin.warehouses.destroy | WarehouseController@destroy |
| GET|HEAD | /admin/warehouses/{warehouse}/edit | admin.warehouses.edit | WarehouseController@edit |
| GET|HEAD | /admin/warehouses | admin.warehouses.index | WarehouseController@index |
| POST | /admin/warehouses | admin.warehouses.store | WarehouseController@store |
| PATCH | /admin/warehouses/{warehouse}/toggle-status | admin.warehouses.toggle-status | WarehouseController@toggleStatus |
| PUT|PATCH | /admin/warehouses/{warehouse} | admin.warehouses.update | WarehouseController@update |

## Documentation Index

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /documentation | documentation.index | DocumentationController@index |

## Documentation Search

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /documentation/search | documentation.search | DocumentationController@search |

## Documentation Section

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /documentation/{section} | documentation.section | DocumentationController@section |

## Employee Appraisals

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/appraisals | employee.appraisals.index | AppraisalController@index |
| GET|HEAD | /employee/appraisals/{appraisal}/pdf | employee.appraisals.pdf | AppraisalController@pdf |
| GET|HEAD | /employee/appraisals/{appraisal} | employee.appraisals.show | AppraisalController@show |

## Employee Attendance

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/attendance | employee.attendance.index | AttendanceController@index |
| POST | /employee/attendance/punch | employee.attendance.punch | AttendanceController@punch |

## Employee Change-Password

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /employee/change-password | employee.change-password | AuthController@changePassword |

## Employee Dashboard

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/dashboard | employee.dashboard | DashboardController@index |

## Employee Feedback

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/feedback | employee.feedback.index | FeedbackController@index |
| POST | /employee/feedback | employee.feedback.store | FeedbackController@store |

## Employee Leaves

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /employee/leaves/{leaveRequest}/cancel | employee.leaves.cancel | LeaveController@cancel |
| GET|HEAD | /employee/leaves/apply | employee.leaves.create | LeaveController@create |
| GET|HEAD | /employee/leaves | employee.leaves.index | LeaveController@index |
| GET|HEAD | /employee/leaves/{leaveRequest} | employee.leaves.show | LeaveController@show |
| POST | /employee/leaves | employee.leaves.store | LeaveController@store |

## Employee Login

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/login | employee.login | AuthController@showLogin |

## Employee Logout

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /employee/logout | employee.logout | AuthController@logout |

## Employee Payslips

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/payslips | employee.payslips.index | PayslipController@index |
| GET|HEAD | /employee/payslips/{payslip}/pdf | employee.payslips.pdf | PayslipController@pdf |
| GET|HEAD | /employee/payslips/{payslip} | employee.payslips.show | PayslipController@show |

## Employee Penalties

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/penalties | employee.penalties.index | PenaltyController@index |
| GET|HEAD | /employee/penalties/{penalty} | employee.penalties.show | PenaltyController@show |

## Employee Performance

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/performance | employee.performance.index | PerformanceController@index |

## Employee Profile

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /employee/profile/edit | employee.profile.edit | ProfileController@edit |
| GET|HEAD | /employee/profile | employee.profile.show | ProfileController@show |
| PUT | /employee/profile | employee.profile.update | ProfileController@update |

## Employee Signin

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /employee/login | employee.signin | AuthController@login |

## Employee Warnings

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| POST | /employee/warnings/{warning}/acknowledge | employee.warnings.acknowledge | WarningController@acknowledge |
| GET|HEAD | /employee/warnings | employee.warnings.index | WarningController@index |

## Storage Local

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET|HEAD | /storage/{path} | storage.local | Closure |
| PUT | /storage/{path} | storage.local.upload | Closure |

