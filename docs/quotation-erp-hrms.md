# ERP / HRMS — Project Quotation & Scope Document

**Prepared by:** Tech India Solutions  
**Date:** June 2026  
**Project Type:** Custom ERP / HRMS Platform (Laravel + Multi-Tenant SaaS)

---

> **How to read this document**
>
> Each module is broken into two sections:
> - **Implemented** — features that are live in the current codebase and deployable.
> - **Pending / New Requirement** — features from the new requirement document that are not yet built and must be quoted separately.
>
> All numeric thresholds (probation days, leave accrual rates, TAT hours, etc.) are **fully configurable** per business — nothing is hard-coded. Wherever a default is mentioned it is a suggested default that an admin can override from the settings panel.

---

## Module Index

| # | Module | Status |
|---|--------|--------|
| 1 | [Recruitment & Hiring Management](#1-recruitment--hiring-management) | Pending |
| 2 | [Payroll & Salary Management](#2-payroll--salary-management) | Partially Implemented |
| 3 | [Attendance Management](#3-attendance-management) | Implemented |
| 4 | [Leave Management](#4-leave-management) | Partially Implemented |
| 5 | [Expense Management & Reimbursement](#5-expense-management--reimbursement) | Partially Implemented |
| 6 | [Employee Document Management](#6-employee-document-management) | Implemented |
| 7 | [Ticket Management System (TMS)](#7-ticket-management-system-tms) | Partially Implemented |
| 8 | [Asset & Inventory Management](#8-asset--inventory-management) | Implemented |
| 9 | [Lead Management](#9-lead-management) | Implemented |
| 10 | [Bulk Import Framework](#10-bulk-import-framework) | Partially Implemented |
| 11 | [Reporting & Analytics](#11-reporting--analytics) | Partially Implemented |
| 12 | [Platform Infra (Multi-Tenancy, Auth, Roles)](#12-platform-infrastructure) | Implemented |

---

## 1. Recruitment & Hiring Management

### Implemented
- None (this module does not exist in the current codebase).

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Candidate profiles — walk-in, referral, campus, other sources | Source field captured and filterable |
| Source-wise recruitment pipeline (Kanban or list view) | Status flow: Applied → Screened → Interview → Offer → Hired / Rejected |
| Employee referral tracking — link candidate to referrer | Referrer gets visibility on candidate status |
| Campus recruitment batch management | Batch name, institution, date, drive coordinator |
| Configurable hiring stages | Admin can add / rename / reorder pipeline stages |
| Offer letter generation (PDF) | Template-driven, placeholders from candidate profile |
| Recruitment reports — source-wise, stage-wise, conversion rate | Export to Excel / PDF |
| Bulk candidate import (Excel) | For campus drives with large applicant lists |

> **Scope note:** Screen-recording / demo flow to be shared separately by client before development begins. UI flow will be finalized in a separate session.

---

## 2. Payroll & Salary Management

### Implemented

| Feature | Detail |
|---------|--------|
| Employee salary structure | Stores Basic, HRA, Conveyance, Medical, Special, Other Allowance per employee |
| Multi-version salary structures | `effective_from` / `effective_to` date range per structure; `is_current` flag tracks the active version |
| Payroll processing | Selects the salary structure whose date range covers the payroll month; blocks if none found |
| Earnings on payslip | Basic, HRA, Conveyance, Medical, Special, Other Allowance, Bonus |
| Deductions on payslip | PF (configurable %), ESI (configurable %), Professional Tax (fixed amount), TDS (monthly fixed), Penalty Deduction, LOP Deduction, Other Deductions |
| Loss of Pay (LOP) | `lop_days` calculated per payroll run; deducted from gross proportionally |
| Payslip generation | PDF payslip with full earnings / deduction breakdown |
| Payslip approval workflow | HR submits → Admin/Director approves → Employee can view |
| Salary structure approval | Pending → Approved → Rejected workflow with reviewer notes |
| PF / ESI / PT configurable per employee | Stored as `pf_percent`, `esi_percent`, `professional_tax` on salary structure |
| Business-scoped payroll | Each business has its own payroll runs; no cross-business leakage |
| Activity log | Every salary structure change is audit-logged (who changed what, when) |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Salary structure per Department and Employee Category | Currently per-employee only; needs department-level and category-level templates |
| Salary component override / edit at payroll-run level | Allow HR to override a single component (e.g. Arrears, Incentive) for a specific month without creating a new salary structure version |
| Arrears calculation | Auto-compute arrears when a salary revision is applied mid-month or retroactively |
| Incentive / Variable Pay component | Named variable pay component with per-run entry |
| TDS full calculation engine | Currently stores a fixed monthly TDS amount; needs actual TDS slab-based calculation with Form 16 support |
| LWF (Labour Welfare Fund) configuration | State-wise LWF amount, frequency (monthly / half-yearly), employee + employer contribution |
| ESI Challan generation | Generate ESIC challan in the prescribed format |
| EPS Calculation | Employee Pension Scheme — auto-split from PF contribution |
| PF Challan generation | Generate EPF challan (ECR file format) |
| Statutory compliance reports | Monthly PF/ESI/PT/LWF registers |
| 0.5 day LOP for excess break | Configurable break duration policy per business; auto-flag in attendance → feeds LOP at payroll |
| Payroll data bulk export | Export payroll data in bank-transfer format (Excel) |
| Bulk salary structure assignment | Assign a department / category template to multiple employees at once |

---

## 3. Attendance Management

### Implemented

| Feature | Detail |
|---------|--------|
| Daily attendance record | Per-employee, per-date: check-in, check-out, hours worked, status (present / absent / half-day / WFH / holiday / week-off) |
| Biometric device sync | Real-time pull every minute via configurable device API (with overlap protection); stores `biometric_ref` and `card_no` |
| Manual attendance entry | Admin / HR can manually add or edit attendance records |
| Attendance import (Excel) | Date-wise daily summary Excel import; cross-business validated |
| Punch lock | `check_in_locked` / `check_out_locked` flags prevent edits after HR locks a record |
| Shift assignment | Each employee has a shift with start time; late / early hours computed |
| Late hours / Early hours / Overtime | Stored per record; visible in reports |
| Weekly-off configuration | Business-level configurable week-off days (e.g. Sunday, or Sunday + Saturday half-day) |
| Comp-off (dynamic week-off) | Employees can request comp-off for working on a week-off day; HR approves; approved comp-off grants an extra day off |
| Holiday calendar | Yearly holidays per business; auto-marked on attendance |
| Monthly attendance summary export | Excel export with present / absent / late / OT counts per employee |
| Activity log | Source tracked (`manual`, `import`, `biometric`, `api`) |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Attendance regularization / missed-punch request by employee | Employee raises a self-service ticket to correct their own punch; HR approves / rejects |
| Ticket-based attendance correction workflow | Structured ticket (request_type, date, expected_in, expected_out, reason) → HR review → resolution; all actions logged |
| TAT enforcement — 48-hour resolution SLA | Auto-escalate or flag if ticket open > configurable hours (default 48) |

> **Context:** The client questioned whether more attendance work was needed after the biometric API was delivered. The above three items (regularization tickets, workflow, and TAT) are **new requirements** from the current document that were not part of the original attendance API scope.

---

## 4. Leave Management

### Implemented

| Feature | Detail |
|---------|--------|
| Leave types | Configurable leave types (code, name, annual quota, paid/unpaid, carry-forward, encashable) |
| Leave balance per employee per year | Tracks allocated, used, pending, carried_forward; available computed on the fly |
| Leave request submission | Employee submits with date range, leave type, day portion (full / first-half / second-half), reason |
| Leave request split | Long requests are split at month / policy boundaries automatically |
| Approval workflow | Routes to reporting manager (or configurable approver) |
| Leave history | Employee and admin both see request history with status |
| Carry forward | Controlled per leave type (`carry_forward` flag + `max_carry_forward` cap) |
| Business-scoped leave types | Each business configures its own leave types |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Leave cycle — Calendar Year (Jan–Dec) | Year-boundary rollover logic, lapse of SL/CL at Dec 31 |
| Probation-based eligibility | Configurable probation period (days); leave accrual only starts after probation end date — no hard-coded duration |
| Automated monthly leave accrual (cron) | Daily scheduler checks each employee; credits configured accrual rate (e.g. 0.5/month for SL, 0.5/month for CL — rates are configurable, not hard-coded); EL credits after configurable working-days threshold (default 240) |
| Accrual frequency options | Configurable: Monthly / Half-Yearly / Annual bulk allocation |
| 240-working-days rule for EL | System counts actual working days (excluding holidays + week-offs) since joining; configurable threshold |
| Backdated leave restriction — TAT window | Configurable TAT window (default 72 hours); employee cannot submit leave for a date older than the window |
| Auto-rejection of backdated applications | System validates `from_date` vs `applied_at`; auto-rejects if beyond TAT window |
| Bulk leave balance grant | Admin grants leave to multiple employees at once (by department / all) |
| Year-end lapse job | Cron at Dec 31: lapses unused SL/CL balances; carries forward eligible EL (up to max cap) |
| Leave approval auto-route to reporting manager | Automatically set `approver_id` from employee's `reporting_manager_id` at submission |
| Leave policy document per business | Configurable policy fields displayed to employees |

> **Important:** All numeric values — probation period, monthly accrual rate, 240-day EL threshold, 72-hour TAT, carry-forward cap — are **admin-configurable settings**, not constants in code.

---

## 5. Expense Management & Reimbursement

### Implemented

| Feature | Detail |
|---------|--------|
| Expense recording | Title, amount, category, subcategory, date, payment method, attachment upload |
| Recurring expenses | Configurable frequency (weekly / monthly / quarterly / half-yearly / yearly); auto-generates next instance daily via scheduler |
| Expense categories & subcategories | Admin manages category and subcategory master |
| Expense status flow | Unpaid → Paid → Cancelled |
| Payment tracking | `paid_date`, `payment_reference`, `paid_by_admin_id` |
| Expense reminders | Automated email reminders at T-3 and daily-overdue via scheduler |
| Expense reports | Filter by category, date range, status; export to Excel / PDF |
| Business-scoped | Each business sees only its own expenses |
| Soft delete + activity log | Full audit trail |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Employee self-service expense claims | Employees submit reimbursement claims (bill upload, amount, purpose, category) |
| Claim status tracking | Submitted → Under Review → Approved → Disbursed / Rejected |
| Employee claim history view | Timeline of all submitted claims with status |
| Budget management | Admin defines budget per category/period; system shows Total / Utilized / Remaining in real time |
| Requisition management | Admin / Accounts Head / authorized employees create requisitions (requested amount, estimated amount, purpose, category with dropdown: Furniture, Chairs, Systems, IT Equipment, Stationery, Other) |
| Approval matrix for requisitions | Route to Directors; configurable approval chain |
| Disbursement tracking | Mark requisition as disbursed; link to payment record |
| Requisition reports | Status-wise, category-wise, requestor-wise |

---

## 6. Employee Document Management

### Implemented

| Feature | Detail |
|---------|--------|
| Employee document upload | Types: Aadhaar, PAN, Educational Certificates, Offer Letter, Experience Letter, Other |
| File metadata stored | `doc_type`, `title`, `file_path`, `file_mime`, `file_size`, `expires_on` |
| Admin view | Admin panel shows all uploaded documents for any employee with full file preview |
| Employee self-upload | Employee can upload from their profile portal |
| Expiry tracking | `expires_on` date stored; can be used to flag expiring documents |
| Business-scoped | Documents belong to the employee's business context |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Admin verification / approval flow | Admin marks each document as Verified / Rejected with remarks; employee sees status |
| Document verification audit log | Who verified, when, remarks |
| Notification on upload | Admin notified when employee uploads a new document |
| Bulk document download | Admin can download all documents for an employee as a ZIP |

---

## 7. Ticket Management System (TMS)

### Implemented

| Feature | Detail |
|---------|--------|
| Service tickets | Ticket number, customer, product, category, issue description, site location, contact info, priority, status, assigned agent |
| Service categories | Admin-managed category master |
| Ticket comments | Threaded comments per ticket |
| Status flow | Open → In Progress → Resolved → Closed |
| Priority levels | Configurable priority |
| Scheduled appointment | `scheduled_at` datetime per ticket |
| Business-scoped | Each business sees only its own tickets |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Internal HR / IT / Admin / Accounts department tickets | Current TMS is customer-facing (service tickets); new requirement needs an internal employee-facing helpdesk |
| Internal ticket workflow | Employee raises → Department assigns agent → Review → Resolution → Closure |
| Escalation matrix | If ticket unresolved beyond configurable days (default 3), auto-escalate to next configured level; levels and escalation targets set by admin |
| Email notifications | On ticket creation, assignment, status change, escalation, closure — to creator, assignee, and escalation target |
| In-app (bell) notifications | Same events as email |
| TAT tracking | Configurable TAT per department / category; system flags breached tickets |
| Attendance regularization tickets | (See also Module 3) — missed punch / correction requests routed as internal HR tickets |

---

## 8. Asset & Inventory Management

### Implemented

| Feature | Detail |
|---------|--------|
| Asset master | Asset code, name, serial number, category, model, location, vendor, purchase details, warranty / insurance / end-of-life dates |
| Asset categories, models, locations | Full master data management |
| Asset status & condition | Status (active / inactive / disposed / lost) + condition rating |
| Asset assignment | Assign to employee; track custodian; assignment history |
| Asset maintenance logs | Log maintenance events with cost and notes |
| Asset repair module | Employee / admin raises repair request; approval workflow; activity log |
| Depreciation | Straight-line and reducing balance methods; monthly depreciation posting via scheduler; accumulated depreciation and book value tracked |
| QR code + image per asset | QR path stored; supports asset scanning |
| Asset register export (Excel / PDF) | Full register with all fields |
| Asset assignments export | Who holds what |
| Asset maintenance export | Maintenance history |
| Asset import | Bulk import via Excel template |
| Non-repairable flag | Admin marks asset as beyond repair |
| Business-scoped | Multi-tenant isolation |
| Activity log | Full change history |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Bulk operations UI | Select all / multi-select + bulk: assign, change category, change status, change condition, change location — all in one action |
| Bulk location transfer | Move N assets between locations in one operation (client example: 4,000 assets) |
| Bulk edit | Change multiple fields across selected assets in one step |
| Bulk delete | With confirmation and audit log |
| Asset-wise reports | Per-asset report with full history |
| Employee asset reports | All assets currently held by an employee |
| Status / condition / location reports | Filter and export by any dimension |

---

## 9. Lead Management

### Implemented

| Feature | Detail |
|---------|--------|
| Lead master | Code, name, company, phone, email, source, status, assigned employee, expected value, next follow-up date, notes |
| Lead activities | Activity log per lead (call, email, meeting, demo, etc.) |
| Lead status flow | Configurable (New → Contacted → Qualified → Proposal → Won / Lost) |
| Lead source tracking | Captured at creation; filterable |
| Assigned employee | Each lead assigned to a sales employee |
| Business-scoped | Multi-tenant isolation |
| Soft delete + activity log | Full audit trail |
| Lead reports | Filter / export |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Product-wise lead segmentation | Associate lead with a specific product; leads listed and reported product-wise |
| Lead time tracking | Capture time spent per lead / per stage |
| Lead date fields | `lead_date` (when lead was created/received) distinct from `next_follow_up_at` — already partially present |
| Assigned employee remarks per stage | Stage-wise remark history |
| Lead reports — product-wise | Filter leads by product, date range, employee, status; export |

---

## 10. Bulk Import Framework

### Implemented

| Feature | Detail |
|---------|--------|
| Attendance bulk import | Date-wise daily summary Excel; cross-business validated; import log with errors |
| Asset bulk import | Excel template with all asset fields; validation errors returned row-by-row |
| Asset import template download | Pre-formatted Excel template for users |
| Employee bulk import | Basic employee import (within EmployeeService) |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Employee bulk import — full UI + validation report | Upload → preview validation errors → confirm → import; downloadable error report |
| Payroll bulk import | Import payroll adjustments / overrides (incentives, arrears, extra deductions) per employee per month |
| Leave balance bulk import / grant | Upload leave balances for multiple employees at once |
| Standardized import framework | Unified import pipeline reused across modules: validate → preview → confirm → log |

---

## 11. Reporting & Analytics

### Implemented

| Feature | Detail |
|---------|--------|
| Sales reports | Filter by date, customer, product; Excel + PDF export |
| Purchase reports | Filter by vendor, date; export |
| Payment reports | Filter by status, date; export |
| Customer reports | Export |
| Inventory reports | Stock levels, low-stock alerts |
| Attendance monthly summary export | Excel with present / absent / late / OT per employee |
| Asset register export | Full asset list with all fields |
| Asset assignment / maintenance exports | History exports |
| Payslip PDF | Per-employee per-month payslip |
| Activity log (audit trail) | Across all models (Spatie Activitylog) |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| HR reports — Employee Master | Filterable, exportable employee list |
| Payroll reports | Monthly payroll summary; department-wise; component-wise breakdown |
| Leave reports | Balance report, usage report, lapse report, carry-forward report |
| Attendance reports | Date-wise detail, employee-wise summary, late / absent / OT reports |
| Expense reports (employee claims) | Claim-wise, employee-wise, category-wise |
| Lead reports | Product-wise, source-wise, status-wise, employee-wise |
| Recruitment reports | Source-wise conversion, stage-wise funnel |
| Custom Report Builder | Admin selects columns from any module, applies filters, saves report template, exports — e.g. Employee Master with only: Employee Code, Name, Bank Account, ESI Number, EPS Number |
| Dashboard analytics | Module-wise KPI cards; charts for attendance, leave, payroll, asset utilization |

---

## 12. Platform Infrastructure

### Implemented

| Feature | Detail |
|---------|--------|
| Multi-tenant (business-scoped) architecture | Every record scoped to `business_id`; global admin manages all businesses |
| Role-based access control (RBAC) | Spatie Laravel Permission; granular permissions per module and action |
| Admin portal | Full admin panel for all HRMS modules |
| Employee self-service portal | Employee dashboard, attendance, leave, payslip, document upload, appraisal, profile |
| Authentication | Separate guards for Admin and Employee; secure login / password reset |
| Activity logging | Spatie Activitylog on all critical models |
| Notification system | Email + in-app bell notifications; per-event configurable; notification log |
| Scheduler / cron | Laravel scheduler with daily / recurring tasks (see schedule in `routes/console.php`) |
| Dark mode | Admin and employee portals support light / dark / system theme |
| PDF generation | Payslips, invoices, asset reports, quotations |
| Excel export (Maatwebsite Laravel Excel) | Multiple modules |
| Biometric device API integration | Real-time sync every minute with overlap protection |
| Docker / production deployment | Docker Compose + production config; DB backup cron |

### Pending / New Requirement

| Feature | Notes |
|---------|-------|
| Push / mobile notifications | If mobile app is in scope |
| SSO / LDAP integration | Not in current scope; can be added |
| API layer for third-party integrations | REST API with token auth for external systems (biometric is already done; generalize) |

---

## Configuration Parameters (All Fully Dynamic)

All the following values are **admin-configurable per business** — no hard-coded constants:

| Parameter | Where Configured | Example Default |
|-----------|-----------------|-----------------|
| Probation period (days) | Business / HR Settings | 90 days |
| Leave accrual rate — SL | Leave Type settings | 0.5 per month |
| Leave accrual rate — CL | Leave Type settings | 0.5 per month |
| Leave accrual rate — EL | Leave Type settings | 0.5 per month |
| Working days required for EL eligibility | Leave Type settings | 240 days |
| Leave TAT window (hours) | Business Policy settings | 72 hours |
| Attendance correction TAT (hours) | HR Settings | 48 hours |
| Ticket escalation threshold (days) | TMS Settings | 3 days |
| Break duration for 0.5 LOP | Attendance / Payroll settings | Configurable |
| PF percentage (employer + employee) | Salary Structure | 12% each |
| ESI percentage | Salary Structure | 3.25% employer / 0.75% employee |
| LWF amount and frequency | Business Compliance settings | State-specific |
| Professional Tax slab | Business Compliance settings | State-specific |
| Carry-forward cap (EL) | Leave Type settings | Configurable |
| Bulk leave allocation frequency | Leave Policy settings | Monthly / Half-Yearly / Annual |

---

## Pending Decisions (from Client Side)

These items need client sign-off before development can begin:

1. **Leave Approval Workflow** — should HR also be a parallel approver, or only reporting manager?
2. **Break Policy for 0.5 LOP** — exact threshold (e.g. break > 30 min = 0.5 LOP); applies per day or cumulative?
3. **Business-wise Payroll Configuration** — when multiple companies exist, do they share salary structure templates or are they fully independent?
4. **Expense Approval Workflow** — who approves employee claims? Reporting manager → accounts, or direct to accounts?
5. **Escalation Matrix Levels for TMS** — how many escalation levels? Who is level 1, level 2, etc.?
6. **Recruitment Screen Recording / Demo Flow** — client to share screen recording to finalize UI/UX for hiring pipeline.

---

## Scope Clarification — Attendance API Situation

The client indicated "no more work" after the biometric attendance API was delivered. However, the new requirement document introduces three features that were **not part of the original attendance API scope**:

1. Employee self-service attendance regularization (missed punch / correction requests)
2. Ticket-based correction workflow with HR approval and audit trail
3. 48-hour TAT enforcement with escalation / flagging

These are **new functional requirements** and are quoted separately in Module 3 Pending above.

---

## Summary of What Is Built vs. What Is New

| Module | Built | New / Pending |
|--------|-------|---------------|
| Recruitment | 0% | 100% |
| Payroll | ~60% (structure, payslip, PF/ESI/PT) | ~40% (TDS engine, LWF, challans, LOP policy, bulk ops) |
| Attendance | ~85% (biometric, import, manual, punch lock, comp-off) | ~15% (regularization tickets, TAT) |
| Leave | ~40% (types, balances, requests, approval) | ~60% (accrual cron, probation gate, backdated block, bulk grant, year-end lapse) |
| Expense | ~50% (company expenses, recurring, reminders) | ~50% (employee claims, budget, requisitions, approval matrix) |
| Employee Documents | ~70% (upload, admin view, types) | ~30% (verification workflow, notifications, bulk download) |
| TMS | ~40% (customer service tickets, comments) | ~60% (internal helpdesk, escalation matrix, email/in-app notifications, TAT) |
| Asset & Inventory | ~80% (master, assignment, maintenance, depreciation, import/export) | ~20% (bulk UI operations, bulk location transfer, extended reports) |
| Lead | ~80% (master, activities, pipeline) | ~20% (product-wise segmentation, lead time, product reports) |
| Bulk Import | ~40% (attendance, asset, basic employee) | ~60% (full employee UI, payroll import, leave import, standardized framework) |
| Reporting & Analytics | ~35% (sales, purchase, inventory, asset exports) | ~65% (HR/payroll/leave/attendance/expense/lead reports, custom builder) |

---

*Document generated from live codebase analysis. All feature descriptions reflect the actual implemented models, controllers, services, and scheduled jobs as of the current commit.*
