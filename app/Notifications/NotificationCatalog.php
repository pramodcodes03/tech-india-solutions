<?php

namespace App\Notifications;

/**
 * Single source of truth for every notification event in the system.
 *
 * Each event has:
 *   key          unique slug, e.g. "invoice.created"
 *   module       grouping for the admin toggle UI
 *   name         human-readable label
 *   description  what it does, who gets it
 *   subject      mailer subject template (with {placeholders})
 *   recipients   array of role keys; resolved by RecipientResolver
 *   related      morph type stored on notification_logs (for "what is this email about?")
 *   pdf          optional callable: returns view-name + filename for an attached PDF
 *   default_on   whether enabled by default for new businesses (default: true)
 *
 * Adding a notification:
 *   1. Add an entry here.
 *   2. Create resources/views/emails/events/{key-with-slashes-as-folders}.blade.php
 *   3. Call NotificationDispatcher::fire('event.key', $entity, $context = []) at the trigger point.
 *
 * The recipient role keys understood by RecipientResolver:
 *   customer.email         → entity->customer->email (Invoice, Quotation, etc.)
 *   vendor.email           → entity->vendor->email (Purchase Order)
 *   employee.email         → entity->employee->email or entity itself if Employee
 *   employee.personal      → personal_email if set, else employee email
 *   admin.creator          → entity->creator->email
 *   admin.all              → all active admins of this business
 *   admin.role:HR Manager  → admins of this business with that role
 *   admin.super            → all super admins (cross-business)
 *   reporting_manager      → entity->employee->reportingManager->email
 *   technician             → entity->technician->email (when bookings module exists)
 */
class NotificationCatalog
{
    public static function events(): array
    {
        return [

            // ───────────────────────────── 1. SALES / CRM ─────────────────────────────
            'lead.assigned' => [
                'module' => 'Sales / CRM',
                'name' => 'Lead assigned',
                'description' => 'Sent to the assigned sales rep when a lead is assigned to them.',
                'subject' => 'New lead assigned to you: {entity.name}',
                'recipients' => ['lead.assignee'],
                'related' => 'lead',
            ],
            'lead.status_changed' => [
                'module' => 'Sales / CRM',
                'name' => 'Lead status changed',
                'description' => 'Sent to the lead owner + their manager when the lead status changes.',
                'subject' => 'Lead {entity.name} status changed to {context.new_status}',
                'recipients' => ['lead.assignee', 'lead.assignee_manager'],
                'related' => 'lead',
            ],
            'lead.converted' => [
                'module' => 'Sales / CRM',
                'name' => 'Lead converted to customer',
                'description' => 'Sent to sales managers when a lead becomes a customer.',
                'subject' => 'Lead {entity.name} converted to customer',
                'recipients' => ['admin.role:Sales', 'admin.role:Admin'],
                'related' => 'lead',
            ],
            'quotation.sent' => [
                'module' => 'Sales / CRM',
                'name' => 'Quotation sent to customer',
                'description' => 'Sent to the customer when a quotation is created/sent. Internal CC to creator.',
                'subject' => 'Quotation {entity.quotation_number} from {business.name}',
                'recipients' => ['customer.email', 'admin.creator'],
                'related' => 'quotation',
                'pdf' => ['view' => 'admin.quotations.pdf', 'name' => 'Quotation-{entity.quotation_number}.pdf'],
            ],
            'quotation.approved' => [
                'module' => 'Sales / CRM',
                'name' => 'Quotation approved',
                'description' => 'Notify sales when customer approves a quotation.',
                'subject' => 'Quotation {entity.quotation_number} approved',
                'recipients' => ['admin.creator', 'admin.role:Sales'],
                'related' => 'quotation',
            ],
            'quotation.rejected' => [
                'module' => 'Sales / CRM',
                'name' => 'Quotation rejected',
                'description' => 'Notify sales when customer rejects a quotation.',
                'subject' => 'Quotation {entity.quotation_number} rejected',
                'recipients' => ['admin.creator', 'admin.role:Sales'],
                'related' => 'quotation',
            ],
            'quotation.converted_to_so' => [
                'module' => 'Sales / CRM',
                'name' => 'Quotation converted to Sales Order',
                'description' => 'Notify customer + sales rep when an SO is created from a quotation.',
                'subject' => 'Sales Order {context.order_number} confirmed (from {entity.quotation_number})',
                'recipients' => ['customer.email', 'admin.creator'],
                'related' => 'quotation',
            ],
            'proforma.issued' => [
                'module' => 'Sales / CRM',
                'name' => 'Proforma invoice issued',
                'description' => 'Sent to the customer when a proforma invoice is created.',
                'subject' => 'Proforma Invoice {entity.proforma_number} from {business.name}',
                'recipients' => ['customer.email'],
                'related' => 'proforma_invoice',
                'pdf' => ['view' => 'admin.proforma-invoices.pdf', 'name' => 'Proforma-{entity.proforma_number}.pdf'],
            ],
            'sales_order.status_changed' => [
                'module' => 'Sales / CRM',
                'name' => 'Sales order status changed',
                'description' => 'Sent to customer when SO is confirmed / dispatched / delivered.',
                'subject' => 'Sales Order {entity.order_number} {context.new_status}',
                'recipients' => ['customer.email'],
                'related' => 'sales_order',
            ],

            // ───────────────────────── 2. INVOICING & PAYMENTS ────────────────────────
            'invoice.created' => [
                'module' => 'Invoicing & Payments',
                'name' => 'Invoice generated',
                'description' => 'Invoice PDF emailed to the customer when created.',
                'subject' => 'Invoice {entity.invoice_number} from {business.name}',
                'recipients' => ['customer.email'],
                'related' => 'invoice',
                'pdf' => ['view' => 'admin.invoices.pdf', 'name' => 'Invoice-{entity.invoice_number}.pdf'],
            ],
            'invoice.cancelled' => [
                'module' => 'Invoicing & Payments',
                'name' => 'Invoice cancelled',
                'description' => 'Customer is notified when an invoice is cancelled or voided.',
                'subject' => 'Invoice {entity.invoice_number} has been cancelled',
                'recipients' => ['customer.email'],
                'related' => 'invoice',
            ],
            'payment.received' => [
                'module' => 'Invoicing & Payments',
                'name' => 'Payment received (receipt)',
                'description' => 'Receipt emailed to customer when their payment is recorded.',
                'subject' => 'Payment receipt — {entity.amount} for invoice {context.invoice_number}',
                'recipients' => ['customer.email'],
                'related' => 'payment',
            ],
            'invoice.reminder_t3' => [
                'module' => 'Invoicing & Payments',
                'name' => 'Payment reminder — 3 days before due',
                'description' => 'Reminder to customer 3 days before invoice due date.',
                'subject' => 'Reminder: Invoice {entity.invoice_number} due in 3 days',
                'recipients' => ['customer.email'],
                'related' => 'invoice',
            ],
            'invoice.overdue' => [
                'module' => 'Invoicing & Payments',
                'name' => 'Invoice overdue',
                'description' => 'Daily reminder to customer + accounts when invoice is overdue.',
                'subject' => 'OVERDUE: Invoice {entity.invoice_number}',
                'recipients' => ['customer.email', 'admin.role:Accounts'],
                'related' => 'invoice',
            ],

            // ─────────────────────── 3. INVENTORY & PURCHASE ──────────────────────────
            'stock.low' => [
                'module' => 'Inventory & Purchase',
                'name' => 'Low-stock alert',
                'description' => 'Sent to inventory manager when a product drops below reorder level.',
                'subject' => 'Low stock: {entity.name} ({context.current_stock} left)',
                'recipients' => ['admin.role:Inventory', 'admin.role:Admin'],
                'related' => 'product',
            ],
            'stock.adjusted' => [
                'module' => 'Inventory & Purchase',
                'name' => 'Stock adjustment posted',
                'description' => 'Sent to the warehouse manager when stock is manually adjusted.',
                'subject' => 'Stock adjustment posted for {context.product_name}',
                'recipients' => ['admin.role:Inventory'],
                'related' => 'stock_movement',
                'default_on' => false,
            ],
            'purchase_order.issued' => [
                'module' => 'Inventory & Purchase',
                'name' => 'Purchase order issued',
                'description' => 'PO PDF emailed to vendor when issued.',
                'subject' => 'Purchase Order {entity.po_number} from {business.name}',
                'recipients' => ['vendor.email'],
                'related' => 'purchase_order',
                'pdf' => ['view' => 'admin.purchase-orders.pdf', 'name' => 'PO-{entity.po_number}.pdf'],
            ],
            'goods_receipt.received' => [
                'module' => 'Inventory & Purchase',
                'name' => 'PO — goods received',
                'description' => 'Sent to buyer + accounts when goods are received against a PO.',
                'subject' => 'Goods received — GRN {entity.grn_number}',
                'recipients' => ['admin.role:Inventory', 'admin.role:Accounts'],
                'related' => 'goods_receipt',
            ],

            // ───────────────────────── 4. SERVICE TICKETS ─────────────────────────────
            'service_ticket.created' => [
                'module' => 'Service Tickets',
                'name' => 'New ticket raised',
                'description' => 'Acknowledgement to customer + alert to service agent.',
                'subject' => 'Ticket {entity.ticket_number} received',
                'recipients' => ['customer.email', 'admin.role:Service'],
                'related' => 'service_ticket',
            ],
            'service_ticket.assigned' => [
                'module' => 'Service Tickets',
                'name' => 'Ticket assigned / reassigned',
                'description' => 'Sent to the new assignee.',
                'subject' => 'Ticket {entity.ticket_number} assigned to you',
                'recipients' => ['ticket.assignee'],
                'related' => 'service_ticket',
            ],
            'service_ticket.commented' => [
                'module' => 'Service Tickets',
                'name' => 'New comment on ticket',
                'description' => 'Sent to customer + watchers when a comment is added.',
                'subject' => 'New update on ticket {entity.ticket_number}',
                'recipients' => ['customer.email'],
                'related' => 'service_ticket',
            ],
            'service_ticket.status_changed' => [
                'module' => 'Service Tickets',
                'name' => 'Ticket status changed',
                'description' => 'Customer notified on in-progress / resolved / closed.',
                'subject' => 'Ticket {entity.ticket_number} is now {context.new_status}',
                'recipients' => ['customer.email'],
                'related' => 'service_ticket',
            ],

            // ───────────────────────────── 6. HR — LEAVES ─────────────────────────────
            'leave.applied' => [
                'module' => 'HR — Leaves',
                'name' => 'Leave application submitted',
                'description' => 'Sent to the reporting manager + HR.',
                'subject' => 'Leave request from {entity.employee.first_name}',
                'recipients' => ['reporting_manager', 'admin.role:HR Manager'],
                'related' => 'leave_request',
            ],
            'leave.approved' => [
                'module' => 'HR — Leaves',
                'name' => 'Leave approved',
                'description' => 'Sent to the employee.',
                'subject' => 'Your leave request was approved',
                'recipients' => ['employee.email'],
                'related' => 'leave_request',
            ],
            'leave.rejected' => [
                'module' => 'HR — Leaves',
                'name' => 'Leave rejected',
                'description' => 'Sent to the employee with reason.',
                'subject' => 'Your leave request was rejected',
                'recipients' => ['employee.email'],
                'related' => 'leave_request',
            ],
            'leave.cancelled' => [
                'module' => 'HR — Leaves',
                'name' => 'Leave cancelled by employee',
                'description' => 'Sent to manager + HR when employee cancels their leave.',
                'subject' => 'Leave cancelled — {entity.employee.first_name}',
                'recipients' => ['reporting_manager', 'admin.role:HR Manager'],
                'related' => 'leave_request',
            ],
            'leave_balance.updated' => [
                'module' => 'HR — Leaves',
                'name' => 'Leave balance updated',
                'description' => 'Sent to employee when their leave balance is allocated or adjusted.',
                'subject' => 'Your leave balance has been updated',
                'recipients' => ['employee.email'],
                'related' => 'leave_balance',
                'default_on' => false,
            ],

            // ──────────────────────────── 7. HR — PAYROLL ─────────────────────────────
            'payslip.generated' => [
                'module' => 'HR — Payroll',
                'name' => 'Payslip generated',
                'description' => 'Payslip PDF emailed to employee.',
                'subject' => 'Your payslip for {context.period}',
                'recipients' => ['employee.personal'],
                'related' => 'payslip',
                'pdf' => ['view' => 'admin.hr.payroll.pdf', 'name' => 'Payslip-{entity.payslip_code}.pdf'],
            ],
            'payslip.paid' => [
                'module' => 'HR — Payroll',
                'name' => 'Salary marked paid',
                'description' => 'Notify employee when salary has been paid out.',
                'subject' => 'Salary credited for {context.period}',
                'recipients' => ['employee.personal'],
                'related' => 'payslip',
            ],
            'salary_structure.changed' => [
                'module' => 'HR — Payroll',
                'name' => 'Salary structure changed',
                'description' => 'Notify employee when their CTC / structure is updated.',
                'subject' => 'Your salary structure has been updated',
                'recipients' => ['employee.personal'],
                'related' => 'salary_structure',
            ],
            'salary_structure.submitted' => [
                'module' => 'HR — Payroll',
                'name' => 'Salary structure submitted for approval',
                'description' => 'Sent to Admin + Super Admin when HR submits a new or revised salary structure that needs approval before it takes effect.',
                'subject' => 'Salary structure pending approval — {entity.employee.first_name}',
                'recipients' => ['admin.role:Admin', 'admin.super', 'admin.role:Business Admin'],
                'related' => 'salary_structure',
            ],
            'salary_structure.approved' => [
                'module' => 'HR — Payroll',
                'name' => 'Salary structure approved',
                'description' => 'Sent to the HR submitter and the employee when an Admin approves a submitted salary structure.',
                'subject' => 'Salary structure approved for {entity.employee.first_name}',
                'recipients' => ['salary_structure.submitter', 'employee.personal'],
                'related' => 'salary_structure',
            ],
            'salary_structure.rejected' => [
                'module' => 'HR — Payroll',
                'name' => 'Salary structure rejected',
                'description' => 'Sent to the HR submitter when an Admin rejects a submitted salary structure.',
                'subject' => 'Salary structure rejected for {entity.employee.first_name}',
                'recipients' => ['salary_structure.submitter'],
                'related' => 'salary_structure',
            ],
            'bank_edit.requested' => [
                'module' => 'HR — Discipline',
                'name' => 'Bank-detail edit requested',
                'description' => 'Sent to Admin + Super Admin when HR submits a request to change an employee\'s bank account number or IFSC.',
                'subject' => 'Bank detail change request — {entity.employee.first_name}',
                'recipients' => ['admin.role:Admin', 'admin.super', 'admin.role:Business Admin'],
                'related' => 'bank_detail_edit_request',
            ],
            'bank_edit.approved' => [
                'module' => 'HR — Discipline',
                'name' => 'Bank-detail change approved',
                'description' => 'Sent to the requesting HR user (and the affected employee) when an Admin approves the change.',
                'subject' => 'Bank detail change approved — {entity.employee.first_name}',
                'recipients' => ['bank_edit.requester', 'employee.personal'],
                'related' => 'bank_detail_edit_request',
            ],
            'bank_edit.rejected' => [
                'module' => 'HR — Discipline',
                'name' => 'Bank-detail change rejected',
                'description' => 'Sent to the requesting HR user when an Admin rejects the change.',
                'subject' => 'Bank detail change rejected — {entity.employee.first_name}',
                'recipients' => ['bank_edit.requester'],
                'related' => 'bank_detail_edit_request',
            ],
            'payroll.completed' => [
                'module' => 'HR — Payroll',
                'name' => 'Payroll generation completed',
                'description' => 'Summary email to HR admin after monthly payroll run.',
                'subject' => 'Payroll for {context.period} completed',
                'recipients' => ['admin.role:HR Manager'],
                'related' => null,
            ],

            // ───────────────────── 8. HR — DISCIPLINE & PERFORMANCE ───────────────────
            'warning.issued' => [
                'module' => 'HR — Discipline',
                'name' => 'Warning issued',
                'description' => 'Notify employee + their manager when a warning is issued.',
                'subject' => 'A warning has been issued to you',
                'recipients' => ['employee.email', 'reporting_manager'],
                'related' => 'warning',
            ],
            'warning.withdrawn' => [
                'module' => 'HR — Discipline',
                'name' => 'Warning withdrawn',
                'description' => 'Notify employee when a warning is withdrawn.',
                'subject' => 'A warning has been withdrawn',
                'recipients' => ['employee.email'],
                'related' => 'warning',
            ],
            'penalty.issued' => [
                'module' => 'HR — Discipline',
                'name' => 'Penalty issued',
                'description' => 'Notify employee about a penalty.',
                'subject' => 'A penalty has been recorded',
                'recipients' => ['employee.email'],
                'related' => 'penalty',
            ],
            'penalty.reduced' => [
                'module' => 'HR — Discipline',
                'name' => 'Penalty reduced or waived',
                'description' => 'Notify employee when a penalty is reduced or waived.',
                'subject' => 'A penalty has been reduced',
                'recipients' => ['employee.email'],
                'related' => 'penalty',
            ],
            'appraisal.recorded' => [
                'module' => 'HR — Discipline',
                'name' => 'Appraisal / increment recorded',
                'description' => 'Send appraisal PDF to employee.',
                'subject' => 'Your appraisal for {entity.cycle}',
                'recipients' => ['employee.personal'],
                'related' => 'appraisal',
                'pdf' => ['view' => 'admin.hr.appraisals.pdf', 'name' => 'Appraisal-{entity.appraisal_code}.pdf'],
            ],
            'feedback.submitted' => [
                'module' => 'HR — Discipline',
                'name' => 'New feedback submitted',
                'description' => 'Notify HR when feedback is submitted by an employee.',
                'subject' => 'New department feedback received',
                'recipients' => ['admin.role:HR Manager'],
                'related' => 'department_feedback',
                'default_on' => false,
            ],

            // ──────────────────────── 9. HR — CALENDAR ────────────────────────────────
            'holiday.upcoming' => [
                'module' => 'HR — Calendar',
                'name' => 'Upcoming holiday reminder',
                'description' => 'Reminder to all employees 2 days before a holiday.',
                'subject' => 'Upcoming holiday: {entity.name}',
                'recipients' => ['admin.role:HR Manager'], // employee fan-out done by command
                'related' => 'holiday',
            ],
            'employee.birthday' => [
                'module' => 'HR — Calendar',
                'name' => 'Birthday / work anniversary',
                'description' => 'Birthday email to employee + team notification.',
                'subject' => 'Happy birthday {entity.first_name}!',
                'recipients' => ['employee.personal'],
                'related' => 'employee',
                'default_on' => false,
            ],
            'shift.changed' => [
                'module' => 'HR — Calendar',
                'name' => 'Shift change notice',
                'description' => 'Notify the affected employee when their shift assignment changes.',
                'subject' => 'Your shift has been updated',
                'recipients' => ['employee.email'],
                'related' => 'employee',
            ],

            // ────────────────────────── 10. ASSET MANAGEMENT ──────────────────────────
            'asset.assigned' => [
                'module' => 'Assets',
                'name' => 'Asset assigned to employee',
                'description' => 'Notify the employee receiving the asset.',
                'subject' => 'Asset {context.asset_code} assigned to you',
                'recipients' => ['asset_assignment.employee'],
                'related' => 'asset_assignment',
            ],
            'asset.returned' => [
                'module' => 'Assets',
                'name' => 'Asset returned',
                'description' => 'Notify employee + asset manager on return.',
                'subject' => 'Asset {context.asset_code} returned',
                'recipients' => ['asset_assignment.employee', 'admin.role:Admin'],
                'related' => 'asset_assignment',
            ],
            'asset.transferred' => [
                'module' => 'Assets',
                'name' => 'Asset transferred',
                'description' => 'Notify both the previous and new holders.',
                'subject' => 'Asset {context.asset_code} transferred',
                'recipients' => ['asset_assignment.employee'],
                'related' => 'asset_assignment',
            ],
            'asset.maintenance_scheduled' => [
                'module' => 'Assets',
                'name' => 'Maintenance scheduled / due',
                'description' => 'Notify asset manager about upcoming maintenance.',
                'subject' => 'Maintenance due: {context.asset_code}',
                'recipients' => ['admin.role:Admin'],
                'related' => 'asset_maintenance_log',
            ],
            'asset.maintenance_completed' => [
                'module' => 'Assets',
                'name' => 'Maintenance completed',
                'description' => 'Notify asset manager when maintenance is finished.',
                'subject' => 'Maintenance completed: {context.asset_code}',
                'recipients' => ['admin.role:Admin'],
                'related' => 'asset_maintenance_log',
            ],

            // ─────────────────── Routine Payment Tracker (existing) ───────────────────
            'expense.reminder_t3' => [
                'module' => 'Routine Payment Tracker',
                'name' => 'Payment reminder — 3 days before due',
                'description' => 'Sent to all admins 3 days before a payment due date.',
                'subject' => 'Payment due in 3 days: {entity.title}',
                'recipients' => ['admin.all'],
                'related' => 'expense',
            ],
            'expense.reminder_t1' => [
                'module' => 'Routine Payment Tracker',
                'name' => 'Payment reminder — 1 day before',
                'description' => 'Sent to all admins 1 day before due.',
                'subject' => 'Payment due tomorrow: {entity.title}',
                'recipients' => ['admin.all'],
                'related' => 'expense',
            ],
            'expense.due_today' => [
                'module' => 'Routine Payment Tracker',
                'name' => 'Payment due today',
                'description' => 'Sent to all admins on the due date.',
                'subject' => 'Payment due today: {entity.title}',
                'recipients' => ['admin.all'],
                'related' => 'expense',
            ],
            'expense.overdue' => [
                'module' => 'Routine Payment Tracker',
                'name' => 'Payment overdue',
                'description' => 'Daily reminder to all admins for unpaid payments past due.',
                'subject' => 'Payment OVERDUE: {entity.title}',
                'recipients' => ['admin.all'],
                'related' => 'expense',
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::events()[$key] ?? null;
    }

    public static function byModule(): array
    {
        $grouped = [];
        foreach (self::events() as $key => $event) {
            $grouped[$event['module']][$key] = $event;
        }

        return $grouped;
    }

    public static function exists(string $key): bool
    {
        return isset(self::events()[$key]);
    }
}
