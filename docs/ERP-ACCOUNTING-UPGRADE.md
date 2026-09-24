# ERP Accounting Upgrade — Implementation Blueprint

**Repository:** erptechindia.tech ("ALTechnics ERP") · Laravel 13.8.0 · PHP 8.3+ (8.4.20 on the box) · MySQL 8.0
**Written:** 2026-07-24, from a full read of the codebase at commit `49b9905` (branch `feature/hrms-addons-scope`).
**Goal:** turn a document-printing ERP (Leads → Quotations → SO → Invoices, plus HRMS) into a system that produces **books of account** — Trial Balance, P&L, Balance Sheet — that a Chartered Accountant will sign, and that answer the owner's test case:

> Raw Material 1000 + 200 Additional Cost + 600 Margin = 1,800. 900 cha maal sale kelay. 1000 kuthe, 200 kuthe, 900 kuthe show karnar? Remaining chi original value kay ahe?

The complete worked answer, with journal entries and automated-test assertions, is in **Section 5 (Golden Tests)**. Everything else in this document exists to make that section pass.

---

## 0. How to use this document

You are a fresh Claude Code session (or a human engineer) with zero memory of how this document was produced. Everything you need is in this file plus the repository itself. Follow these rules:

1. **Work Section 6 (Implementation Backlog) strictly in task order.** Task IDs are ordered by dependency; TASK-001 is buildable against the current repo with nothing else done. Never start a task whose `Depends on` list is not fully complete.
2. **One task per commit (minimum).** After finishing a task: tick its `- [x] Status: done` checkbox **in this file**, run the task's named test, and commit both the code and the updated checkbox together. Commit message format: `ACC: TASK-0NN <task title>`.
3. **Never mark a task done unless its named test passes.** If a test cannot pass because of a defect in this spec, fix the spec in the same commit and note the change under Section 9.
4. **Run `php artisan accounting:verify-integrity` after completing every milestone** (the command is built in TASK-016 and extended later). A milestone is not done while this command reports a single failure.
5. **DECISION blocks.** Section 9 lists every judgement call with a recommended default. Each default is safe to implement without asking anyone. Only stop and ask the owner if you intend to *deviate* from a recommended default.
6. **Do not touch the existing HRMS/asset/service-ticket modules** except at the explicit integration points named in tasks (payroll posting, reimbursement posting, expense posting).
7. **Money arithmetic**: from TASK-001 onward, all new money math goes through `App\Support\Money\Money` (BCMath). Never do ledger arithmetic in PHP `float`. Every task that touches money names its rounding boundary; respect it exactly.
8. **Never write a migration that edits or deletes posted accounting rows.** Corrections are reversal journals. The only exception is the append-only `audit_trail` retention job specified in TASK-007.
9. **Tenancy**: every new table carries `business_id` and every new model uses the existing `App\Support\Tenancy\BelongsToBusiness` trait, with the two deliberate exceptions listed in Section 3.1.9 (`voucher_types`, `tds_sections` global masters). All new tables live on the **default MySQL connection** — the app is single-database, shared-schema multi-tenant (see §1.3).
10. **Existing tests must stay green.** `php artisan test` (sqlite in-memory suite, 79 tests) and `php artisan test --configuration=phpunit.addons.xml` (MySQL suite) both pass today; they must pass after every task.
11. **Feature flag**: all accounting behaviour is gated per business by `businesses.accounting_enabled` + `businesses.books_start_date` (added in TASK-010). With the flag off, the app behaves exactly as today. Do not remove existing behaviour until the milestone that explicitly replaces it.
12. When this file and the code disagree about the current state of the repo, trust the code and record the discrepancy in Section 9.

Vocabulary note: this document says **"post"** to mean "create the immutable journal + lines for a document inside a DB transaction". A document that has been posted is **"finalised"**; before that it is a **draft** and has zero accounting effect.

---
## 1. Current state audit

Everything below was verified by reading the code on 2026-07-24. File paths are real; open them.

### 1.1 Stack & infrastructure

| Item | Fact | Evidence |
|---|---|---|
| Framework | Laravel **13.8.0** | `composer.lock` (`laravel/framework v13.8.0`) |
| PHP | `^8.3` required; 8.4.20 installed | `composer.json:9`, `php -v` |
| Package manager | Composer + npm (`package-lock.json`) | repo root |
| Database | **MySQL 8.0** (`mysql:8.0` image), `strict => true` | `docker-compose.prod.yml`, `config/database.php:60` |
| Migrations | Standard Laravel migrations, 139 files | `database/migrations/` |
| Queue / Cache / Session | all `database` driver | `.env` (`QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`); Redis container exists but is unused by config |
| Scheduler | 14 jobs in `routes/console.php` (invoice overdue marking, recurring expenses, leave accrual, biometric sync every minute, `backup:run --only-db` daily 02:00) | `routes/console.php:17-54` |
| Storage | `local` + `public` disks; attachments under `businesses/{id}/…` | `.env`, `app/Services/ExpenseService.php:63-67` |
| PDF | `barryvdh/laravel-dompdf` v3.1.2, `Pdf::loadView('admin.<module>.pdf', …)` | e.g. `app/Http/Controllers/Admin/InvoiceController.php:157` |
| Excel | `maatwebsite/excel` 3.1.69; exports in `app/Exports/` (`FromCollection` + `WithHeadings`), imports via `app/Services/Import/BulkImportService.php` (validate → preview → confirm → log) | `app/Exports/`, `app/Http/Controllers/Admin/BulkImportController.php` |
| Activity log | `spatie/laravel-activitylog` 5.0.0 — most models use `LogsActivity` with `logFillable()->logOnlyDirty()` | e.g. `app/Models/Quotation.php:51-57` |
| Backup | `spatie/laravel-backup` 10.2.1, DB-only nightly | `routes/console.php:20` |
| Tests | PHPUnit 12.5.24. **19 test files / 79 test methods.** Main suite on sqlite `:memory:` (`phpunit.xml`); an "Addons" suite runs on MySQL `erptechindia_test` (`phpunit.addons.xml`). No coverage tooling configured. | `tests/`, both phpunit XMLs |
| CI | **None.** No `.github/`, no pipeline config anywhere. Deploy is docker-compose + supervisor (`deploy/supervisor/`, `Makefile`). | repo root |
| Auth | 3 guards: `web` (unused `App\Models\User`), **`admin`** (`App\Models\Admin`, the ERP panel), `employee` (`App\Models\Employee`, self-service portal, custom `tenant-aware-eloquent` provider) | `config/auth.php`, `app/Providers/AppServiceProvider.php:33` |
| Permissions | `spatie/laravel-permission` 7.4.1, single guard `admin`, `teams => false`. Naming: **`module.action`** snake_case (`quotations.view`, `expenses.mark_paid`, `payments.delete`, `report_sales.view`). Roles seeded: Super Admin, Admin, Business Admin, Sales, Inventory, **Accounts**, Service, HR Manager, Viewer. Super Admin bypasses via `Gate::before` (`app/Providers/AppServiceProvider.php:53-57`). Checks are inline `abort_unless(Auth::guard('admin')->user()->can('…'), 403)` at the top of every controller action — no policies, no route `can:` middleware. | `database/seeders/RolePermissionSeeder.php:17-242` |
| API surface | **None.** Server-rendered Blade only; no `routes/api.php`, no Inertia/Livewire/SPA. Frontend = Tailwind 3.4 + Alpine.js (static files under `public/assets/js`), reusable Blade components (`<x-layout.admin>`, `<x-admin.data-table>` with AJAX partial refresh). | `bootstrap/app.php`, `resources/views/components/` |
| Timezone / locale | `Asia/Kolkata`; dates rendered DD-MM-YYYY via `@formatDate` Blade directive; money rendered inline as `{{ $business->currency_symbol ?? '₹' }}{{ number_format($v, 2) }}` in ~125 views — **there is no money/currency helper and no Money type anywhere** | `config/app.php:75`, `app/Providers/AppServiceProvider.php:60-67` |
| Ops smell | `.env` has `APP_ENV=production` **with `APP_DEBUG=true`** — unrelated to accounting but fix it (TASK-001 note) | `.env` |

### 1.2 Multi-tenancy — the model every new table must follow

Hand-rolled **shared-schema, shared-database tenancy with a `business_id` column** on every tenant table. No package.

- `businesses` table is the tenant root (`database/migrations/2026_03_29_054132_create_businesses_table.php`) and already carries statutory identity: `gst`, `pan`, `cin`, address/state, `currency_code/symbol`, and per-document prefixes (`invoice_prefix`, `quotation_prefix`, `sales_order_prefix`, `po_prefix`, `grn_prefix`, `proforma_prefix`).
- Models opt in with the `App\Support\Tenancy\BelongsToBusiness` trait, which registers a global `BusinessScope` (`WHERE table.business_id = ?`, **fail-closed** to `1 = 0` when no business is resolved) and auto-fills `business_id` on create. `app/Support/Tenancy/{BelongsToBusiness,BusinessScope,CurrentBusiness}.php`.
- `CurrentBusiness` is a container singleton hydrated by the `business` middleware (`app/Http/Middleware/EnsureBusinessContext.php`): regular admins are pinned to their own `business_id`; Super Admin picks one via session and `resolveRouteBinding` auto-switches when they open another tenant's record (`app/Support/Tenancy/BelongsToBusiness.php:39-105`).
- The `settings` table is **global** (unique `key`, no `business_id`) — per-business config belongs on the `businesses` table, not in `settings`. `database/migrations/2026_04_10_100001_create_settings_table.php`.

**Consequence for this project:** every accounting table gets `business_id` + `BelongsToBusiness`, lives on the single default MySQL connection, and raw/report queries must filter `business_id` explicitly (see the existing pattern at `app/Http/Controllers/Admin/DashboardsController.php:694-698`).

### 1.3 Domain model — money-bearing tables as they exist today

All monetary columns are `DECIMAL` — **no FLOAT/DOUBLE columns exist** (verified by grep across `database/migrations/`). The precision defect is in PHP, not storage: every service casts to `(float)` and does IEEE-754 arithmetic with a trailing `round(…, 2)` (e.g. `app/Services/QuotationService.php:119-128`, `app/Services/InvoiceService.php:139-201`). Storage precisions are inconsistent: documents `15,2`, expenses `14,2`, payroll `12,2`, penalties `10,2`; `quantity` is `15,2` (2 dp is too coarse for kg/litre/metre trades).

| Table | Monetary columns (type) | Notes |
|---|---|---|
| `quotations` | subtotal, discount_value, tax_amount, grand_total (15,2); tax_percent (5,2) | header-level single tax %; soft-deletes; `deleted_by` |
| `quotation_items` | quantity, rate, line_total (15,2); discount_percent, tax_percent (5,2) | `line_total` is **tax-inclusive** (see §1.5) |
| `sales_orders` / `sales_order_items` | same shape as quotations | status: pending→confirmed→processing→shipped→delivered / cancelled |
| `proforma_invoices` / items | same + `advance_received` (15,2) | advances captured as a plain number — no receipt record, no GST-on-advance |
| `invoices` | subtotal, discount_value, tax_amount, grand_total, amount_paid, balance_due (15,2) | denormalised paid/due; status unpaid/partial/paid (+`overdue` set by `invoices:mark-overdue`) |
| `invoice_items` | quantity, rate, line_total (15,2) | `line_total` is **tax-exclusive** — a different formula from every other document (§1.5) |
| `payments` | amount (15,2) | customer receipts only; **`invoice_id` NOT NULL** → advances/unallocated receipts are unrepresentable; no vendor payments table exists at all |
| `purchase_orders` / items | same shape as quotations | |
| `goods_receipts` / `goods_receipt_items` | quantity_received (15,2) only — **no rate, no value** | GRN carries zero cost information |
| `stock_movements` | quantity (15,2) only — **no rate, no value, no running balance** | `type` in/out/adjustment; `adjustment` is always **added** (`SUM(CASE WHEN type IN ('in','adjustment') THEN quantity ELSE -quantity END)` — `app/Services/InventoryService.php:33-35`), so a negative correction must be entered as a negative quantity |
| `products` | purchase_price, selling_price, mrp (15,2); tax_percent (5,2) | list prices, not costs; `hsn_code` free-text; no item type, no valuation method, no UoM conversions |
| `customers` / `vendors` | credit_limit (15,2) / — | **two separate party tables**; `gst_number` free-text, never validated, no checksum, no state-code derivation, no PAN, no registration type; `state` is a free string |
| `expenses` | amount (14,2) | category+subcategory; recurring templates; status unpaid/paid/cancelled; **no GST/ITC fields, no capital-vs-revenue flag, no vendor link, no TDS** |
| `expense_budgets` | amount (14,2) | per category × period (× optional employee); utilisation computed live |
| `requisitions` | requested_amount, estimated_amount (14,2) | multi-level approval; **does not link to purchase_orders**; disbursement writes only `disbursed_at` + `payment_reference` |
| `reimbursement_claims` | amount, approved_amount (12,2) | submitted→under_review→approved→disbursed/rejected; disbursement writes no financial record |
| `payslips` | basic…other_allowance, bonus, gross_earnings, pf, esi, professional_tax, tds, penalty_deduction, lop_deduction, other_deductions, total_deductions, net_pay (all 12,2) | statuses draft/generated/paid; **posting: none** |
| `salary_structures` / `salary_templates` | components (12,2), ctc_annual (14,2), pf_percent/esi_percent (5,2) | maker-checker exists here (pending/approved/rejected — `2026_05_05_100002_add_approval_to_salary_structures.php`) — the only approval workflow on money in the app |
| `payroll_adjustments`, `penalties`, `penalty_types`, `tds_slabs`, `appraisals` | 12,2 / 10,2 / 14,2 | tds_slabs = Section 192 income-tax slab bands per business per FY |
| `leads` | expected_value (15,2) | statuses: new/attempted/contacted/qualified/**evaluation** (renamed from proposal, `2026_07_10_000002…`)/won/lost; recent additions: product_id, lead_date, city/state, bid_number, ra_emd (`2026_06_06_130001…`, `2026_07_09_000001…`) |

Numbering: every document number is generated by string-sorting the latest existing number and adding 1 — e.g. `Quotation::withTrashed()->where('quotation_number','like',$prefix.'%')->orderByDesc('quotation_number')->first()` then `+1` (`app/Services/QuotationService.php:20-33`; same pattern in `InvoiceService`, `PaymentService`, `SalesOrderService`, `PurchaseOrderService::receiveGoods`, `ExpenseService`). Properties: (a) **race-prone** — two concurrent creates read the same "last" number; the unique index then throws a 500; (b) resets on the **calendar year** (`date('Y')`), not the financial year, so an invoice series spans two GST document-series periods; (c) string ordering breaks when a series crosses 4 digits (`…-10000` sorts before `…-9999`).

Soft deletes: **every document table including `invoices` and `payments` is soft-deletable**, and the controllers expose it (`invoices.delete`, `payments.delete` permissions — `app/Http/Controllers/Admin/InvoiceController.php:126-139`, `PaymentController.php:116-128`). There is no guard preventing deletion of a paid invoice; deleting a tax invoice breaks GSTR-1 document-series continuity and Rule 3(1) audit-trail expectations. Section 3.1.7 addresses this.

### 1.4 Behavioural audit — the three flows, traced

**Flow A: Quotation created → sent → accepted → Sales Order → Invoice.**
`QuotationService::create()` writes `quotations` + `quotation_items` in a transaction (`app/Services/QuotationService.php:38-64`). `QuotationController::updateStatus` flips the status string through draft→sent→accepted/rejected/expired and fires a notification — nothing else (`app/Http/Controllers/Admin/QuotationController.php:177-214`). `convertToSalesOrder()` copies the rows into `sales_orders` (`QuotationService.php:192-233`). `SalesOrderService::updateStatus('confirmed')` writes **quantity-only** `stock_movements` (type `out`) against the default warehouse — at *confirmation*, not dispatch, so stock is decremented before goods move (`app/Services/SalesOrderService.php:119-164`). `generateInvoice()` copies the SO into `invoices` (`SalesOrderService.php:169-202`). **Total financial effect of the entire chain: zero.** No cost is recorded, no revenue is recognised anywhere except as unposted document rows that dashboards later SUM over.

**Flow B: Expense recorded → marked paid.**
`ExpenseService::create()` writes one `expenses` row (`app/Services/ExpenseService.php:27-71`). `markPaid()` sets `status='paid'`, `paid_date`, `payment_method` (a free string: cash/bank/cheque/upi/card), `payment_reference` (`ExpenseService.php:101-113`). Where did the money come from? **Unknowable.** There is no cash ledger, no bank account entity, no credit to anything. The same is true of reimbursement disbursement (`app/Services/ReimbursementService.php:35-66`) and requisition disbursement (`app/Services/RequisitionService.php:111-121`): a status flip plus a reference string. Payroll: `PayrollService::generate()` computes PF (12% of basic), ESI (0.75% employee under a hardcoded ₹21,000 gross threshold), PT, penalties and writes **one `payslips` row** (`app/Services/PayrollService.php:162-281`); `StatutoryService` derives the employer-side register (EPS 8.33% capped at ₹15,000 PF wage, employer ESI 3.25%) for Excel export only (`app/Services/StatutoryService.php:23-101`). Note the wiring gap: `TdsService::monthlyTds()` — a real Section 192 slab engine with 4% cess (`app/Services/TdsService.php:59-83`) — **has no callers**; the payslip's `tds` field is a manually keyed flat amount from `salary_structures.monthly_tds`.

**Flow C: the "profit" numbers.** Two places claim to show profit:
1. Executive dashboard (`app/Http/Controllers/Admin/DashboardsController.php:689-810`):
   - `'gross_margin' => $revMtd - $poMtd` (line 720) where `$revMtd` = SUM of `invoices.grand_total` (GST-**inclusive**) dated in the range and `$poMtd` = SUM of `purchase_orders.grand_total` (also GST-inclusive, whether or not goods were received, billed, or sold) dated in the range.
   - `marginTrend` repeats the same subtraction monthly (lines 779-793).
   - `topMargin` = `SUM(si.quantity * (p.selling_price - p.purchase_price))` — the *current master list prices*, applied retroactively to every historical sale (lines 795-803).
   - `net_cash_flow => $payMtd - $poMtd` (line 721) subtracts purchase *orders* from customer receipts — POs are not payments; vendors cannot even be paid in this system.
2. Sales report / dashboards "revenue" = SUM(`invoices.grand_total`) — includes output GST in revenue (`app/Services/DashboardService.php:31-34`, `app/Services/ReportService.php:17-46`).

**Verdict, bluntly: the current profit report is fiction.** It compares two GST-inclusive document totals from different periods of different lifecycles, contains no COGS, no stock, no expenses, no payroll, and its per-product margin assumes cost and price never change. Buying ₹10 lakh of stock in March makes March look loss-making and April fabulous. Nobody can file anything from these numbers, and if they do, they are filing wrong numbers.

There is also a document-consistency defect chain worth stating plainly (details §1.5): a customer can accept a quotation for one grand total, receive a proforma for a second, and be invoiced a third — from the same line items.

### 1.5 Correctness defects found while tracing (each becomes a gap-analysis row)

1. **Two different totalling algorithms.** Quotations/Proformas/SOs/POs: `line_total = (qty × rate − line-disc%) × (1 + line-tax%)` (tax-inclusive), then header discount, then **header tax again on top** (`app/Services/QuotationService.php:116-161`, `SalesOrderService.php:209-251`, `PurchaseOrderService.php:195-237`). Invoices: `line_total = qty × rate − line-disc%` (tax-exclusive), then per-line tax pro-rated after header discount (`app/Services/InvoiceService.php:139-201`). Consequences: (a) mixing line tax + header tax on a quotation **double-taxes**; (b) `SalesOrderService::generateInvoice` copies SO items into `InvoiceService::create`, which recomputes under the other algorithm — the invoice total can silently differ from the accepted SO total. The repair migration `database/migrations/2026_07_17_100002_backfill_line_totals_for_documents.php` documents the quotation-side formula as intended behaviour, so this is a live design fork, not a typo.
2. **`percent` vs `percentage` discount bug, still live in two services.** `QuotationService::calculateTotals` was fixed to accept both spellings (comment at `QuotationService.php:142-146`); `SalesOrderService.php:237` and `PurchaseOrderService.php:222` still test `=== 'percentage'` while the forms submit `percent` — a percentage discount on an SO/PO is silently applied as a **flat rupee amount**.
3. **Quotation PDF recomputes totals at render time** (`app/Http/Controllers/Admin/QuotationController.php:147-160`) instead of printing stored values — the printed document can disagree with the list/detail screens whenever stored math and render math diverge (which §1.5.1 makes possible).
4. **GST does not exist.** `tax_percent` is a single undifferentiated rate. No CGST/SGST/IGST split, no place-of-supply, no reverse charge, no HSN validation, no rate-wise summary, no rounding to the rupee, no e-invoice/IRN, no e-way bill, no GSTR-anything. Grep for `gst` in `app/` matches only the `BelongsToBusiness` trait name. The only GST fields anywhere are free-text `gst_number` on customers/vendors and `gst` on businesses.
5. **Inventory is a quantity counter, not a value.** `stock_movements` has no cost; `current_stock` is a SUM over all history with no as-on-date capability and no per-warehouse balance snapshot; stock is decremented at SO *confirmation* (not delivery), never reserved, never valued; GRN receives quantities with no rate; nothing ever computes COGS. Negative stock is silently allowed.
6. **Payments are single-invoice, customer-side only.** No advances, no allocation across invoices, no vendor payments, no bank/cash accounts, no reconciliation. `payments.delete` erases money movement without trace beyond the activity log.
7. **Mutability everywhere.** Every document (including invoices with payments against them) is editable and soft-deletable at any status; item rows are deleted and recreated on every update (`InvoiceService.php:84-91`), destroying item identity; there is no period close, no locking, no reversal concept.
8. **Numbering** — race-prone, calendar-year-reset, string-sorted (§1.3).
9. **Rounding** — `round()` scattered per-service at different boundaries; PHP float arithmetic throughout; invoice totals not rounded to the rupee, so documents legally carry paise the customer never pays; no Round-Off ledger.
10. **The audit trail is optional.** `spatie/laravel-activitylog` records fillable-dirty diffs, but: the `activity_log` table can be truncated by anyone with DB access, log entries are skipped when writes bypass Eloquent events (bulk `DB::table()` updates like the backfill migration), and nothing records *reads* or login/lock events. Rule 3(1) of the Companies (Accounts) Rules 2014 (audit-trail proviso, mandatory since 1 April 2023) expects an edit log that cannot be disabled. Current state does not meet it.

### 1.6 What already exists that the upgrade must reuse (not rebuild)

- Tenancy trait + middleware + fail-closed scope (§1.2).
- Permission seeding pattern: `$modules` map in `database/seeders/RolePermissionSeeder.php` + migration per new permission batch (pattern: `database/migrations/2026_06_07_110004_add_expense_module_permissions.php`); `Accounts` role is the natural home for new grants.
- `<x-layout.admin>` + `<x-admin.data-table>` (AJAX partial refresh), numbered paginator (`resources/views/vendor/pagination/numbered.blade.php`), flash conventions, `@formatDate`.
- FormRequest-per-action in `app/Http/Requests/Admin/`.
- `Pdf::loadView('admin.<module>.pdf', …)` templates; currency precedence `$business->currency_symbol ?? $settings['currency_symbol'] ?? '₹'`.
- `GenericArrayExport` + `BulkImportService`/`RowImporter` pipeline for Excel in/out.
- `spatie/laravel-activitylog` stays for UI-level "who touched this" history; the statutory `audit_trail` table (TASK-006) is additive, not a replacement.
- Scheduler registration style in `routes/console.php`; command classes in `app/Console/Commands/`.
- Seeder-per-module pattern in `database/seeders/` and the test conventions: `Tests\Traits\CreatesAdminUsers::seedPermissions()/createAdminWithRole()`, `RefreshDatabase`, `#[Test]` attributes (`tests/Feature/QuotationTest.php:17-60`).

---
## 2. Gap analysis

### 2.1 Capability scorecard

Statuses: **Absent** = does not exist. **Partial** = exists but incomplete. **Present-but-wrong** = exists and produces numbers someone will trust and file. Present-but-wrong items are listed first because they are actively dangerous.

| # | Capability | Status | Statutory risk | Business impact | Effort | Priority |
|---|---|---|---|---|---|---|
| 1 | Profit / margin reporting (`gross_margin = invoices − POs`, product margin from list prices — `DashboardsController.php:720,795-803`) | **Present-but-wrong** | Owner files ITR/GST from fictional profit → misstatement; Sec 271 penalties possible | Owner cannot know if the business makes money; pricing decisions taken on fiction | M (delete + replace with real P&L) | **P0** |
| 2 | Revenue figures (SUM of GST-inclusive `grand_total` — `DashboardService.php:31-34`) | **Present-but-wrong** | Turnover overstated by the GST component (~18%); wrong 44AB/e-invoice threshold conclusions | Every dashboard number inflated | S (fix with taxable-value fields in M4) | **P0** |
| 3 | Document totalling (two algorithms; double-tax; `percent/percentage` bug — §1.5.1-2) | **Present-but-wrong** | Invoice ≠ accepted order value; GST computed on wrong base | Customer disputes; unexplainable totals | M | **P0** |
| 4 | Stock quantity tracking (no value, decrement at SO-confirm, negative allowed) | **Present-but-wrong** | Closing stock unverifiable → AS-2/ICDS-II non-compliance; 44AB clause 14 unanswerable | "Remaining chi original value kay ahe?" — unanswerable today | L (M2 rebuilds it) | **P0** |
| 5 | Deletable/mutable financial documents (paid invoices, payments) | **Present-but-wrong** | Rule 3(1) audit trail; GSTR-1 doc-series gaps (Table 13); Sec 128 books-of-account integrity | CA cannot trust any list; fraud surface | M | **P0** |
| 6 | Double-entry ledger (journals, CoA, TB) | Absent | Sec 128 Companies Act: books on accrual & double-entry basis — companies using this ERP are non-compliant | No TB/P&L/BS; CA re-enters everything into Tally at year-end | XL | **P0** |
| 7 | Money arithmetic discipline (PHP floats, scattered `round()`) | Partial (DECIMAL storage is fine) | Paise drift across documents/ledgers | Ledgers that don't tie to the paisa | S | **P0** |
| 8 | Statutory audit trail (activitylog is bypassable/truncatable) | Partial | **Rule 3(1), Companies (Accounts) Rules 2014** — audit trail must be tamper-proof, always-on; auditor must report on it (CARO/Sec 143) | Audit qualification | M | **P0** |
| 9 | Inventory valuation & COGS (FIFO/WA, landed cost, dispatch-time COGS) | Absent | **AS-2 / ICDS-II** (cost of purchase + costs to bring to location/condition; lower of cost & NRV) | The owner's ₹200 gets expensed or lost; profit wrong every month there's stock movement | XL | **P0** |
| 10 | GST engine (POS, CGST/SGST/IGST split, RCM, rate-wise summaries, rupee rounding) | Absent | CGST/SGST Acts; Sec 31 invoice rules; Rule 46 (mandatory particulars incl. HSN) | Users compute GST outside the ERP; invoices arguably not valid tax invoices | XL | **P0** |
| 11 | GST returns data (GSTR-1 tables, GSTR-3B, 2B reconciliation) | Absent | Sec 37/39 CGST; ITC only via matched 2B (Sec 16(2)(aa), Rule 36(4)) | CA charges more; ITC leaks | L | **P1** |
| 12 | Purchase bills / vendor invoices (P2P stops at GRN) | Absent | ITC has no source document (Sec 16(2): possession of tax invoice required); Sec 43B(h) MSME disallowance untrackable | Payables unknown; vendor ageing impossible | L | **P0** |
| 13 | Vendor payments & payment allocation | Absent | Rule 37 (180-day ITC reversal) untrackable; 194Q/TDS on payment leg impossible | Cash position unknowable | M | **P0** |
| 14 | Bank & cash accounts, BRS | Absent | 44AB clause on cash transactions; Sec 269ST monitoring impossible | No reconciliation; theft/undetected errors | L | **P1** |
| 15 | Advances (customer receipt without invoice is unrepresentable — `payments.invoice_id` NOT NULL) | Absent | GST on advances for services (Sec 12/13 time-of-supply) | Proformas carry `advance_received` as a dead number | M | **P1** |
| 16 | Credit/debit notes | Absent | Sec 34 CGST (CDNR in GSTR-1) | Returns/price revisions handled by editing history | M | **P1** |
| 17 | TDS/TCS on trade (194C/J/H/I/Q…) | Absent (only Sec 192 salary slabs exist, and even those aren't wired — §1.4 Flow B) | Chapter XVII-B: disallowance 40(a)(ia) 30%, interest 201(1A), fees 234E | Vendors' TDS handled outside; 26Q prepared by hand | L | **P1** |
| 18 | Fixed asset register + dual depreciation | Absent (IT-asset tracker exists — `assets` table tracks laptops/joiners, has no cost ledger link) | Schedule II useful lives; IT Act blocks u/s 32; CARO 3(i) | BS shows no assets; capital expenses likely sitting in `expenses` as revenue | L | **P1** |
| 19 | Expense capitalisation / prepaid / accrual | Absent | AS-based accrual (Sec 128 "accrual basis"); 43B items | Monthly P&L wrong (rent/insurance lumps) | M | **P1** |
| 20 | Financial statements (TB, Trading, P&L, BS, Cash Flow, Schedule III) | Absent | Schedule III presentation; AS-3 cash flow | The literal ask of the owner | L (once ledger exists) | **P0** |
| 21 | Period locking / FY concept | Absent | Books must be closed per FY (Apr–Mar); today numbering resets on calendar year | Prior-year figures mutate after filing | S | **P0** |
| 22 | Party statutory identity (GSTIN validation, PAN, state code, MSME/Udyam) | Absent | Rule 46 invoice particulars; 206AB-era PAN checks; Sch III MSME split; 43B(h) | Wrong-state tax splits inevitable | S | **P0** |
| 23 | Payroll → books (salary JV, PF/ESI/PT/TDS payable ledgers) | Absent | Sec 36(1)(va)/43B: employee-contribution due-date tracking needs payable ledgers | Largest expense of a service SME invisible in P&L | M | **P1** |
| 24 | E-invoice (IRN/QR) & e-way bill | Absent | Rule 48(4)/(5): for mandated taxpayers an invoice without IRN "shall not be treated as an invoice"; Rule 138 | Blocks any customer above ₹5 cr AATO from adopting | L | **P2** |
| 25 | CA collaboration (portal, query tracker, handover pack) | Absent | — (differentiator, not compliance) | The wedge — see §2.3 | L | **P2** |
| 26 | Tally XML interop | Absent | — | Removes the #1 switching objection | M | **P2** |
| 27 | Concurrency safety on money writes (row locks, idempotency) | Partial (`DB::transaction` wraps writes; no locks — numbering races, `receiveGoods` double-submit creates double stock) | — | Duplicate numbers/movements under two clicks | M | **P0** |
| 28 | Cost centres / project P&L (quoted vs actual) | Absent | — | Owner's real question ("did this job make money?") | M | **P2** |

### 2.2 What breaks in a real audit — the questions the system cannot answer today

*From the statutory auditor (Companies Act):* "Give me your trial balance as at 31 March with opening balances, and show me that closing stock of ₹X ties to purchase and consumption records" — there is no trial balance, and stock has quantities but no values. "Show me the audit trail for this invoice that was edited in May" — activitylog shows a diff, but the auditor's next question, "prove this log cannot be switched off or truncated," has no good answer (Rule 3(1) reporting under CARO). "Why is a paid invoice deletable?" — it just is.

*From the GST officer (ASMT-10 / DRC-01):* "Your GSTR-3B claimed ITC of ₹Y; produce the purchase register with invoice-level tax splits and prove payment within 180 days (Rule 37)" — there is no purchase register (POs are not purchases), no tax splits, no vendor payment records at all. "Reconcile the document series declared in GSTR-1 Table 13 with your books" — numbering resets on 1 January, mid-financial-year, and soft-deleted invoices leave silent holes.

*From the income-tax side (44AB Form 3CD):* Clause 14 (method of stock valuation and deviation from ICDS-II) — no valuation method exists. Clause 21/26 (40(a)(ia), 43B items incl. 43B(h) MSME payables) — no payables ledger, no MSME flag, no TDS on vendors. Clause 40 (GP/NP ratios) — the only "profit" number is §1.4 Flow C fiction.

*From the CA who receives the books:* today the client exports Excel lists of invoices, POs and expenses. Debtors don't tie to receipts (deleted payments), purchases don't exist as bills, stock has no value, salaries live in a separate module, and the CA re-keys everything into Tally. The ERP is, at year-end, a data-entry burden rather than a source of books. That is the single blocker to this product being an "ERP" in the Indian sense, and it is what Section 3 fixes.

### 2.3 Competitive position

Honest benchmark against the incumbents an Indian SME will compare this to. (Pricing is indicative list pricing as of mid-2026; verify before quoting in marketing.)

| Capability | TallyPrime | Zoho Books | Busy | Marg | Vyapar | ERPNext | Refrens | **This ERP today** | **This ERP after M0–M7** |
|---|---|---|---|---|---|---|---|---|---|
| Double-entry core | ✅ gold standard | ✅ | ✅ | ✅ | ⚠️ simplified | ✅ | ⚠️ invoice-first | ❌ | ✅ |
| GST returns (GSTR-1/3B, 2B recon) | ✅ | ✅ | ✅ | ✅ | ⚠️ 1 only | ⚠️ India preset, patchy 2B | ⚠️ | ❌ | ✅ |
| E-invoice / e-way bill | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ via connectors | ✅ | ❌ | ✅ (M4, §3.6.6) |
| Inventory costing (FIFO/WA, landed cost) | ✅ | ⚠️ FIFO only, landed cost clunky | ✅ | ✅ | ⚠️ | ✅ | ❌ | ❌ qty only | ✅ WA/FIFO + landed cost |
| Job/project costing with quote-vs-actual | ⚠️ cost centres, no quote link | ⚠️ Projects in higher plans, no quote-vs-actual | ⚠️ | ⚠️ | ❌ | ⚠️ possible, heavy setup | ❌ | ❌ | ✅ native (lead→quote→job→books) |
| CRM / lead → quote continuity | ❌ (none) | ⚠️ separate Zoho CRM, sync tax | ❌ | ❌ | ❌ | ⚠️ separate module | ⚠️ | ✅ already native | ✅ |
| HRMS + payroll in same system | ❌ (TallyPrime payroll is weak) | ❌ (separate Zoho Payroll) | ⚠️ | ⚠️ | ❌ | ⚠️ HR module | ❌ | ✅ already native | ✅ posts to books |
| CA collaboration | ⚠️ file sharing / Tally on cloud | ⚠️ accountant login, no query tracker | ❌ | ❌ | ❌ | ❌ | ⚠️ | ❌ | ✅ portal + query tracker + CA Pack |
| Mobile | ⚠️ view apps | ✅ | ⚠️ | ⚠️ | ✅ best-in-class | ⚠️ | ✅ | ❌ (responsive Blade only) | ⚠️ responsive web (native out of scope) |
| API / openness | ❌ closed XML/ODBC | ✅ good REST | ⚠️ | ⚠️ | ❌ | ✅ fully open | ⚠️ | ❌ none | ⚠️ internal JSON endpoints (public API out of scope here) |
| Multi-business in one login | ⚠️ multi-company, one machine | ⚠️ per-org billing | ⚠️ | ⚠️ | ⚠️ | ✅ | ⚠️ | ✅ native multi-tenant | ✅ |
| Price feel | ₹750+/mo/user or perpetual | ₹0–₹2,999/mo per org | perpetual + AMC | perpetual + AMC | ₹0–₹4k/yr | free software, paid hosting/partners | freemium | bundled with ERP | bundled |

**The three wedges this ERP can honestly win on (argued from the table):**

1. **Lead → Quotation → Job → Books in one primary key.** Tally starts at the voucher; Zoho splits CRM and Books into two products with a sync boundary; ERPNext can be configured into it but nobody's SME does. This repo *already owns* the front half (leads with bid numbers/EMD fields, versionable quotations, SOs) — after M3 every rupee of actual cost and revenue carries the `project_id`/lead lineage, so "quoted margin 600, realised margin 512, variance = freight underquoted" is a native report (§3.9.13) no incumbent produces without consultants.
2. **A CA collaboration layer that treats the auditor as a first-class user.** Zoho's accountant seat is a login, not a workflow. Tally's answer is "send me the data file." The CA Pack (one button → indexed, reconciled, checksummed ZIP — §3.10) plus a time-boxed read-only portal with a voucher-level query tracker and CA-proposed closing entries (§3.10.3) turns the annual "bhej do Excel" ritual into a product feature. CAs then *recommend* the product — a distribution channel none of the incumbents has built.
3. **HRMS + payroll already in the same schema as the books.** The repo's deepest existing asset (attendance, payslips, PF/ESI/PT computation, reimbursements) is exactly what Tally lacks and Zoho sells separately. One posting service (§3.5.7, TASK-047) makes salary cost, department-wise, land in the same P&L as the jobs those people worked on — service-sector SMEs (the quotation module's audience) get labour-inclusive project margins nobody else gives them at this price point.

Where we will *not* beat incumbents in this programme and should not pretend: native mobile apps, a public REST API, and the 30-year trust moat of Tally's ledger engine. The counter is interop, not replacement: the Tally XML bridge (§3.10.4) lets a sceptical CA verify our books *in Tally itself* — the fastest possible trust transfer.

---
## 3. Target architecture

Conventions used throughout this section:

- **Namespaces.** Models: `App\Models\Accounting\*` (new sub-namespace; the repo already sub-namespaces controllers `Admin\Hr\*` and services `Services\Asset\*`). Services: `App\Services\Accounting\{Posting,Costing,Gst,Tds,Reports,Statements,Tally,CaPack}\*`. Controllers: `App\Http\Controllers\Admin\Accounting\*`. Views: `resources/views/admin/accounting/**`. Requests: `App\Http\Requests\Admin\Accounting\*`.
- **Routes.** All under the existing `Route::middleware(['auth:admin','business'])` group in `routes/web.php`, inside `Route::prefix('accounting')->name('accounting.')`. Server-rendered Blade + the existing `<x-admin.data-table>`; JSON is returned only by the small endpoints marked (json) for Alpine widgets, matching the existing `inbox/unread-count` style.
- **Permissions.** `module.action` on guard `admin`, seeded via a migration per milestone (repo pattern `2026_06_07_110004_add_expense_module_permissions.php`) and added to `database/seeders/RolePermissionSeeder.php`. New modules: `accounting.*`, `vouchers.*`, `inventory_valuation.*`, `gst.*`, `tds.*`, `fixed_assets.*`, `banking.*`, `statements.*`, `ca_portal.*`, `tally.*`. Grant view/create to the existing **Accounts** role; grant `accounting.lock_period`, `accounting.unlock_period`, `vouchers.post_backdated`, `accounting.manage_coa` only to Admin/Business Admin.
- **Tenancy.** Every table below has `business_id` + `BelongsToBusiness` except the two global masters flagged §3.1.9. DDL for every table is canonical in **Section 4**; this section describes shape and behaviour.
- **Money.** All arithmetic through `App\Support\Money\Money` (TASK-003): immutable, BCMath strings, internal scale 6. Ledger boundary: **every journal-line amount is rounded half-up to 2 dp at posting time**; nothing after posting ever re-rounds. Quantities/rates: scale 6 in DB (`DECIMAL(20,6)`), amounts `DECIMAL(20,4)` in ledger tables (stored values will always end in `00` given the 2 dp policy — the extra headroom is deliberate so a future policy change needs no schema change).

### 3.1 The Ledger Core

**Why (for the non-accountant):** double-entry means every rupee has a source and a destination recorded simultaneously (a debit and an equal credit), so the whole system can be proven consistent by adding two columns. Everything else in this document is just machinery that writes these pairs automatically.

**Tables (DDL §4.1):** `fiscal_years`, `accounting_periods`, `ledger_accounts`, `voucher_types` (global), `voucher_sequences`, `journals`, `journal_lines`, `account_balances`, `audit_trail`, `cost_centres`, `projects`.

**Classes:**

| Path | Responsibility |
|---|---|
| `app/Support/Money/Money.php` | BCMath value object: `Money::of('1234.56')`, `plus/minus/times/dividedBy/allocate(int[] $ratios)/isNegative/compareTo/toDecimalString(int $scale)`; `allocate` uses largest-remainder so parts always sum exactly to the whole |
| `app/Models/Accounting/{FiscalYear,AccountingPeriod,LedgerAccount,VoucherType,Journal,JournalLine,CostCentre,Project,AccountBalance}.php` | Eloquent models; `Journal`/`JournalLine` have **no** `SoftDeletes`; `Journal::booted()` throws `App\Exceptions\Accounting\ImmutableJournalException` on `updating`/`deleting` when `status === 'posted'` unless the update is the whitelisted reversal transition |
| `app/Services/Accounting/Posting/JournalPostingService.php` | The only write-path: `post(JournalDraft $draft): Journal` and `reverse(Journal $j, \DateTimeInterface $onDate, string $reason): Journal` |
| `app/Services/Accounting/Posting/JournalDraft.php` | Plain builder: voucher type, date, narration, source model, idempotency key, `debit(accountCode|id, Money, dims)` / `credit(...)` |
| `app/Services/Accounting/Posting/VoucherNumberService.php` | `next(int $businessId, string $voucherTypeCode, FiscalYear $fy): string` — locks the `voucher_sequences` row (`SELECT … FOR UPDATE`), increments, returns `PREFIX/FY/NNNN` |
| `app/Services/Accounting/Posting/PeriodGuard.php` | `assertOpen(int $businessId, \DateTimeInterface $date)` — throws `PeriodLockedException` if the FY or month is locked or the date < `books_start_date` |
| `app/Services/Accounting/ChartOfAccountsService.php` | CRUD for accounts; auto-creates party sub-ledgers (§3.1.4); guards `is_system` |
| `app/Services/Accounting/AccountBalanceService.php` | Applies posted lines to `account_balances` inside the posting transaction; `rebuild(int $businessId)` recomputes from `journal_lines` |
| `app/Console/Commands/Accounting/VerifyIntegrity.php` | `accounting:verify-integrity {--business=}` — §3.11.4 checks |
| `app/Console/Commands/Accounting/RebuildBalances.php` | `accounting:rebuild-balances {--business=}` |
| `database/seeders/ChartOfAccountsSeeder.php` | Seeds §3.1.2 groups + §3.1.3 system ledgers for one business; called on business creation (`app/Services/BusinessService.php`) and by TASK-012 backfill for existing businesses |

#### 3.1.1 Chart of Accounts shape

`ledger_accounts` is a self-referencing tree (`parent_id`), unlimited depth. Each row: `code` (unique per business, 4-to-8-char, pattern below), `name`, `nature` enum(`asset`,`liability`,`equity`,`income`,`expense`), `normal_balance` enum(`debit`,`credit`), `is_group` bool (groups cannot be posted to — enforced in `JournalPostingService`), `affects_gross_profit` bool (true for Sales/Purchase/Direct groups — drives the Trading account), `schedule_iii_head` (slug from §3.9.6 list), `tally_group_name` (exact TallyPrime group string for XML export), `is_system` bool (undeletable, unrenamable except display alias), optional `party_type`/`party_id` (morph to `App\Models\Customer` / `App\Models\Vendor` / `App\Models\Employee` for auto-created sub-ledgers), optional `bank_account_id` (§3.9.9). Code blocks: `1xxx` assets, `2xxx` liabilities, `3xxx` equity, `4xxx` income, `5xxx` expenses; children extend the parent code (`1201`, `1201-0001` for parties). Codes are for CAs and imports; the UI searches by name.

#### 3.1.2 Seeded groups — the 28 Tally-standard groups

Seeded per business, `is_group = true`, `is_system = true`. (15 primary + 13 sub-groups. `affects_gross_profit` marked GP.)

| Code | Group | Parent | Nature | Normal | Schedule III head | GP |
|---|---|---|---|---|---|---|
| 3000 | Capital Account | — | equity | credit | share_capital | |
| 3100 | Reserves & Surplus | Capital Account | equity | credit | reserves_and_surplus | |
| 2000 | Loans (Liability) | — | liability | credit | long_term_borrowings | |
| 2100 | Secured Loans | Loans (Liability) | liability | credit | long_term_borrowings | |
| 2200 | Unsecured Loans | Loans (Liability) | liability | credit | long_term_borrowings | |
| 2300 | Bank OD A/c | Loans (Liability) | liability | credit | short_term_borrowings | |
| 2400 | Current Liabilities | — | liability | credit | other_current_liabilities | |
| 2410 | Duties & Taxes | Current Liabilities | liability | credit | other_current_liabilities | |
| 2420 | Sundry Creditors | Current Liabilities | liability | credit | trade_payables | |
| 2430 | Provisions | Current Liabilities | liability | credit | short_term_provisions | |
| 1000 | Fixed Assets | — | asset | debit | tangible_assets | |
| 1100 | Investments | — | asset | debit | non_current_investments | |
| 1200 | Current Assets | — | asset | debit | other_current_assets | |
| 1210 | Stock-in-Hand | Current Assets | asset | debit | inventories | |
| 1220 | Sundry Debtors | Current Assets | asset | debit | trade_receivables | |
| 1230 | Cash-in-Hand | Current Assets | asset | debit | cash_and_equivalents | |
| 1240 | Bank Accounts | Current Assets | asset | debit | cash_and_equivalents | |
| 1250 | Deposits (Asset) | Current Assets | asset | debit | long_term_loans_advances | |
| 1260 | Loans & Advances (Asset) | Current Assets | asset | debit | short_term_loans_advances | |
| 1300 | Misc. Expenses (Asset) | — | asset | debit | other_non_current_assets | |
| 1900 | Suspense A/c | — | asset | debit | other_current_assets | |
| 3900 | Branch / Divisions | — | equity | credit | reserves_and_surplus | |
| 4000 | Sales Accounts | — | income | credit | revenue_from_operations | GP |
| 5000 | Purchase Accounts | — | expense | debit | cost_of_materials | GP |
| 4100 | Direct Incomes | — | income | credit | other_operating_revenue | GP |
| 5100 | Direct Expenses | — | expense | debit | direct_expenses | GP |
| 4200 | Indirect Incomes | — | income | credit | other_income | |
| 5200 | Indirect Expenses | — | expense | debit | other_expenses | |

#### 3.1.3 Seeded system ledgers (postable leaves, `is_system = true`)

| Code | Ledger | Group | Used by |
|---|---|---|---|
| 1231 | Cash | Cash-in-Hand | payment/receipt/contra in mode `cash` |
| 1211 | Stock-in-Hand | Stock-in-Hand | every inventory posting; must equal the stock valuation report at all times |
| 4001 | Sales | Sales Accounts | sales vouchers (per-business extra sales ledgers allowed) |
| 4002 | Sales Returns | Sales Accounts | credit notes (contra-revenue, debit-normal leaf in a credit group — allowed) |
| 5001 | Purchases | Purchase Accounts | presentation ledger for the purchase register figure (§3.3.8); posted and immediately offset — see Trading account note |
| 5002 | Purchase Returns | Purchase Accounts | debit notes |
| 5101 | Cost of Goods Sold | Direct Expenses | COGS on dispatch (Dr) |
| 5102 | Purchase Price Variance | Direct Expenses | GRN-vs-bill rate difference (§3.5.3) |
| 5103 | Stock Adjustment (Shortage/Excess) | Direct Expenses | physical-count variances, write-offs |
| 2411 | Output CGST | Duties & Taxes | sales GST |
| 2412 | Output SGST/UTGST | Duties & Taxes | sales GST |
| 2413 | Output IGST | Duties & Taxes | sales GST |
| 2414 | Output Cess | Duties & Taxes | cess |
| 2415 | Input CGST | Duties & Taxes | ITC (debit-normal leaf in credit group) |
| 2416 | Input SGST/UTGST | Duties & Taxes | ITC |
| 2417 | Input IGST | Duties & Taxes | ITC |
| 2418 | Input Cess | Duties & Taxes | ITC |
| 2419 | GST Cash Ledger (Electronic) | Duties & Taxes | challan payments to the portal cash ledger |
| 2421 | GST RCM Payable | Duties & Taxes | reverse charge liability |
| 2422 | GST on Advances | Duties & Taxes | Sec 12(2) advances on services |
| 2423 | ITC Reversed (Reclaimable) | Loans & Advances (Asset) | Rule 37 parking (§3.6.8) |
| 2431 | TDS Payable | Provisions | parent for per-section children created on first use (`TDS Payable — 194C` …) |
| 2432 | Salaries Payable | Provisions | payroll net pay |
| 2433 | PF Payable / 2434 ESI Payable / 2435 PT Payable / 2436 TDS on Salary Payable (192) | Provisions | payroll statutory |
| 2424 | GRN Clearing (Goods Received Not Billed) | Current Liabilities | between GRN and purchase bill (§3.5.2) |
| 2437 | Audit Fee Provision | Provisions | closing-entry convenience |
| 1261 | TDS Receivable (by customers) | Loans & Advances (Asset) | 194Q/TDS deducted on our receipts |
| 1262 | Employee Advances | Loans & Advances (Asset) | requisition disbursements (§3.5.7) |
| 1263 | Prepaid Expenses | Loans & Advances (Asset) | §3.5.5 amortisation |
| 2438 | Accrued Expenses Payable | Provisions | §3.5.5 |
| 2425 | Advances from Customers | Current Liabilities | receipts without invoice (§3.4.6) |
| 5201 | Round Off | Indirect Expenses | invoice rupee-rounding differences, both signs |
| 5202 | Bank Charges | Indirect Expenses | BRS auto-suggestion target |
| 3101 | Profit & Loss A/c | Reserves & Surplus | year-end close + BS "profit for the period" line |
| 3001 | Owner's Capital | Capital Account | opening balance equity + drawings contra |
| 1901 | Opening Balance Difference | Suspense A/c | only the migration wizard may post here; must be zero before go-live sign-off (§7) |

Expense integration: every `expense_categories` row gets `ledger_account_id` (TASK-055) pointing to an auto-created leaf under Indirect Expenses (or Direct, user-choosable), so today's expense module keeps its UX and gains postings.

#### 3.1.4 Party sub-ledgers

On first financial document for a customer, `ChartOfAccountsService::ensurePartyLedger(Customer|Vendor|Employee $party)` creates a leaf under Sundry Debtors (customers), Sundry Creditors (vendors), or the relevant payable/advance group (employees), name = party name, `party_type/party_id` set, code = group code + `-` + zero-padded party id. Receivables/payables reports read these; the party screens show a ledger tab. Renaming the party renames the ledger (alias kept for Tally export stability).

#### 3.1.5 Journals & lines — the invariants

A `journals` row is a voucher header: `voucher_type_id`, `voucher_number` (unique per business+type+FY), `voucher_date`, `fiscal_year_id`, `accounting_period_id`, `narration`, `reference` (external doc no.), morph `source_type/source_id` (the business document that generated it), `status` enum(`draft`,`posted`,`reversed`), `total_debit`, `total_credit`, `posted_at/by`, `reversal_of_journal_id`, `reversed_by_journal_id`, `idempotency_key`. Lines carry: `line_no`, `ledger_account_id`, `debit`, `credit` (exactly one non-zero, both ≥ 0), and the dimensions: `party_type/party_id`, `cost_centre_id`, `project_id`, `product_id`, `quantity`, `tax_rate_percent`, `hsn_code`, per-line `narration`.

`SUM(debit) = SUM(credit)` is enforced at three layers, exactly as required:

1. **DB layer.** `journals` carries `CHECK (total_debit = total_credit)` (MySQL 8.0.16+ enforces CHECK); `journal_lines` carries `CHECK (debit >= 0 AND credit >= 0 AND (debit = 0) <> (credit = 0))`; trigger `trg_journals_post_guard BEFORE UPDATE ON journals` verifies, when `NEW.status='posted' AND OLD.status='draft'`, that `(SELECT COALESCE(SUM(debit),0) FROM journal_lines WHERE journal_id = NEW.id) = NEW.total_debit` and likewise for credit, else `SIGNAL SQLSTATE '45000'`. Triggers `trg_journal_lines_frozen_upd/del` block UPDATE/DELETE of lines whose parent is not `draft`; `trg_journals_frozen_upd/del` block header mutation/deletion after posting except the whitelisted `posted → reversed` status flip that also sets `reversed_by_journal_id`. Full trigger DDL in §4.2.
2. **Model layer.** `JournalPostingService::post()` asserts balance with `Money` before writing anything and wraps insert-header(draft) → insert-lines → flip-to-posted in one `DB::transaction`.
3. **Nightly job.** `accounting:verify-integrity` re-sums every posted journal and the whole TB (§3.11.4), scheduled daily 03:00 in `routes/console.php`.

#### 3.1.6 The posting pipeline (every voucher goes through this)

```
JournalPostingService::post(JournalDraft $draft): Journal
  1. assert $draft balances (Money-exact), has ≥ 2 lines, all accounts postable
     (is_group = false), all accounts belong to $draft->businessId
  2. PeriodGuard::assertOpen(businessId, voucherDate)   // FY exists, month unlocked,
                                                        // date ≥ books_start_date
  3. DB::transaction(attempts: 3):                      // retries deadlocks (1213)
     a. if idempotency_key given: SELECT id FROM journals WHERE business_id=? AND
        idempotency_key=? FOR UPDATE → if found, return that Journal (no-op replay)
     b. VoucherNumberService::next(...)                 // SELECT ... FOR UPDATE on
                                                        // voucher_sequences row
     c. INSERT journals (status='draft', totals)        // draft first so the post-
     d. INSERT journal_lines (bulk)                     // guard trigger can verify
     e. UPDATE journals SET status='posted', posted_at, posted_by
     f. AccountBalanceService::apply(lines)             // ± on account_balances rows
                                                        // (INSERT ... ON DUPLICATE KEY)
     g. audit_trail row (action='posted', new_values = header + line digest)
  4. return Journal
```

Reversal (`reverse()`): builds a mirror-image draft (debits↔credits), `voucher_type` = original's, narration `Reversal of {number}: {reason}`, links both ids, posts through the same pipeline dated `$onDate` (must be an open period — reversing into a locked month is exactly what period locks exist to prevent). Documents that need "cancel" semantics (§3.2) call `reverse()` and set their own status; they never delete.

**Locking order rule (deadlock avoidance):** within any posting transaction, lock in this fixed order: 1) `voucher_sequences` row, 2) domain rows (e.g. stock item balance), 3) inserts. All services in §3.3–§3.8 follow it.

#### 3.1.7 Immutability meets the existing soft-delete world

Rule: **a business document that has posted journals can no longer be edited or soft-deleted.** Implemented once in `app/Support/Accounting/HasPostedJournals.php` trait (adds `journals()` morph, `isFinalised()`, and a `booted()` guard throwing `DocumentFinalisedException` on update/delete when a posted journal exists and the changed attributes are financial). Applied to `Invoice`, `Payment`, `Expense`, `PurchaseBill`, `CreditNote`, `DebitNote`, `DeliveryNote`, `GoodsReceipt`, `Payslip`. The existing `invoices.delete` / `payments.delete` UI actions become **Cancel** (posts the reversal, sets status `cancelled`, keeps the row and its number — GSTR-1 Table 13 needs cancelled numbers listed, not missing). Drafts (nothing posted) keep today's behaviour unchanged.

#### 3.1.8 Fiscal years, periods, locking, numbering

- FY = 1 April – 31 March, rows in `fiscal_years` auto-created on first posting into a new year (`FiscalYear::forDate()`); 12 `accounting_periods` children created with it. Every report defaults to the current FY (`FiscalYear::current($businessId)`).
- Locking: month-level and FY-level `is_locked`. `accounting.lock_period` locks; `accounting.unlock_period` (Admin-only) unlocks and **must** supply a reason → `audit_trail` row `action='period_unlocked'`. Posting, reversing, and cancelling all pass `PeriodGuard`.
- Voucher numbering: **gapless per business + voucher type + FY**, format `{prefix}{FYshort}/{seq}` e.g. `INV/25-26/0042`, prefix from `businesses.*_prefix` columns (reused) or `voucher_types.default_prefix`. Strategy — dedicated `voucher_sequences` row locked with `SELECT … FOR UPDATE` inside the posting transaction (chosen over (a) `MAX()+1` — the current racy bug, (b) AUTO_INCREMENT — global, gappy on rollback, (c) MySQL has no native sequences). Gapless holds because a number is only consumed inside the same transaction that posts the voucher: rollback returns the increment. Sequence rows are seeded lazily on first use of a (type, FY).
- Drafts have **no number** (display `DRAFT-{id}`); numbers exist only on posted vouchers. This is what makes gapless possible and is standard Tally behaviour (optional vouchers).

#### 3.1.9 The two global (non-tenant) masters

`voucher_types` (13 rows, §3.2) and `tds_sections` (§3.7) are system reference data shared by all businesses: no `business_id`, no `BelongsToBusiness`, seeded by migration, mutable only via new migrations. Everything else in this document is tenant-scoped.

#### 3.1.10 Audit trail (Rule 3(1) — this is a legal requirement, not a nice-to-have)

Proviso to Rule 3(1), Companies (Accounts) Rules 2014 (effective 1 April 2023): accounting software used by a company must record an audit trail of **every** create/change/delete of accounting entries, with date stamps, which **cannot be disabled**, and auditors must report on it. Design:

- `audit_trail` table (§4.1): `business_id`, `user_guard`+`user_id`, `action` enum(`created`,`updated`,`deleted`,`posted`,`reversed`,`cancelled`,`period_locked`,`period_unlocked`,`sequence_adjusted`,`login_as`,`export_generated`), morph `auditable_type/id`, `old_values` JSON, `new_values` JSON, `ip`, `user_agent`, `occurred_at` (server time, indexed).
- Written synchronously inside the same DB transaction as the change, by `App\Support\Accounting\AuditTrail::record(...)` — called from `JournalPostingService`, the `HasPostedJournals` guard, period lock/unlock, and an observer on every accounting model.
- **Tamper resistance:** DB triggers `trg_audit_trail_no_update` / `trg_audit_trail_no_delete` raise `SIGNAL SQLSTATE '45000'` unconditionally — the app cannot edit or delete rows even with a bug. There is no artisan command that truncates it. Retention: the table is partitioned by `YEAR(occurred_at)`; the only removal path is `accounting:prune-audit-trail` which **refuses** any cutoff younger than 8 full financial years (Sec 128(5) Companies Act / Sec 36 CGST both require 8 years) and logs its own run into the trail first.
- The existing `spatie/laravel-activitylog` continues untouched for non-accounting models; accounting models write to **both** (activitylog for the UI timeline, audit_trail for the statutory record).

**Endpoints & screens (M1):** `accounting/chart-of-accounts` (tree CRUD, `accounting.manage_coa`), `accounting/journals` + `accounting/journals/create` (manual Journal voucher with maker-checker per §3.11.3, `vouchers.create`/`vouchers.post`), `accounting/journals/{journal}` (voucher view, printable, shows reversal chain), `accounting/periods` (lock/unlock UI), `accounting/day-book` (§3.9.1). All list pages use `<x-admin.data-table>`.

**Edge cases:** posting dated 31 March 23:59 vs FY boundary (date decides, never `created_at`); voucher on a date before `books_start_date` (rejected); two tabs posting the same draft (idempotency key = `journal-draft:{draft id}` makes the second a no-op); account made inactive while a draft references it (post rejects, message names the account); deadlock between two sequences (retry x3 then surface); reversal of a reversal (allowed — chain is walkable both ways); manual journal directly to Stock-in-Hand or GST output ledgers (blocked for non-system sources — these balances must tie to their sub-modules; override permission `vouchers.post_to_controlled` exists for the CA's closing entries, and every use is an audit_trail event).

**Module tests:** balanced-post happy path; unbalanced draft rejected at service AND at trigger layer (attempt raw SQL flip); posted journal UPDATE/DELETE rejected by trigger; period lock blocks post/reverse; unlock writes audit row; concurrent `next()` under 32 parallel workers yields strictly consecutive unique numbers (addons suite, MySQL — sqlite cannot test this); idempotent replay returns same journal id; balance materialisation equals `SUM(journal_lines)` after 1,000 random postings; audit_trail UPDATE rejected by trigger.

### 3.2 Voucher types

Thirteen types, seeded in `voucher_types` with `code`, `name`, `default_prefix`, `numbering` (`gapless`), `is_system=true`. For each: the fields of its capture screen, exactly what it posts, its inventory and GST effects, and what it does **not** do. "Party ledger" = the §3.1.4 sub-ledger. All GST splits per §3.6.2; all amounts 2 dp half-up at posting.

| # | Voucher (code) | Captures | Debits | Credits | Inventory effect | GST effect | Does NOT |
|---|---|---|---|---|---|---|---|
| 1 | **Sales (SAL)** | invoice fields §3.4.5 | Party (Sundry Debtor) gross; Round Off if negative diff | Sales (taxable value); Output CGST+SGST or IGST; Round Off if positive diff | none (DN does it) | output liability rows in `gst_ledger_entries`, bucketed for GSTR-1 | does not touch stock or COGS; does not recognise receipt |
| 2 | **Purchase (PUR)** | purchase bill §3.5.3 | GRN Clearing (billed-qty value at GRN rate); Purchase Price Variance (±); Input CGST/SGST/IGST (eligible ITC); expense/asset ledger for non-stock lines | Party (Sundry Creditor) gross; TDS Payable — {section} if applicable | none (GRN did it) | ITC rows in `gst_ledger_entries` (eligible/blocked per §3.6.7) | does not receive stock; does not pay the vendor |
| 3 | **Payment (PAY)** | payee (vendor/expense/other ledger), bank/cash account, mode, ref, allocations | Party or expense ledger | Bank/Cash ledger | none | none (except RCM cash-basis entries §3.6.5) | does not create the liability it settles |
| 4 | **Receipt (RCT)** | customer, bank/cash, mode, ref, allocations §3.4.6 | Bank/Cash | Party (Sundry Debtor) or Advances from Customers | none | GST-on-advance rows when unallocated + services (§3.4.6) | does not touch the invoice's revenue |
| 5 | **Contra (CON)** | from-account, to-account (both cash/bank group) | destination Bank/Cash | source Bank/Cash | none | none | nothing outside cash/bank groups (validated) |
| 6 | **Journal (JRN)** | free lines (maker-checker §3.11.3) | any postable | any postable | none | none | cannot hit controlled ledgers without `vouchers.post_to_controlled` (§3.1 edge cases) |
| 7 | **Debit Note (DBN)** | vendor, against purchase bill, reason (Sec 34 mirror), lines | Party (Creditor) | Purchase Returns (value); Input GST reversal lines | optional stock-out (return-to-vendor toggle → stock ledger issue at original lot cost) | reduces ITC in `gst_ledger_entries` | does not refund cash (that's a Receipt from vendor / Payment reversal) |
| 8 | **Credit Note (CRN)** | customer, against invoice, reason, lines §3.4.7 | Sales Returns (taxable); Output CGST/SGST/IGST (reversal) | Party (Debtor) | optional stock-in at original COGS (restock toggle) | CDNR/CDNUR rows for GSTR-1 | does not un-issue the original invoice |
| 9 | **Stock Journal (STJ)** | consume lines / produce lines, BOM optional §3.3.7 | Stock-in-Hand (produced value) | Stock-in-Hand (consumed value); conversion-cost ledger credit if added | out(consume) + in(produce) in stock ledger | none | no party, no GST |
| 10 | **Delivery Note (DLN)** | SO/invoice link, warehouse, qty §3.4.4 | Cost of Goods Sold | Stock-in-Hand | issue at engine cost (§3.3.4) | none | no revenue, no debtor |
| 11 | **Receipt Note (RCN)** = GRN | PO link, qty received §3.5.2 | Stock-in-Hand (qty × PO rate) | GRN Clearing | receipt into stock ledger + FIFO lot | none (ITC waits for the bill) | no creditor yet |
| 12 | **Sales Order (SO)** | existing screen | — | — | none (reservation is a soft qty, §3.3.9) | none | **posts nothing** — the current stock-out-on-confirm behaviour is removed in TASK-041 |
| 13 | **Purchase Order (PO)** | existing screen | — | — | none | none | **posts nothing** |

Every posting above goes through §3.1.6 with `source_type/source_id` = the document, and an idempotency key `{table}:{id}:{event}` (e.g. `delivery_notes:88:finalise`) so re-clicks and queue retries are no-ops.

### 3.3 Inventory & costing — where the owner's ₹1,000 + ₹200 lives

**Why:** profit on a sale is sale price minus what the goods *actually cost you to get sellable* — purchase price **plus** freight/labour/etc. (AS-2 ¶6-7: costs of purchase + costs incurred in bringing inventories to their present location and condition). If the ₹200 freight went to P&L as an expense in month 1 and the goods sold in month 2, both months' profits are wrong. So the ₹200 is *capitalised* into stock value and comes out through COGS as goods are dispatched.

**Tables (DDL §4.3):** `uoms`, `stock_ledger_entries`, `stock_lots`, `landed_cost_vouchers`, `landed_cost_lines`, `stock_transfers` + `stock_transfer_items`, `stock_takes` + `stock_take_lines`, `boms` + `bom_lines`, `stock_journals` + `stock_journal_lines`; **alterations** to `products` (item_type, uom_id, alt_uom_id, alt_conversion_factor, valuation_method, standard_cost, is_batch_tracked, hsn validation, gst_rate reuse of `tax_percent`, opening fields) and `warehouses` (none needed beyond existing).

**Classes:** `app/Services/Accounting/Costing/StockPostingService.php` (`receive()`, `issue()`, `transfer()`, `adjust()` — every call appends `stock_ledger_entries` rows and returns the costed value), `WeightedAverageEngine.php`, `FifoEngine.php`, `StandardCostEngine.php` (all implementing `CostingEngine` interface: `costOfIssue(product, warehouse, qty): Money` + `applyReceipt(...)`), `LandedCostService.php`, `StockValuationReport.php`, `NrvAssessmentService.php`, `app/Http/Controllers/Admin/Accounting/{StockLedgerController, LandedCostController, StockTransferController, StockTakeController, BomController}.php`.

#### 3.3.1 Item master changes

`products` gains: `item_type` enum(`goods`,`service`,`raw_material`,`finished_good`,`consumable`) default `goods`; `uom_id` FK → `uoms` (seeded: PCS, NOS, KG, G, TON, L, ML, M, CM, SQM, BOX, SET, PAIR, DOZ, ROLL, BAG, BTL, CAN, DRUM, HR, DAY, JOB — each with UQC code for e-invoice/e-way, e.g. KG→`KGS`, PCS→`PCS`); optional `alt_uom_id` + `alt_conversion_factor DECIMAL(20,6)` (1 BOX = 24 PCS ⇒ factor 24, entry screens accept either unit and store base); `valuation_method` enum(`weighted_average`,`fifo`,`standard`) **nullable — null means "use business default"** (`businesses.default_valuation_method`, default `weighted_average`); `standard_cost DECIMAL(20,6)`; `is_batch_tracked`/`is_serial_tracked` booleans (schema now, engines enforce batch in M2, serial deferred — noted in TASK-021's code comment); `reorder_level` becomes `DECIMAL(20,6)` (was int). `hsn_code` gains format validation (4/6/8 numeric) at the FormRequest layer. Services (`item_type = service`) never touch the stock ledger.

#### 3.3.2 The stock ledger (`stock_ledger_entries`)

Append-only. Each row: product, warehouse, `entry_date`, `direction` (`in`/`out`), `quantity` (>0, base UoM, 6 dp), `rate` (6 dp), `value` (= round(qty × rate, 2) — **rounding boundary: line value 2 dp half-up here and nowhere else**), `balance_qty_after`, `balance_value_after` (running, per product × warehouse × business), morph `source_type/source_id`, `journal_id` (the voucher that carried the value), `lot_id` (FIFO). Ordering key for running balances: (`entry_date`, `id`). Backdated entries are **rejected** if any later entry exists for that product+warehouse unless the user holds `inventory_valuation.backdate` — in which case all later `balance_*_after` and dependent WA rates are recomputed synchronously and the affected journals compared; if any posted COGS journal's value would change, the service posts value-adjustment journals (Dr/Cr COGS ↔ Stock-in-Hand) rather than editing history. This is the hardest invariant in M2; TASK-034 owns it.

#### 3.3.3 Valuation engines

- **Weighted average (default):** moving average per product × business (not per warehouse — transfers between own godowns don't change value; DECISION-07). New WA rate after a receipt = (existing value + receipt value) / (existing qty + receipt qty), kept at 6 dp; issues cost = qty × current WA rate, value rounded 2 dp.
- **FIFO:** receipts create `stock_lots` (qty_in, qty_remaining, unit_cost 6 dp); issues consume lots oldest-`received_on`-first (tie-break lot id), possibly splitting; issue value = Σ round(lot-slice qty × lot cost, 2) per slice... **no**: value = round(Σ(slice qty × lot cost), 2) — single rounding per issue line (boundary as §3.3.2).
- **Standard:** issues and receipts at `standard_cost`; purchase-vs-standard differences post to Purchase Price Variance at bill time.
- Landed cost (§3.3.5) raises lot cost / WA value; if some of the lot already sold, the portion attributable to sold qty posts `Dr COGS / Cr Stock-in-Hand` correction instead of inflating remaining stock (keeps remaining stock at true per-unit cost).
- Changing an item's valuation method is allowed only at a date with zero stock on hand, or via a stock-take-style revaluation voucher (difference → Stock Adjustment ledger). Enforced in `ProductService`.

#### 3.3.4 COGS on dispatch — the golden rule

`DeliveryNoteService::finalise()` (§3.4.4) calls `StockPostingService::issue()` per line → engine cost → posts **one** DLN journal: `Dr Cost of Goods Sold {total} / Cr Stock-in-Hand {total}` with per-line dimensions (product, qty, project, cost centre). **COGS never reads quotation margin, list `purchase_price`, or the invoice.** Partial dispatch = proportional cost of the actual lots/average at that moment. Services lines: no COGS here (see §3.4.8 for service WIP).

#### 3.3.5 Landed cost / additional cost apportionment (the owner's ₹200)

`landed_cost_vouchers`: linked GRN(s) (or, for the cash-freight case, any stock still on hand from those GRNs), one or more cost lines — each: `cost_type` enum(`freight`,`loading`,`packing`,`customs_duty`,`labour`,`insurance`,`other`), amount, **apportionment basis per line** enum(`by_value`,`by_quantity`,`by_weight`) (weight uses a per-line weight input when products lack one), paid-to (vendor for credit, or cash/bank for immediate), GST fields (ITC eligible? freight under GTA-RCM handled via §3.6.5 flag). Apportionment: `Money::allocate` largest-remainder across target GRN lines so paise sum exactly. Posting: `Dr Stock-in-Hand {apportioned Σ} / Cr Vendor|Cash|Bank {total}`; ITC lines to Input GST as applicable; engines update WA value / lot costs; already-sold portion → COGS correction (§3.3.3). AS-2 note printed on the voucher screen so users stop expensing freight.

#### 3.3.6 NRV (lower of cost or net realisable value)

Period-end tool, not a daily posting: `NrvAssessmentService` builds a working paper — per item: qty on hand, carrying cost, NRV input (default = current `selling_price` minus a per-assessment estimated cost-to-sell %), writedown = max(0, cost − NRV) — user confirms → posts `Dr Stock Adjustment / Cr Stock-in-Hand` per item, stores the working paper (`stock_takes.kind = 'nrv'` reusing the stock-take tables with a kind column) for the CA Pack (06-Inventory). Reversal next period if NRV recovers (AS-2 ¶24 style) is a manual reverse from the stored voucher.

#### 3.3.7 BOM & manufacturing (raw material → finished goods)

`boms`: finished product, output qty, lines (component product, qty per output, scrap %). **Stock Journal** voucher (§3.2 #9): consume side (components issued at engine cost) + produce side; produced value = Σ consumed value + optional conversion-cost lines (labour/power ledger credits, capitalising conversion cost per AS-2 ¶8) − by-product credit lines; produced unit cost = produced value / produced qty. WIP: a two-step flow uses an intermediate WIP item (`item_type` includes treating a product as WIP via its stock); single-step manufacture posts consume→produce in one voucher. Golden-test-2 (services WIP) uses ledgers, not items (§3.4.8).

#### 3.3.8 Purchase register vs perpetual postings (read this before touching the Trading account)

The ledger is **perpetual**: purchases debit Stock-in-Hand (via GRN Clearing), dispatches credit it — that is the only way "Stock-in-Hand ledger = stock report, any date" can hold. But CAs expect a Trading account showing *Purchases* and *Closing Stock*. Resolution: the Trading account (§3.9.4) **derives** its lines: Purchases = Σ inventory-value of Purchase vouchers in period (from `journal_lines` where source is a purchase bill and account is GRN Clearing/Stock — tagged `line_role = 'purchase_value'` on the line for a clean query); Direct expenses = Direct Expenses group excluding COGS ledger; Opening/Closing Stock = Stock-in-Hand ledger balance at the period edges; Gross Profit = Sales − (Opening + Purchases + Direct − Closing). The integrity command asserts this equals Sales − COGS − (other Direct expenses) from the perpetual side, to the paisa. Both worldviews reconcile; neither is stored twice. (`journal_lines.line_role` enum column exists for exactly this: `purchase_value`, `cogs`, `round_off`, `tax`, `tds`, `landed_cost`, `normal` default.)

#### 3.3.9 Godowns, transfers, stock takes, reservations

- Transfers: `stock_transfers` post paired out/in stock-ledger rows at carrying cost (no P&L effect, no journal needed since Stock-in-Hand→Stock-in-Hand; a journal IS posted when businesses later enable per-warehouse ledgers — not in scope; single Stock-in-Hand ledger now).
- Stock take: `stock_takes` snapshot counted vs book qty per warehouse; variance posts Stock Journal-style: shortage `Dr Stock Adjustment / Cr Stock-in-Hand` at engine cost, excess opposite at last cost (or standard). Sign-off field for the CA pack.
- Reservation: SO confirmation sets `sales_order_items.reserved_qty` (soft), shown as "available = on hand − reserved" on product screens; **no ledger effect** (replaces today's premature stock-out, removed in TASK-041).
- Negative stock: blocked by default (`businesses.allow_negative_stock = false`); when true (trader who bills before GRN lands), issues at WA rate-so-far and the integrity report carries a permanent warning until healed (DECISION-08).

**Endpoints & screens (M2):** `accounting/stock/ledger` (per product/warehouse register with running qty+value), `accounting/stock/valuation` (as-on-date, must equal ledger 1211 — badge shows PASS/FAIL live), `accounting/landed-costs` CRUD+finalise, `accounting/stock-transfers`, `accounting/stock-takes` (+ variance posting), `accounting/boms`, `accounting/stock-journals`. Permissions: `inventory_valuation.view/create/finalise/backdate`, `stock_takes.count/approve`, `boms.manage`.

**Edge cases:** issue qty > on hand (blocked / DECISION-08); landed cost arriving after 100% of lot sold (whole amount → COGS correction); landed cost across multiple GRNs with by-weight basis and a zero-weight line (validation error, names the line); FIFO issue spanning 3 lots where paise don't divide (single-rounding rule §3.3.3); WA with return-to-vendor at older cost (debit note uses original lot/WA-at-receipt cost captured on the GRN line, not today's WA); backdated GRN before existing issues (TASK-035 recompute path); UoM alternate entry (2 BOX → 48 PCS stored); product deactivated with stock on hand (blocked).

**Module tests:** golden test §5.1 end-to-end; WA vs FIFO §5.1 assertion 8; landed-cost allocation sums exactly (property-style test over random paise); valuation-equals-ledger invariant after a scripted 40-event sequence incl. transfers and a stock take; negative-stock block; idempotent GRN finalise.

### 3.4 Quote-to-Cash

**Why:** a quotation's margin is a *hope*; accounting recognises revenue when goods are dispatched / services rendered (AS-9 ¶11: transfer of significant risks & rewards). So the chain is: Lead → Quotation (estimate; **posts nothing**) → Sales Order (commitment; posts nothing) → Delivery Note (**COGS posts**) → Tax Invoice (**revenue + GST post**) → Receipt (cash posts) → Credit Note (reversals). Any code that books profit at quotation stage is a defect (§2 rows 1-2); the margin quoted is kept — as an *estimate* to compare against actuals (§3.9.13).

**Tables (DDL §4.4):** `quotation_revisions`, `delivery_notes` + `delivery_note_items`, `receipts` + `receipt_allocations` (supersede `payments` — §3.4.6), `credit_notes` + `credit_note_items`; **alterations:** `quotations` (+`revision_no`, `project_id`), `sales_orders` (+`project_id`, item `reserved_qty`, `delivered_qty`, `billed_qty`), `invoices` (+GST columns §3.6.3, `place_of_supply`, `irn` link, `delivery_note_ids` via pivot `delivery_note_invoice`), `customers` (+`gstin` validation columns §3.6.1).

**Classes:** `app/Services/Accounting/Posting/{SalesInvoicePostingService, ReceiptPostingService, CreditNotePostingService, CogsPostingService}.php`, `app/Services/DeliveryNoteService.php`, `app/Services/QuotationRevisionService.php`, controllers `Admin\Accounting\{DeliveryNoteController, ReceiptController, CreditNoteController}` + surgical edits to existing `InvoiceController`, `QuotationController`, `SalesOrderController`.

#### 3.4.1 Quotation versioning
Editing a `sent|accepted` quotation now snapshots the old header+items JSON into `quotation_revisions` (`revision_no` increments; PDF shows `QUO-…-R2`). Acceptance freezes further edits (revise = new revision, back to `sent`). No accounting effect ever.

#### 3.4.2 One totalling algorithm for every document (fixes §1.5.1-3)
New single source of truth `app/Services/Accounting/DocumentTotalsService.php`: per line — `gross = qty × rate` (Money, 6 dp), `line_discount = gross × disc% ` , header discount allocated over lines by `Money::allocate` proportional to (gross − line_discount), **taxable_value = round(gross − line_disc − allocated_header_disc, 2)** ← rounding boundary; per-line GST on taxable_value per §3.6.2, each tax amount rounded 2 dp per line; document totals = Σ lines; invoice-level rupee rounding §3.6.4. Line `line_total` stored = taxable_value (tax-exclusive) **for all document types** — quotations/SO/PO/proforma migrate to the same meaning (one-off data migration recomputes stored headers for **drafts only**; sent/accepted historical documents keep stored totals and are flagged `legacy_totals = 1`). All five services (`QuotationService`, `SalesOrderService`, `PurchaseOrderService`, `ProformaInvoiceService`, `InvoiceService`) delete their private `normalizeItems`/`calculateTotals` and delegate here (TASK-003). The `percent`/`percentage` bug dies with this task.

#### 3.4.3 Sales Order
Keeps its lifecycle; `confirmed` now only reserves (§3.3.9) and validates credit limit (`customers.credit_limit` finally does something: warn-or-block toggle `businesses.enforce_credit_limit`). Partial flows tracked per item: `delivered_qty`, `billed_qty` maintained by DN/invoice finalisation (row-locked `FOR UPDATE` when updating).

#### 3.4.4 Delivery Note (new document — DLN voucher)
Created from an SO (default: undelivered balance) or standalone for counter sales. Fields: customer, SO link, warehouse, date, transporter fields (feed e-way bill later), lines (product, qty ≤ undelivered). `finalise()`: PeriodGuard → stock issue per line (§3.3.4) → post `Dr COGS / Cr Stock-in-Hand` (idempotency `delivery_notes:{id}:finalise`) → bump `delivered_qty`. Cancel = reverse journal + stock re-receipt at the same per-line issued cost (stored on `delivery_note_items.issued_cost` — never re-averaged) + status `cancelled`. Partial dispatch = multiple DNs per SO.

#### 3.4.5 Tax Invoice
Invoice creation (from SO / from DNs / direct) stays a draft with **no postings**. New `finalise()` action (`invoices.finalise` permission): requires every goods line to be covered by finalised DN qty — if `businesses.auto_delivery_note = true` (default), finalising an invoice with uncovered goods lines auto-creates+finalises a covering DN from the invoice's warehouse in the same transaction (so small traders never see a DN screen; the COGS entry still exists separately, cleanly). Posts the SAL journal (§3.2 #1) with per-line dims + GST rows + rupee round-off; assigns the gapless number **at finalisation** (draft numbering display-only; the `invoice_number` column keeps the posted number; existing pre-accounting invoices keep their historical numbers, flagged `legacy_numbering`). E-invoice hook (§3.6.6) fires after posting for mandated businesses. Cancel (allowed only same GST period + no receipts allocated): reversal journal + DN reversal choice + `gst_ledger_entries` negation + IRN cancellation if within 24 h, else blocked with "issue a Credit Note instead".

#### 3.4.6 Receipts & advances (supersedes `payments`)
`receipts`: customer, date, bank/cash account (§3.9.9 master — replaces the free-string `mode` with a real ledger), amount, TDS-deducted-by-customer amount (posts `Dr TDS Receivable`), allocation lines to invoices (many-to-many at last). Unallocated remainder posts `Cr Advances from Customers`; if the business sells **services** (toggle `businesses.gst_on_advances`, default on when any `item_type = service` sold), the advance's GST portion posts `Dr GST on Advances (2422) / Cr Output CGST/SGST or IGST` computed **inclusive** (advance × rate/(100+rate), 2 dp) per Sec 12(2) time-of-supply; later invoice allocation reverses it. Goods-only advances: no GST (Notification 66/2017-CT). Legacy: TASK-041 migrates every `payments` row into `receipts` + one allocation, then `payments` screens 301-redirect to receipts; the old table stays read-only for URL/history integrity.

#### 3.4.7 Credit Note (CRN)
Against one invoice (Sec 34): reason enum(`sales_return`,`post_sale_discount`,`rate_revision`,`deficiency`), lines ≤ original, restock toggle per line (stock-in at the DN's `issued_cost`, paired `Dr Stock-in-Hand / Cr COGS`). Posts §3.2 #8 + negative `gst_ledger_entries` (CDNR/CDNUR bucket). GST note: output-tax reduction via CN is only claimable per Sec 34(2) timelines — the GSTR-1 builder handles the bucketing; the books post regardless (books ≠ portal timing, the 3B recon (§3.6.9) shows the difference).

#### 3.4.8 Services & milestones (second golden test lives here)
Service quotations/SOs/invoices skip DN/COGS. Project-linked service jobs may accrue cost to WIP: ledger `Work-in-Progress (Services)` created under Current Assets on first use (code 1212): timesheet/labour/vendor costs marked `project_id` + `capitalise_to_wip = true` post `Dr WIP / Cr {source}`; milestone invoice finalisation posts revenue (SAL) **and** releases cost `Dr Cost of Services (5104, Direct Expenses) / Cr WIP` — release amount = WIP balance × (milestone value / total contract value), rounded 2 dp, unless the user overrides with an actual figure (both paths stored on the invoice for the variance report); on the **final** milestone (project completion) the entire remaining WIP releases regardless of proportion, so no cost is stranded on the Balance Sheet. §5.2 tests this exactly.

#### 3.4.9 Quotation-estimate vs actual variance
Because quotation items already carry `rate` and products know engine cost at dispatch, the report (§3.9.13) shows per SO/project: quoted revenue vs invoiced, quoted implied cost (from quotation-time `products.purchase_price` snapshot — stored on `quotation_items.cost_snapshot` at creation, TASK-048 adds the column) vs actual COGS+direct costs, margin delta with drill-down. Sales users see *why* the quote lied; nothing here posts.

**Endpoints & screens (M3):** `accounting/delivery-notes` (+ finalise/cancel), `accounting/receipts` (+ allocate UI showing open invoices with balances), `accounting/credit-notes`; existing invoice screens gain Finalise/Cancel buttons + a "Postings" tab rendering the journal; quotations gain a Revisions tab. Permissions: `delivery_notes.view/create/finalise/cancel`, `receipts.view/create/allocate/delete→cancel`, `credit_notes.view/create/finalise`, `invoices.finalise/cancel` (added; `invoices.delete` remapped to cancel per §3.1.7).

**Edge cases:** DN qty exceeding SO balance (blocked, names the line); invoice from two DNs of different warehouses (allowed; COGS already posted per DN); receipt against a cancelled invoice (blocked); over-allocation (blocked at 100% of grand total); advance later refunded (Payment voucher Dr Advances from Customers / Cr Bank, GST-on-advance reversal posted); rate revision after partial billing (SO edit allowed for undelivered/unbilled balance only — delivered lines frozen); customer with TDS deduction and part payment in one receipt; credit-limit check must use *posted* debtor balance + unbilled DNs, not document sums.

**Module tests:** partial-dispatch partial-billing matrix (SO 10 units → DN 6 → INV 6 → DN 4 → INV 4; assert COGS per DN, revenue per invoice, SO counters); advance→invoice GST swing; CN with restock returns stock at issued cost not current WA; legacy `payments` migration reconciles paisa-for-paisa (Σ old = Σ new allocations).
### 3.5 Purchases, expenses & payables

**Why:** today the purchase side stops at "goods received" — no bill, no creditor, no ITC, no payment. A payable you can't see is a payable you pay late (interest), pay twice (fraud), or lose ITC on (Rule 37). And an expense module that can't tell a laptop from a lunch produces a P&L where capital assets are eaten in one month.

**Tables (DDL §4.5):** `purchase_bills` + `purchase_bill_items`, `vendor_payments` + `vendor_payment_allocations`, `prepaid_schedules` + `prepaid_schedule_lines`, `accrual_vouchers`; **alterations:** `expenses` (+`vendor_id`, `is_capital`, `fixed_asset_id`, GST/ITC columns §3.6.3, `tds_section_id`, `cost_centre_id`, `project_id`, `bank_account_id`, attachment-mandatory rule), `expense_categories` (+`ledger_account_id`, `nature` enum(`direct`,`indirect`)), `goods_receipt_items` (+`rate_at_receipt`, `landed_cost_added`, `issued_cost` reference fields), `vendors` (+GSTIN/PAN/MSME §3.6.1, `tds_section_id` default, `bank details`), `requisitions` (+`purchase_order_id` link).

**Classes:** `app/Services/Accounting/Posting/{PurchaseBillPostingService, VendorPaymentPostingService, ExpensePostingService, GrnPostingService}.php`, `app/Services/Accounting/ThreeWayMatchService.php`, `app/Services/Accounting/PrepaidAmortisationService.php`, `app/Console/Commands/Accounting/{PostPrepaidAmortisation, WarnRule37Unpaid}.php`, controllers `Admin\Accounting\{PurchaseBillController, VendorPaymentController}` + edits to `ExpenseController`, `PurchaseOrderController`.

#### 3.5.1 The P2P chain and its postings

`PO (no posting) → GRN (Dr Stock-in-Hand / Cr GRN Clearing, at PO rate) → Purchase Bill (Dr GRN Clearing + Dr Input GST ± PPV / Cr Vendor − Cr TDS) → Vendor Payment (Dr Vendor / Cr Bank)`. GRN valuing at PO rate with a clearing account is DECISION-05 (recommended): stock is usable & costed the moment it lands; the bill trues-up money later; GRN Clearing shows exactly the "goods received not yet billed" audit answer.

#### 3.5.2 GRN changes (TASK-024)
`PurchaseOrderService::receiveGoods()` gains: `rate_at_receipt` snapshot (PO item rate), warehouse required per line (no silent first-item default — validation), `finalise` semantics with idempotency `goods_receipts:{id}:receive`, `StockPostingService::receive()` per line (WA/lot update), RCN journal posting (§3.2 #11), and rejection of over-receipt beyond PO qty + `businesses.grn_tolerance_percent` (default 0).

#### 3.5.3 Purchase Bill (PUR voucher — the ITC source document)
Fields: vendor, vendor's invoice no + date (Rule 36: ITC needs the supplier's document, our number is separate), our gapless number at finalise, due date (from `vendors.credit_days`, default 30), GRN links (many), lines: from GRN balances (3-way match §3.5.4) or free non-stock lines (expense/asset ledger picker), qty, rate, per-line GST per §3.6.2/3.6.7 with ITC eligibility, TDS section applied at header or line (§3.7). Posting per §3.2 #2: `Dr GRN Clearing` at GRN value, rate difference `(bill − GRN)` → `Dr/Cr Purchase Price Variance` **unless** the stock from that GRN is still ≥ billed qty on hand, in which case the difference adjusts Stock-in-Hand (true-up, keeps golden-rule stock cost honest; the service checks remaining lot/WA qty). Non-stock lines post straight to their ledger (or to CWIP/Fixed Asset per §3.8.2). RCM lines per §3.6.5.

#### 3.5.4 3-way match
`ThreeWayMatchService::check(bill)` compares PO qty/rate ↔ GRN received ↔ bill lines: PASS / WARN (within `businesses.match_tolerance_percent`, default 2%) / FAIL (block finalise without `purchase_bills.override_match` permission; override reason → audit_trail). Match report stored on the bill for the CA pack.

#### 3.5.5 Prepaid & accrued expenses
On any expense/bill line with `period_from`/`period_to` spanning > 1 month and `is_prepaid = true`: posts `Dr Prepaid Expenses (1263)` instead of the expense ledger, and creates a `prepaid_schedules` row with month-wise lines (straight-line over days, largest-remainder so the total is exact). `accounting:post-prepaid-amortisation` (monthly, 1st, 02:30) posts `Dr {expense ledger} / Cr Prepaid Expenses` per due line, idempotent per line id. Accruals are the mirror: `accrual_vouchers` capture "expense incurred, bill awaited" (`Dr expense / Cr Accrued Expenses Payable 2438`) and **auto-reverse on the 1st of the next month** (standard practice so the real bill posts clean); the CA pack lists open accruals.

#### 3.5.6 Expense module wiring (keeps today's UX)
`Expense::markPaid` is replaced by real postings (TASK-045): recording an approved expense posts `Dr {category ledger} (+ Dr Input GST if eligible) / Cr Vendor (if vendor_id) else Cr Expenses Payable (2438)`; paying posts `Dr Vendor|Expenses Payable / Cr Bank|Cash (bank_account_id now required)`. `is_capital = true` routes the debit to a Fixed Asset/CWIP ledger and spawns the FAR draft (§3.8.2) — the laptop stops being lunch. Attachment mandatory when amount > `businesses.expense_attachment_threshold` (default ₹5,000) — FormRequest rule. Recurring expenses inherit all of this (the generator copies the ledger/GST/vendor fields).

#### 3.5.7 Employee-money integration (reimbursements, requisitions, payroll)
- Reimbursement `approved`: `Dr {category ledger} / Cr Employee ledger (payable)`; `disbursed`: `Dr Employee ledger / Cr Bank`. (TASK-046.)
- Requisition `disbursed` becomes an advance: `Dr Employee Advances (1262) / Cr Bank`; settlement screen closes it against expense bills or refund.
- Payroll (TASK-047): on payslip batch finalise, per month one JV: `Dr Salaries & Wages (5203, auto-created Indirect... DECISION-11: default Indirect Expenses; businesses doing contract manufacturing may re-parent to Direct) gross + Dr Employer PF/ESI contribution expense / Cr Salaries Payable (net), Cr PF Payable (employee+employer), Cr ESI Payable, Cr PT Payable, Cr TDS on Salary Payable (192)`, dimensions per department → cost centre mapping (`departments.cost_centre_id`, auto-created). Payment of each payable = ordinary Payment voucher. Payslip edits after posting: blocked (HasPostedJournals) — regenerate via reversal + new batch.

#### 3.5.8 Rule 37 (180-day) tracker
`accounting:warn-rule37` (daily): vendor bills with ITC claimed, unpaid (allocation-aware) past 150 days → notification (existing `NotificationDispatcher` pattern); past 180 days → creates a **draft** Rule-37 reversal JV (`Dr ITC Reversed Reclaimable 2423 / Cr Input CGST/SGST/IGST` pro-rata to unpaid fraction) for Accounts to post (auto-post toggle `businesses.rule37_auto_post`, default off); on later payment, drafts the re-claim JV back. `gst_ledger_entries.itc_status` transitions `eligible → reversed_rule37 → reclaimed` feed 3B table 4(B)(2)/4(D)(1).

**Endpoints & screens (M3):** `accounting/purchase-bills` (+ from-GRN builder + match panel), `accounting/vendor-payments` (+ allocation UI + bulk "pay due this week"), `accounting/prepaid-schedules`, expense form gains ledger/GST/vendor/capital fields. Permissions: `purchase_bills.view/create/finalise/cancel/override_match`, `vendor_payments.view/create/finalise/cancel`, `expenses.finalise` (post) added to existing expense perms.

**Edge cases:** bill without PO/GRN (services, utilities — allowed, non-stock lines only); one bill across many GRNs and one GRN across many bills (allocation by GRN line balances, `FOR UPDATE` on GRN items); vendor bill in a locked month arriving late (post on receipt date with `reference` = vendor's date — never backdate into locked periods); TDS on advance payment to vendor (deduct at payment when no bill yet — §3.7.3); partial payment allocation order (user-chosen, default oldest-due-first); MSME vendor unpaid > 45 days (Sec 43B(h) flag on ageing + CA pack note); duplicate vendor-invoice-number guard per vendor per FY (unique index) — the classic double-payment hole.

**Module tests:** GRN→bill→payment happy path ties GRN Clearing to zero; PPV vs stock-true-up branch by remaining qty; 3-way match tolerance edges (exact, +2%, +2.01%); prepaid 12-month schedule sums exactly; Rule-37 draft at 181 days with 40% unpaid reverses 40% of ITC; payroll JV nets to payslip totals for a generated month.

### 3.6 GST compliance

**Why:** GST is not a report you print later; it decides, on every line, *which two of six tax ledgers* move. Getting place-of-supply wrong doesn't mis-report tax — it pays the wrong government, and the fix is a refund claim, not an edit.

**Tables (DDL §4.6):** `tax_rates` (seed 0, 0.1, 0.25, 3, 5, 12, 18, 28 + cess capability), `gst_ledger_entries` (the register every return reads), `einvoices`, `eway_bills`, `gst_returns`, `gstr2b_lines`; **alterations:** `businesses` (+`gstin` validated, `gst_registration_type` enum(`regular`,`composition`,`unregistered`), `einvoice_enabled`, `einvoice_credentials` encrypted JSON, `eway_credentials`, `state_code` char(2) derived), `states` (+`gst_state_code` char(2): Maharashtra 27, Delhi 07, … full 36-code map in the TASK-036 migration), `customers`/`vendors` (§3.6.1), all document items (§3.6.3).

**Classes:** `app/Services/Accounting/Gst/{GstinValidator, PlaceOfSupplyResolver, GstCalculator, GstRegisterService, RcmService, Gstr1Builder, Gstr3bBuilder, Gstr2bReconciliationService, EInvoiceService, EwayBillService, HsnSummaryBuilder}.php`, `app/Http/Controllers/Admin/Accounting/{GstReturnController, EInvoiceController, EwayBillController, Gstr2bController}.php`.

#### 3.6.1 Party statutory identity
`customers` & `vendors` gain: `gstin` (existing `gst_number` renamed via alias — keep column, add validated write path), `gst_registration_type` enum(`regular`,`composition`,`unregistered`,`overseas`,`sez`,`uin`), `state_id` FK (replacing free-text state — migration maps existing strings case-insensitively, unmatched → null + repair report), `pan` char(10) (auto-derived from GSTIN chars 3-12 when blank), `msme_registered` bool + `udyam_number`. `GstinValidator`: regex `^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$` + the official mod-36 checksum on char 15 + state-code(2) must exist in `states.gst_state_code`; PAN cross-check when both present. Applied in FormRequests (warn-don't-block for legacy rows until edited).

#### 3.6.2 Tax determination on every line
`PlaceOfSupplyResolver`: POS = customer `shipping state` (goods, Sec 10 IGST Act) / customer state (services default, Sec 12) with document-level override field for Sec 10(1)(b) bill-to-ship-to. Supply is **intra-state** when `businesses.state_code == POS` → CGST+SGST at rate/2 each (both rounded 2 dp per line independently — they may differ by a paisa from rate×value/2 summed; that is correct and matches portal behaviour); else **IGST** (POS in `states` where `is_ut` → UTGST label but same 2412 ledger, labelled per business state). Exports/SEZ: `invoice.gst_treatment` enum(`taxable`,`exempt`,`nil_rated`,`non_gst`,`export_lut`,`export_with_tax`,`sez_lut`,`sez_with_tax`,`rcm_outward`) — LUT paths post zero tax (bucket EXP/SEZ in GSTR-1), with-tax paths post IGST refundable. Composition businesses: documents render "Bill of Supply", zero tax lines everywhere, ITC lines forced ineligible-to-cost.
`GstCalculator` is pure: `(taxable Money, ratePct, pos, sellerState, treatment) → {cgst, sgst, igst, cess}` — unit-tested against a 40-case fixture table including 0.1% merchant-export and 28%+cess.

#### 3.6.3 Document columns (per item + header)
All *_items tables (§3.4/§3.5 docs) gain: `taxable_value`, `cgst_rate/cgst_amount`, `sgst_rate/sgst_amount`, `igst_rate/igst_amount`, `cess_rate/cess_amount` (rates 9,4; amounts 20,4); headers gain the five totals + `round_off_amount` + `place_of_supply` char(2) + `gst_treatment`. Existing rows: backfill NULL→0, `legacy_totals` flag stays (§3.4.2); reports treat legacy rows via their stored `tax_amount` only.

#### 3.6.4 Invoice-level rupee rounding
After line math, `round_off = round(grand_total_exact, 0) − grand_total_exact` (2 dp), posted to Round Off 5201 (Dr when negative, Cr when positive), stored on header, printed on the PDF ("Round Off: −0.38"). Never silently absorbed, never re-derived. GSTR values use taxable/tax as computed (returns are paise-true; rounding is a books-side convenience line). Boundary declared: **this is the only place a document total is rounded to whole rupees.**

#### 3.6.5 Reverse charge (RCM)
`purchase_bill_items.is_rcm` (auto-on for notified categories seeded in `tax_rates.rcm_categories` JSON: GTA 5%, advocate, sponsorship, import of services, security services from non-body-corporate, unregistered-vendor rent of commercial property etc. — seed list in the TASK-051 config with citations): bill posts **no** vendor-side GST credit; instead `Dr Input CGST/SGST|IGST (RCM) / Cr GST RCM Payable (2421)` in the same JV, ITC conditional on cash payment of the RCM liability (3B 4(A)(3) after 3.1(d)) — `RcmService` generates the monthly **self-invoice** (Sec 31(3)(f)) PDF for unregistered vendors, numbered `RCM/{FY}/{seq}` gapless. RCM payable settles only via GST Cash Ledger payment voucher (validation).

#### 3.6.6 E-invoice (IRN) & e-way bill
`EInvoiceService`: builds INV-01 JSON (schema 1.1) from a finalised invoice (mandatory when `einvoice_enabled` — the ₹5 crore AATO mandate is the business's own determination, toggle + warning banner), posts to the configured IRP (NIC einvoice1 v1.03 REST: auth → generate → returns IRN + signed QR + AckNo/AckDt; sandbox base URL per credentials), stores in `einvoices`, renders QR on the PDF. Failures queue-retry (existing `database` queue) max 5 with backoff, invoice stays legally un-issuable until IRN when mandated (`invoices.status = 'pending_irn'`). Cancellation ≤ 24 h via API; after that only CN. `EwayBillService`: Part-A from invoice/DN (HSN, value, transporter GSTIN), Part-B vehicle update endpoint, validity tracking (1 day per 200 km slab), threshold check ₹50,000 default with per-state intra-state override map (JSON config `config/gst.php`). Both features are **adapter-isolated** (`app/Services/Accounting/Gst/Adapters/{NicIrpAdapter, NicEwbAdapter}.php` behind interfaces) so a GSP can be swapped in; credentials per business, encrypted casts.

#### 3.6.7 ITC ledger discipline
Every purchase-side GST rupee lands in `gst_ledger_entries` with `itc_status`: `eligible`, `blocked_17_5` (motor vehicles ≤13 seats, food & beverages, works-contract-for-building, memberships, personal — picker with the Sec 17(5) list; posts tax **to cost** not to Input ledgers), `capital_goods` (eligible, tagged for Rule 43), `rcm_pending_payment`, `reversed_rule37`, `reversed_rule42`, `reclaimed`. Rule 42/43 apportionment (exempt vs taxable turnover) runs as a period-end wizard: computes D1/D2 per the rule from the period's turnover mix, posts the reversal JV, stores the working paper for GSTR-9/9C.

#### 3.6.8 GST registers ↔ Duties & Taxes reconciliation (P0 invariant)
`GstRegisterService::reconcile(period)`: Σ `gst_ledger_entries` by type must equal the movement on each 241x ledger for the period, line-by-line explainable (every entry carries `journal_id`). Runs inside `accounting:verify-integrity`. Any manual JV touching 241x without a register row = flagged (hence the §3.1 controlled-ledger guard).

#### 3.6.9 Returns
- **GSTR-1** (`Gstr1Builder`): B2B (registered buyers, invoice-level), B2CL (inter-state unregistered, invoice value > ₹1,00,000, invoice-level), B2CS (rest, rate-wise summary), CDNR/CDNUR, EXP, NIL/exempt/non-GST summary, AT (advances) & TXPD (adjusted), HSN summary (6-digit when AATO > ₹5 cr else 4 — business toggle), Table 13 document series (counts gapless series incl. cancelled — this is why §3.1.7 keeps cancelled numbers). Output: portal-schema JSON download + Excel; stored snapshot in `gst_returns` with a diff-vs-books check that must be zero.
- **GSTR-3B** (`Gstr3bBuilder`): 3.1(a-e) from output register, 4(A) ITC available (incl. RCM), 4(B) reversals (Rule 37/42/43), 4(D), 5, 6.1 — values traceable to register queries; snapshot on "mark filed" locks the period's GST edits (posting still possible but flagged into next period's amendment bucket).
- **GSTR-2B reconciliation** (`Gstr2bReconciliationService`): import the portal's 2B JSON → `gstr2b_lines`; match against purchase bills on (supplier GSTIN, doc no fuzzy [strip series punctuation], doc date ±3 d, value ±₹1): buckets **matched / value-mismatch / missing-in-books / missing-in-2B**; screen with accept/link/create-bill actions; ITC eligible-per-2B total vs books ITC total headline (Sec 16(2)(aa)).
- **GSTR-9/9C working papers**: FY roll-ups of the above + the reconciliation statements as Excel (no filing claim — working papers for the CA, in the CA Pack 04-GST).

**Endpoints & screens (M4):** `accounting/gst/returns` (period picker, GSTR-1/3B build+download+mark-filed), `accounting/gst/2b` (upload+recon workbench), `accounting/gst/itc-register`, `accounting/gst/rcm` (self-invoices), `accounting/einvoices`, `accounting/eway-bills`. Permissions: `gst.view/build_returns/mark_filed/reconcile_2b`, `einvoice.generate/cancel`, `eway.generate`.

**Edge cases:** buyer GSTIN of a different state than shipping state (POS follows shipment for goods — warn banner); invoice to SEZ *without* LUT (with-tax path, IGST even intra-state); credit note against a pre-GST-columns legacy invoice (manual tax entry allowed, flagged); e-invoice for CN/DBN (mandated docs too — same pipeline); 2B shows a bill we cancelled (bucket missing-in-books with our cancel note); composition purchase from us (no ITC to them — nothing special for us; composition *seller* business blocks e-invoice); rate change mid-FY (tax_rates are dated rows, resolver picks by document date).

**Module tests:** GstCalculator fixture table (40 cases); golden test §5.1 assertion 9; rounding: 3 lines × ₹333.335 taxable at 18% — per-line rounding sums correct, round-off line balances to the rupee; GSTR-1 JSON matches a hand-built fixture for a seeded month (B2B+B2CS+CDNR+series table incl. 1 cancelled); 2B matcher buckets a crafted 8-case file correctly; RCM bill: no ITC until cash-ledger payment voucher posts.

### 3.7 TDS / TCS on trade

**Why:** when you pay certain vendors, the law makes *you* the tax collector — deduct at source at the **earlier of credit or payment**, deposit by the 7th, report quarterly. Miss it and 30% of the expense is disallowed (40(a)(ia)) plus interest.

**Tables (DDL §4.7):** `tds_sections` (global, §3.1.9), `party_tds_profiles`, `tds_entries`, `tds_challans`; alterations: none beyond §3.5's `tds_section_id` columns. (The existing `tds_slabs` table is Section-192 salary slabs and stays untouched — it belongs to payroll.)

**Seed (`tds_sections`)** — FY 2025-26 values, effective-dated rows so future Finance Acts are new rows, not edits (verify against the current Finance Act at go-live; the seeder cites each):

| Section | Nature | Rate (with PAN) | Threshold | Notes |
|---|---|---|---|---|
| 194C | Contract work | 1% (ind/HUF) / 2% (others) | ₹30,000 single / ₹1,00,000 FY aggregate | transporter nil-declaration flag (194C(6)) |
| 194J(a) | Technical services / call centre | 2% | ₹50,000 | |
| 194J(b) | Professional fees | 10% | ₹50,000 | |
| 194H | Commission/brokerage | 2% | ₹20,000 | |
| 194I(a) | Rent — plant & machinery | 2% | ₹50,000/month | monthly threshold basis per Finance Act 2025 |
| 194I(b) | Rent — land/building/furniture | 10% | ₹50,000/month | |
| 194Q | Purchase of goods | 0.1% on excess over threshold | ₹50,00,000 FY aggregate | buyer turnover > ₹10 cr precondition — business toggle |
| 206C(1H) | TCS on sale of goods | 0.1% | ₹50,00,000 | seeded `active_to = 2025-03-31` (omitted by Finance Act 2025); engine supports it for FY ≤ 24-25 books and any reintroduction |
| No-PAN rule | — | 20% (206AA) | — | applied when party PAN missing/invalid |

206AB/206CCA (higher rate for return-non-filers) were omitted w.e.f. 1 Apr 2025 — implemented as an optional per-party `force_higher_rate` override on `party_tds_profiles` (covers legacy FYs and future reinstatements), default off.

**Mechanics** (`app/Services/Accounting/Tds/TdsEngine.php`): on purchase-bill finalise **or** vendor-payment finalise (whichever first per party+section — the engine checks existing `tds_entries` against the same base document to never deduct twice): threshold tracking per party per section per FY (aggregates from `tds_entries` + bill bases); base = taxable value **excluding GST** (CBDT Circular 23/2017 for 194C/J/H/I when GST shown separately; 194Q on value excluding GST per 13/2021 practice — noted in code comment); LDC (lower-deduction certificate) rows on `party_tds_profiles` (cert no, rate, cap amount, validity) override rate within cap. Posting: bill-side `Cr TDS Payable — {section}` inside the PUR JV (§3.2 #2); payment-side standalone JV when triggered by payment. `tds_challans`: record challan 281 deposits (`Dr TDS Payable — section / Cr Bank`), link entries → challan; quarterly **26Q/27Q/27EQ data export** as the NSDL-format Excel workbook (deductee rows: PAN, section, date, amount, TDS, challan mapping) — filing happens in RPU/portal; we produce the exact data grid. `TDS Receivable` (customer deducted on us): captured on receipts (§3.4.6), FY report for 26AS matching.

**Endpoints & screens (M4):** `accounting/tds/register` (entries by section/quarter, threshold watch list "parties at 80% of threshold"), `accounting/tds/challans`, `accounting/tds/returns` (26Q export). Permissions: `tds.view/manage/deposit/export`.

**Edge cases:** bill in March, payment in April (deduct in March — credit came first; challan due 30 Apr); advance payment before any bill (deduct on payment; when the bill arrives, engine nets the already-deducted base); party crosses threshold mid-year (deduct on the *entire* aggregate for 194C-style sections vs only-excess for 194Q — per-section `threshold_mode` column drives it); vendor supplies goods **and** services on one bill (per-line section); LDC expiring mid-quarter; PAN invalid → 20% + report flag.

**Module tests:** threshold crossing retro-deduction (194C at ₹1,00,001 aggregate deducts on full base); earlier-of-credit-or-payment both orders; 194Q excess-only math; challan allocation equals payable ledger; 26Q export row fixture.

### 3.8 Fixed assets

**Why:** a machine bought today is *used up* over years; depreciation spreads its cost over that life. Indian reality: two parallel books — Companies Act Schedule II (useful-life, for the P&L) and Income-tax block-of-assets WDV (for the return). CAs maintain both; we produce both.

**Tables (DDL §4.8):** `fixed_assets`, `fixed_asset_events` (additions/improvements/disposals), `it_blocks` (global seed: Building 10%, Furniture 10%, Plant & Machinery 15%, Motor vehicles 15%, Computers & software 40%, Intangibles 25%), `it_block_fy_lines` (per business per block per FY: opening WDV, additions ≥180 d, additions <180 d, sale proceeds, dep, closing), `depreciation_runs` + `depreciation_run_lines`. Note: the existing `assets` module (`app/Models/Asset.php`) is an IT-operations tracker (assignments, repairs) — it stays; `fixed_assets.operational_asset_id` optionally links a FAR row to it (DECISION-13).

**Classes:** `app/Services/Accounting/FixedAssets/{CapitalisationService, ScheduleIIDepreciationService, ItBlockService, DisposalService}.php`, `app/Console/Commands/Accounting/RunMonthlyDepreciation.php`, controller `Admin\Accounting\FixedAssetController`.

**Mechanics:**
- **Capitalise** from a purchase-bill line or expense marked `is_capital` (§3.5.6): creates FAR row (cost = taxable value + non-creditable GST + directly attributable costs via landed-cost-style additions; put-to-use date; Schedule II useful life defaulted from a seeded class table — e.g. computers 3 y, office equipment 5 y, furniture 10 y, general P&M 15 y, buildings 30/60 y; residual 5% default), assigns Companies-Act method SLM or WDV (per business default, per asset override), assigns IT block. CWIP: capital spend with `put_to_use = null` accumulates in `Capital Work-in-Progress` ledger (auto leaf under Fixed Assets); commissioning transfers CWIP → asset cost.
- **Monthly Companies-Act run** (`depreciation_runs`, idempotent per business+month): SLM = (cost − residual) × days-in-month/days-in-life; WDV = rate derived from life `r = 1 − (residual/cost)^(1/life-years)` applied on opening book value, day-apportioned in acquisition/disposal months. Posts one JV: `Dr Depreciation (5204, Indirect Expenses) / Cr Accumulated Depreciation — {class}` (contra-asset leaves under Fixed Assets). Runs after month lock is *not* allowed (PeriodGuard applies — schedule it before locking; the lock screen warns of missing runs).
- **IT block (FY-level working paper, no journal):** `ItBlockService::buildFy(fy)` — per block: opening WDV + additions (split by 180-day put-to-use rule for half-rate) − money received on disposals; dep = rate × eligible base; block extinguished/emptied → short-term capital gain/loss lines for the CA. Output feeds the CA Pack 07 and the deferred-tax note: DTL/DTA = (Companies-Act WDV − IT WDV) × 25.17% (configurable tax rate) shown as a **proposed** closing JV (§3.10.3), never auto-posted.
- **Disposal:** `DisposalService::dispose(asset, date, proceeds, buyer)`: depreciate to date → `Dr Bank/Debtor proceeds + Dr Accumulated Depreciation + Dr Loss (or Cr Profit — 4201 "Profit on Sale of Assets" / 5205 loss) / Cr Asset cost`; GST on disposal of business asset (taxable supply) via a Sales voucher for the proceeds when GST applies; IT block reduces by proceeds (not book profit) — both books diverge here by design; the FY line shows it.

**Endpoints & screens (M5):** `accounting/fixed-assets` (register: cost, additions, dep to date, WDV both books), `accounting/fixed-assets/run-depreciation`, asset show page with dual schedule + event history. Permissions: `fixed_assets.view/manage/run_depreciation/dispose`.

**Edge cases:** asset put to use exactly 180 days before FY end (full-rate — the rule is "< 180 days ⇒ half"); mid-month disposal (day-count both legs); improvement spend after 3 years (adds to cost, prospective dep over remaining life); asset fully depreciated to residual then still in use (dep stops at residual, register keeps it); block going negative on disposal (STCG surfaced, block floors at 0); revaluation — out of scope, documented (DECISION-13).

**Module tests:** SLM & WDV month-math against a hand-computed 3-year laptop fixture (₹90,000, 3 y, 5% residual: SLM month-1 = round(85,500 × 31/1095, 2) with the seeded day-count convention); 180-day IT split; disposal with profit posts contra correctly and TB stays balanced; run idempotency.
### 3.9 Financial statements & registers — the actual ask

**Why:** these are the deliverables the owner named. Every one below reads **only** `journals`/`journal_lines`/`account_balances` (+ the registers for GST columns) — never document tables — so the statements cannot disagree with the ledger. Every figure is drillable: statement line → accounts → vouchers → source document. All accept any date range, default = current FY (Apr–Mar), and export to PDF (`Pdf::loadView('admin.accounting.pdf.…')`) and Excel (`GenericArrayExport` or dedicated export class). Comparative column = same range shifted one FY.

**Classes:** `app/Services/Accounting/Statements/{TrialBalanceService, TradingAndPlService, BalanceSheetService, CashFlowService, LedgerStatementService, DayBookService, AgeingService, RegisterService, RatioService, BudgetVsActualService, ProjectPlService}.php`; controllers `Admin\Accounting\{StatementController, RegisterController, BankingController}`; exports in `app/Exports/Accounting/`. Screens under `accounting/statements/*`, permission family `statements.view/export` (+ `statements.view_all_projects` for cross-project P&L).

| # | Report | Contract (what it must show / guarantee) |
|---|---|---|
| 3.9.1 | **Day Book** | all journals for a date/range, filter by voucher type; running count; totals per type; drill to voucher. |
| 3.9.2 | **Ledger / account statement** | opening balance, dated lines (voucher no, counter-account summary, Dr, Cr), running balance, closing; party ledgers show bill-wise allocation status; export "all ledgers, full year" batch for the CA Pack 02. |
| 3.9.3 | **Trial Balance** | per account: opening Dr/Cr, period Dr/Cr, closing Dr/Cr; grouped by CoA tree with subtotals; **footer must show ΣDr = ΣCr or render a red banner and refuse export** (that state is a bug — integrity command finds it). |
| 3.9.4 | **Trading Account** | Tally style per §3.3.8: Opening Stock, Purchases (net of returns), Direct Expenses, Gross Profit c/d vs Sales (net), Closing Stock; both sides total equal. Derivation must equal Sales − COGS-ledger − other-direct to the paisa (assert in tests, not at render). |
| 3.9.5 | **Profit & Loss** | two formats: (a) Tally vertical (Trading section → GP → Indirect Incomes/Expenses → Net Profit); (b) **Schedule III Division I Part II** (Revenue from operations, Other income, Cost of materials consumed [= opening + purchases − closing for a trader shown as "Purchases of stock-in-trade" + "Changes in inventories"], Employee benefits, Finance costs, Depreciation, Other expenses, PBT); mapping via `ledger_accounts.schedule_iii_head`. |
| 3.9.6 | **Balance Sheet** | (a) Tally horizontal (Liabilities/Assets at group level, P&L balance on liabilities side); (b) **Schedule III Division I Part I** vertical with Note numbers and **prior-year comparatives**; heads from the fixed slug list: `share_capital, reserves_and_surplus, long_term_borrowings, deferred_tax_liabilities, other_long_term_liabilities, long_term_provisions, short_term_borrowings, trade_payables, other_current_liabilities, short_term_provisions, tangible_assets, intangible_assets, capital_wip, non_current_investments, deferred_tax_assets, long_term_loans_advances, other_non_current_assets, current_investments, inventories, trade_receivables, cash_and_equivalents, short_term_loans_advances, other_current_assets, revenue_from_operations, other_operating_revenue, other_income, cost_of_materials, direct_expenses, employee_benefits, finance_costs, depreciation_amortisation, other_expenses`. Trade payables split MSME/others from `vendors.msme_registered`. Totals equal or red-banner. |
| 3.9.7 | **Cash Flow (AS-3 indirect)** | PBT → +depreciation, ±working-capital deltas (debtors/creditors/stock/other CA-CL from BS movements), −tax paid → CFO; CFI from Fixed Asset/Investment group movements; CFF from Loans/Capital movements; closing cash reconciles to Cash+Bank ledgers. Classification by CoA group, overrides per account (`cash_flow_bucket` nullable column on `ledger_accounts`). |
| 3.9.8 | **Notes / working papers** | per Schedule III note: the account-level breakup behind each BS/P&L line, ageing schedules for trade receivables/payables **in the Schedule III ageing-bucket format** (<6 m, 6 m–1 y, 1–2 y, 2–3 y, >3 y — this is the amended Sch III disclosure), movement of inventories, of fixed assets (gross block/dep/net block grid). Excel-first. |
| 3.9.9 | **Bank Reconciliation** | `bank_accounts` master (each row auto-creates its 1240-group ledger; migration converts distinct legacy `payments.mode`/expense `payment_method` strings into a Cash ledger + one "Legacy Bank" account for backfill — §7). Statement import: CSV/XLSX column-mapper with saved per-bank templates + MT940 parser (`app/Services/Accounting/Banking/{StatementImportService, Mt940Parser, AutoMatcher}.php`). AutoMatcher: exact amount+date ±3 d, then amount+reference fuzzy (Levenshtein ≤ 3 on cheque/UTR), then rule table (`bank_match_rules`: contains-text → account, e.g. "NEFT CHG" → Bank Charges suggestion — suggested vouchers created as drafts, never auto-posted). BRS statement: book balance vs statement balance, itemised unmatched both sides, printable as at any date; carry-forward of stale unpresented items. |
| 3.9.10 | **Registers** | Sales / Purchase / Journal / Debit-note / Credit-note registers: document-wise rows with full GST split columns (taxable, CGST, SGST, IGST, cess, total), party GSTIN, HSN drill; monthly totals row; these are the CA's vouching entry point and feed CA Pack 03. |
| 3.9.11 | **Receivables & Payables ageing** | party-wise open items (bill-wise from allocations), buckets 0-30/31-60/61-90/90+ (config per business), plus the Sch III buckets for 3.9.8; on-screen consolidated + per-party statement PDF ("ledger confirmation" letter with reply slip — CA Pack 09). MSME column + >45-day 43B(h) flag on payables. |
| 3.9.12 | **Stock reports** | summary (qty+value by item/category/warehouse), valuation as-on-date (ties to 1211 — §3.3), movement analysis (in/out/turnover ratio), slow-moving/dead stock (no issue in N days, value at risk), NRV assessment history. |
| 3.9.13 | **Cost centre & project P&L** | P&L filtered by `journal_lines.cost_centre_id/project_id`, plus the quote-vs-actual panel (§3.4.9): quoted revenue/cost/margin (from quotation snapshot) vs invoiced revenue, actual COGS + direct + allocated overheads (allocation rule: none by default; optional % overhead config) → realised margin, variance rows with reason tags (rate, quantity, freight, scope) the user assigns. This is wedge #1 (§2.3) — build it as a first-class screen, not an afterthought. |
| 3.9.14 | **Ratios & budget-vs-actual** | current/quick ratio, debtor/creditor days (DSO/DPO from ageing), stock turnover, GP%/NP%, interest cover; `ledger_budgets` (account or group × FY × monthly spread) vs actuals with variance %; expense-side reuses the existing `expense_budgets` data where mapped. |

**Performance:** statements read `account_balances` for period aggregates + `journal_lines` only for drill-down; ageing/registers paginate; "all ledgers full year" export runs queued with progress (existing jobs table), file to `storage/app/businesses/{id}/exports`.

**Edge cases:** date range spanning FYs (allowed for registers, blocked for BS [point-in-time] — BS takes a date, not a range); accounts with zero movement hidden by default (toggle); Schedule III when an account lacks a head (renders under "Unmapped" with red badge — integrity check counts it); negative cash (§5.1 world): BS shows Bank OD grouping if credit balance persists at date — presentation rule: credit-balance bank/cash accounts flip to `short_term_borrowings` head at render (standard practice), the golden test's tiny-world variant documents the alternative negative-asset presentation.

**Module tests:** TB totals equal after the full M2 golden run; Sch III BS equals Tally BS totals; cash flow ties (CFO+CFI+CFF = Δcash) on a seeded quarter; ageing bucket boundary (invoice dated exactly 30 days ago sits in 0-30); BRS: imported 20-line fixture auto-matches 16, leaves 4, closes to the paisa.

### 3.10 The CA Handover Pack, CA Portal & Tally interop — the headline differentiator

**Why:** the yearly ritual — "send me your data" — is where every SME tool fails the CA. We make the handover a **product artefact**: one button, one indexed ZIP, every number pre-reconciled, plus a portal where the audit conversation happens against vouchers instead of WhatsApp.

**Tables (DDL §4.9):** `ca_pack_runs` (period, status, checksum, zip path, generated_by, manifest JSON), `ca_users` (email-login auditors; guard `ca`), `ca_access_grants` (business ↔ ca_user, valid_from/valid_until, revoked_at), `audit_queries` + `audit_query_messages` (+ attachments via existing storage pattern), `document_requests` (checklist: title, description, status requested/uploaded/accepted, file), `closing_entry_proposals` (draft JV payload JSON, status proposed/approved/rejected/posted, proposed_by_ca, approved_by_admin, posted_journal_id).

**Classes:** `app/Services/Accounting/CaPack/{CaPackGenerator, ReconciliationSummaryBuilder}.php` (+ one builder per folder: `FinancialStatementsBuilder`, `LedgersBuilder`, `RegistersBuilder`, `GstFolderBuilder`, `TdsFolderBuilder`, `InventoryFolderBuilder`, `FixedAssetsFolderBuilder`, `BankFolderBuilder`, `PartiesFolderBuilder`, `VouchersFolderBuilder`, `AuditTrailFolderBuilder`, `TallyFolderBuilder` — each returns files into the run's workdir); `app/Services/Accounting/Tally/{TallyXmlExporter, TallyXmlImporter, TallyNameMapper}.php`; `app/Http/Controllers/Ca/{AuthController, DashboardController, VoucherBrowserController, AuditQueryController, DocumentRequestController, ClosingEntryController}.php`; routes in a new `Route::prefix('ca')` block with guard `ca` + a scoped-business middleware (`app/Http/Middleware/EnsureCaAccess.php`).

#### 3.10.1 CA Pack generation
`POST accounting/ca-pack {fiscal_year_id | from,to}` → queued job (existing database queue) builds exactly this tree, then zips with an `00-INDEX.pdf` (entity name, GSTIN, PAN, CIN, period, generated-at, generated-by, per-file SHA-256 manifest) and stores the ZIP + top-level checksum on `ca_pack_runs`:

```
CA-Pack-{CompanySlug}-FY{2025-26}/
  00-INDEX.pdf
  01-Financial-Statements/   TrialBalance.pdf+.xlsx, PnL-Tally.pdf, PnL-ScheduleIII.pdf+.xlsx,
                             BalanceSheet-Tally.pdf, BalanceSheet-ScheduleIII.pdf+.xlsx,
                             CashFlow.pdf+.xlsx
  02-Ledgers/                one PDF per ledger with opening/closing + Ledgers-All.xlsx
  03-Registers/              Sales.xlsx, Purchase.xlsx, Journal.xlsx, CashBook.xlsx, BankBook-{acct}.xlsx
  04-GST/                    GSTR1-books-vs-filed.xlsx, GSTR3B-books-vs-filed.xlsx,
                             2B-Reconciliation.xlsx, ITC-Register.xlsx, RCM-SelfInvoices.pdf
  05-TDS/                    Deduction-Register.xlsx, Challans.xlsx, 26Q-Data.xlsx
  06-Inventory/              StockSummary.xlsx, Valuation-Working.xlsx (method, rates, lots), NRV-Note.pdf
  07-Fixed-Assets/           FAR.xlsx, Dep-ScheduleII.xlsx, Dep-IT-Blocks.xlsx
  08-Bank/                   per account: Statement-lines.xlsx + BRS.pdf
  09-Parties/                Debtors-Ageing.xlsx, Creditors-Ageing.xlsx, Confirmation-Letters.pdf (batch)
  10-Vouchers/               JournalVouchers.pdf (JV register with narrations) + attachments/ (linked files)
  11-Audit-Trail/            AuditTrail-{period}.xlsx (from audit_trail, filtered to accounting models)
  12-Tally/                  Masters.xml, Vouchers.xml  (§3.10.4)
  RECONCILIATION-SUMMARY.pdf
```

`RECONCILIATION-SUMMARY.pdf` runs and prints PASS/FAIL for: TB balances; every voucher balances; stock valuation = ledger 1211; GST registers = 241x ledgers; debtors control = Σ party ledgers = ageing total; creditors likewise; bank book = statement ± unreconciled items; GSTR-1 filed vs books delta; TDS payable = register − challans; audit-trail row count for period. **A pack with any FAIL still generates** (the CA wants to see the failure) but is watermarked "CONTAINS UNRECONCILED ITEMS".

#### 3.10.2 CA Portal
Separate login (`/ca/login`, guard `ca`, `ca_users` — invited by email from `accounting/ca-access`; grant is business-scoped and **time-boxed** via `valid_until`, default +120 days, revocable). Strictly read-only: dashboards (TB, statements, registers, voucher browser with attachments) rendered by the same statement services with a `CaContext` that hard-blocks any non-GET route except the three workflows below. Every CA page view of a voucher writes an `audit_trail` `action='ca_viewed'`... (no — keep trail signal-to-noise: only exports and query actions are logged; page views are activitylog). Workflows: **(a) Audit queries** — CA raises against a voucher/document (`audit_queries.journal_id` nullable + free subject), client responds with text + attachment, CA resolves; inbox badges both sides (reuse `NotificationDispatcher` + `admin_notifications`). **(b) Document requests** — CA posts a checklist ("sanction letter for OD", "rent agreement"); client uploads against each. **(c) Closing entry proposals** — CA drafts a JV (same builder UI in propose-mode); client Admin reviews & approves → posts through `JournalPostingService` with `vouchers.post_to_controlled` semantics, `source_type = ClosingEntryProposal`; rejection carries a note. This is maker-checker with the CA as maker (§3.11.3).

#### 3.10.3 Tally interoperability (both directions)
**Export** (`TallyXmlExporter`): TallyPrime-importable XML envelopes. Masters: `<ENVELOPE><HEADER><TALLYREQUEST>Import Data</TALLYREQUEST></HEADER><BODY><IMPORTDATA><REQUESTDESC><REPORTNAME>All Masters</REPORTNAME></REQUESTDESC><REQUESTDATA>` + one `<TALLYMESSAGE xmlns:UDF="TallyUDF">` per `<GROUP>`/`<LEDGER>` (NAME, PARENT = `tally_group_name`, OPENINGBALANCE with Tally sign convention: **debit = negative number, credit = positive**, GSTIN in `<PARTYGSTIN>`, `<GSTREGISTRATIONTYPE>`). Vouchers: `<REPORTNAME>Vouchers</REPORTNAME>` + per journal `<VOUCHER VCHTYPE="Sales|Purchase|Payment|Receipt|Contra|Journal|Credit Note|Debit Note" ACTION="Create">` with `<DATE>YYYYMMDD`, `<VOUCHERNUMBER>`, `<PARTYLEDGERNAME>`, and `<ALLLEDGERENTRIES.LIST>` rows (`<LEDGERNAME>`, `<ISDEEMEDPOSITIVE>Yes/No`, `<AMOUNT>` signed: Dr negative / Cr positive). Voucher-type mapping = §3.2 codes → the eight Tally native types (DLN/RCN/STJ export as Tally `Delivery Note`/`Receipt Note`/`Stock Journal` **only when** the receiving company has inventory enabled; toggle exports them as Journals otherwise — export dialog option). Stock items are exported as `<STOCKITEM>` masters with UQC units when the inventory toggle is on.
**Name mapping & collisions** (`TallyNameMapper`): Tally identifies by NAME within a company — exporter guarantees uniqueness by suffixing ` (2)`, ` (3)` on collision after case-insensitive trim; ledgers renamed in our app keep a stable `tally_alias` column (set on first export) so re-exports match instead of duplicating; unmapped custom groups fall back to parent `Suspense A/c` **never silently** — the export ends with a `Mapping-Report.txt` listing every fallback and suffix applied.
**Import** (onboarding, `TallyXmlImporter`): accepts a Tally "All Masters" XML export: creates ledger groups/ledgers (matching our 28 seeds by Tally name, custom groups under the matched parent), parties (ledgers under Sundry Debtors/Creditors become customers/vendors with GSTIN if present), stock items (name, unit, HSN, opening qty/rate → opening stock rows §7), and **opening balances** into the §7 opening-balance wizard as a pre-filled draft (never auto-posted). Unparseable/unknown elements land in the import report with row-level reasons; import is idempotent by (type, name).

**Endpoints & screens (M6):** `accounting/ca-pack` (runs list + generate + download), `accounting/ca-access` (grants), `/ca/*` portal, `accounting/tally/export`, `accounting/tally/import`. Permissions: `ca_pack.generate/download`, `ca_access.manage`, `tally.export/import`; portal actions live under the `ca` guard (no spatie — simple grant check middleware).

**Edge cases:** pack for a period with unlocked months (banner: "period not locked — numbers may change; lock before sharing"); CA grant expiring mid-query-thread (read access ends, thread preserved); two CAs on one business (allowed, queries carry author); Tally import of a group tree deeper than ours (preserved as-is under matched primary group); Tally export where a party has ₹0 balance but transactions (exported — Tally needs the ledger for voucher references); voucher narration > Tally's practical length (truncate at 4,000 chars, note in mapping report); closing proposal referencing an account the CA typed but doesn't exist (proposal stores name; approval screen forces mapping to a real account before post).

**Module tests:** pack manifest lists every file with matching SHA-256; reconciliation PDF shows all-PASS on the golden dataset; XML export of the §5.1 world re-imported into `TallyXmlImporter` round-trips ledgers/balances losslessly (self-round-trip is our proxy for TallyPrime import — plus a fixture asserting the exact envelope for one Sales voucher, byte-for-byte); CA query lifecycle; closing proposal approve→post writes audit trail and appears in TB.

### 3.11 Non-functional requirements

**3.11.1 Tenancy isolation tests.** Every new controller test asserts cross-business 404 (pattern exists in `tests/Feature/*`); plus a dedicated `tests/Feature/Accounting/TenancyIsolationTest.php`: two seeded businesses post interleaved vouchers; assert TBs are disjoint, sequences independent, `verify-integrity --business=` scoping works, and a Super-Admin cross-business view never leaks into posting context.

**3.11.2 Transactions, locking, idempotency.** All postings inside `DB::transaction(attempts: 3)`; lock order §3.1.6; sequences via `FOR UPDATE`; stock issue locks the product's WA/lot rows (`SELECT … FOR UPDATE` on `stock_lots` / a per-product `stock_balances` row) before costing; **every posting endpoint takes an idempotency key** (hidden form token = draft id + action; honoured server-side via `journals.idempotency_key` unique index). Queue jobs that post (e-invoice, amortisation, payroll) are idempotent by construction (keys named in their tasks).

**3.11.3 RBAC & maker-checker.** Permission map in §3-module sections. Maker-checker on **manual Journal vouchers and CA closing proposals**: `vouchers.create` saves drafts; `vouchers.post` (a different person — enforced `posted_by != created_by` unless business toggle `businesses.maker_checker_journals = false` for one-person shops, default **true** when >1 admin exists) posts. Salary-structure approval already models this pattern (§1.3) — reuse its UI idiom.

**3.11.4 `accounting:verify-integrity`** (extended milestone by milestone): (1) every posted journal: Σlines Dr = Σlines Cr = header totals; (2) TB per business: ΣDr = ΣCr over `journal_lines` **and** over `account_balances` (cross-check materialisation); (3) `account_balances` = recomputed from lines (sampled daily, full on `--deep`); (4) stock: Σ`stock_ledger_entries` value per business = ledger 1211 balance; per product×warehouse `balance_qty_after` chain unbroken; no negative balances unless allowed; (5) GST: `gst_ledger_entries` Σ by type = each 241x ledger movement per period; (6) TDS: `tds_entries` unremitted Σ = TDS Payable children; (7) no orphan `journal_lines` (FK covers it — assert anyway), no posted journal without lines, no line on `is_group` accounts; (8) sequences: for each (type, FY) the set of posted numbers is exactly `1..next-1` (gapless proof); (9) documents flagged finalised have their journal and vice-versa (source_type back-check); (10) Schedule III unmapped accounts = 0. Output: table + exit code; `--business=`, `--fix-balances` (rebuild materialisation only — never touches journals). Scheduled daily 03:00; run manually after every milestone (§0 rule 4).

**3.11.5 Backup/restore.** Extend existing spatie nightly DB backup: add `--only-db` stays, plus weekly full including `storage/app/businesses` (attachments are audit evidence); document restore drill in `docs/` (task M0); backup failure notifies (spatie's notification hooked to mail — config only).

**3.11.6 Data retention.** 8 years minimum (Sec 128(5) Companies Act; Sec 36 CGST): no purge commands for journals/documents exist at all; `accounting:prune-audit-trail` refuses < 8 FY (§3.1.10); soft-deleted pre-accounting rows are retained as-is.

**3.11.7 Performance budgets.** Posting a 50-line voucher ≤ 250 ms server-side; TB for a 100k-line FY ≤ 2 s (via `account_balances`); statements never N+1 (eager tests with `Model::preventLazyLoading` in the accounting test base class). Materialisation is synchronous (SME volumes; DECISION-15) with `accounting:rebuild-balances` as the escape hatch.

**3.11.8 Failure surfaces.** Posting failures must be loud: exceptions bubble to a flash-error with the human reason (period locked, unbalanced, negative stock, sequence conflict); never a silent catch. The `withExceptions` block in `bootstrap/app.php` gains a renderer mapping `App\Exceptions\Accounting\*` to friendly messages (only such global change permitted).

---
## 4. Data model — canonical DDL

Laravel migration bodies (MySQL 8.0, InnoDB, `strict => true`). Shorthand used exactly as written: `MONEY` = `decimal(20,4)`, `QTY` = `decimal(20,6)`, `RATE` = `decimal(20,6)`, `PCT` = `decimal(9,4)`. Every table: engine InnoDB, `id()` PK, `timestamps()` unless noted; `biz()` means `$t->foreignId('business_id')->constrained()->cascadeOnDelete();` **NO ledger/audit table has `softDeletes()` — ever.** Raw `DB::statement` blocks (CHECKs/triggers) run in the same migration after `Schema::create`. Migration file naming: `2026_08_xx_...` sequential per backlog task that owns it (task list names the owner of each table).

### 4.1 Ledger core

```php
Schema::create('fiscal_years', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('label', 9);                 // '2025-26'
    $t->date('starts_on'); $t->date('ends_on');
    $t->boolean('is_locked')->default(false);
    $t->timestamp('locked_at')->nullable();
    $t->foreignId('locked_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','starts_on']);
});
Schema::create('accounting_periods', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
    $t->date('starts_on'); $t->date('ends_on');   // calendar month
    $t->boolean('is_locked')->default(false);
    $t->timestamp('locked_at')->nullable();
    $t->foreignId('locked_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','starts_on']);
});
Schema::create('ledger_accounts', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete();
    $t->string('code', 20); $t->string('name', 160);
    $t->enum('nature', ['asset','liability','equity','income','expense']);
    $t->enum('normal_balance', ['debit','credit']);
    $t->boolean('is_group')->default(false);
    $t->boolean('affects_gross_profit')->default(false);
    $t->string('schedule_iii_head', 60)->nullable();     // slug list §3.9.6
    $t->string('cash_flow_bucket', 20)->nullable();      // operating|investing|financing override
    $t->string('tally_group_name', 120)->nullable();
    $t->string('tally_alias', 160)->nullable();          // stable export name (§3.10.3)
    $t->boolean('is_system')->default(false);
    $t->nullableMorphs('party');                         // party_type/party_id → Customer|Vendor|Employee
    $t->foreignId('bank_account_id')->nullable();        // FK added in §4.9 after bank_accounts exists
    $t->boolean('is_active')->default(true);
    $t->timestamps();
    $t->unique(['business_id','code']);
    $t->index(['business_id','parent_id']); $t->index(['business_id','is_group']);
});
Schema::create('voucher_types', function (Blueprint $t) {  // GLOBAL — no business_id (§3.1.9)
    $t->id();
    $t->string('code', 8)->unique();        // SAL PUR PAY RCT CON JRN DBN CRN STJ DLN RCN SO PO
    $t->string('name', 40); $t->string('default_prefix', 12);
    $t->boolean('is_system')->default(true);
    $t->timestamps();
});
Schema::create('voucher_sequences', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('voucher_type_id')->constrained()->restrictOnDelete();
    $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
    $t->string('prefix', 20);
    $t->unsignedBigInteger('next_number')->default(1);
    $t->unsignedTinyInteger('padding')->default(4);
    $t->timestamps();
    $t->unique(['business_id','voucher_type_id','fiscal_year_id']);
});
Schema::create('journals', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
    $t->foreignId('accounting_period_id')->constrained()->restrictOnDelete();
    $t->foreignId('voucher_type_id')->constrained()->restrictOnDelete();
    $t->string('voucher_number', 40)->nullable();        // null while draft (§3.1.8)
    $t->date('voucher_date');
    $t->string('narration', 4000)->nullable();
    $t->string('reference', 100)->nullable();
    $t->nullableMorphs('source');                        // source_type/source_id
    $t->enum('status', ['draft','posted','reversed'])->default('draft');
    $t->decimal('total_debit', 20, 4)->default(0);
    $t->decimal('total_credit', 20, 4)->default(0);
    $t->timestamp('posted_at')->nullable();
    $t->foreignId('posted_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->foreignId('reversal_of_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
    $t->foreignId('reversed_by_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
    $t->string('idempotency_key', 120)->nullable();
    $t->timestamps();
    $t->unique(['business_id','voucher_type_id','fiscal_year_id','voucher_number']);
    $t->unique(['business_id','idempotency_key']);
    $t->index(['business_id','voucher_date']); $t->index(['source_type','source_id']);
    $t->index(['business_id','status']);
});
DB::statement("ALTER TABLE journals ADD CONSTRAINT chk_journals_balanced CHECK (total_debit = total_credit)");
Schema::create('journal_lines', function (Blueprint $t) {
    $t->id();
    $t->foreignId('journal_id')->constrained()->restrictOnDelete();
    biz();                                               // denormalised for report speed
    $t->unsignedSmallInteger('line_no');
    $t->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
    $t->decimal('debit', 20, 4)->default(0);
    $t->decimal('credit', 20, 4)->default(0);
    $t->nullableMorphs('party');
    $t->foreignId('cost_centre_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $t->decimal('quantity', 20, 6)->nullable();
    $t->decimal('tax_rate_percent', 9, 4)->nullable();
    $t->string('hsn_code', 8)->nullable();
    $t->enum('line_role', ['normal','purchase_value','cogs','round_off','tax','tds','landed_cost'])->default('normal');
    $t->string('narration', 1000)->nullable();
    $t->timestamp('created_at')->nullable();             // no updated_at — lines never update after post
    $t->unique(['journal_id','line_no']);
    $t->index(['business_id','ledger_account_id']);
    $t->index(['business_id','project_id']); $t->index(['business_id','cost_centre_id']);
    $t->index(['party_type','party_id']);
});
DB::statement("ALTER TABLE journal_lines ADD CONSTRAINT chk_lines_one_sided
    CHECK (debit >= 0 AND credit >= 0 AND ((debit = 0) <> (credit = 0)))");
Schema::create('account_balances', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ledger_account_id')->constrained()->cascadeOnDelete();
    $t->foreignId('accounting_period_id')->constrained()->cascadeOnDelete();
    $t->decimal('period_debit', 20, 4)->default(0);
    $t->decimal('period_credit', 20, 4)->default(0);
    $t->timestamps();
    $t->unique(['business_id','ledger_account_id','accounting_period_id'], 'uq_acct_balance');
});
// opening/closing are derived by summation across periods ≤ target — no stored opening
// column to drift. Rebuild = DELETE per business + INSERT..SELECT GROUP BY.
Schema::create('cost_centres', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('code', 20); $t->string('name', 120);
    $t->foreignId('parent_id')->nullable()->constrained('cost_centres')->restrictOnDelete();
    $t->boolean('is_active')->default(true); $t->timestamps();
    $t->unique(['business_id','code']);
});
Schema::create('projects', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('code', 20); $t->string('name', 160);
    $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
    $t->enum('status', ['open','completed','cancelled'])->default('open');
    $t->date('starts_on')->nullable(); $t->date('ends_on')->nullable();
    $t->decimal('budgeted_revenue', 20, 4)->nullable();
    $t->decimal('budgeted_cost', 20, 4)->nullable();
    $t->timestamps();
    $t->unique(['business_id','code']);
});
Schema::create('audit_trail', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('business_id')->nullable()->index();  // plain col: survives business delete
    $t->string('user_guard', 20)->nullable(); $t->unsignedBigInteger('user_id')->nullable();
    $t->string('action', 30);
    $t->string('auditable_type', 120); $t->unsignedBigInteger('auditable_id');
    $t->json('old_values')->nullable(); $t->json('new_values')->nullable();
    $t->string('ip', 45)->nullable(); $t->string('user_agent', 255)->nullable();
    $t->timestamp('occurred_at')->index();
    $t->index(['auditable_type','auditable_id']);
});   // partition by YEAR(occurred_at) via raw ALTER in the same migration; no timestamps()
Schema::create('ledger_budgets', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ledger_account_id')->constrained()->cascadeOnDelete();
    $t->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
    $t->json('monthly_amounts');            // 12 values, Apr..Mar, strings '12345.00'
    $t->timestamps();
    $t->unique(['business_id','ledger_account_id','fiscal_year_id']);
});
```

### 4.2 Integrity triggers (raw SQL, one migration — TASK-014)

```sql
-- Balance guard at the moment of posting (§3.1.5 layer 1)
CREATE TRIGGER trg_journals_post_guard BEFORE UPDATE ON journals FOR EACH ROW
BEGIN
  IF OLD.status = 'draft' AND NEW.status = 'posted' THEN
    IF (SELECT COALESCE(SUM(debit),0)  FROM journal_lines WHERE journal_id = NEW.id) <> NEW.total_debit
    OR (SELECT COALESCE(SUM(credit),0) FROM journal_lines WHERE journal_id = NEW.id) <> NEW.total_credit
    OR NEW.total_debit <> NEW.total_credit
    OR NEW.voucher_number IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: journal does not balance or has no number';
    END IF;
  ELSEIF OLD.status = 'posted' THEN
    -- whitelisted transition: posted -> reversed, only reversed_by may change
    IF NOT (NEW.status = 'reversed'
            AND NEW.total_debit = OLD.total_debit AND NEW.total_credit = OLD.total_credit
            AND NEW.voucher_number = OLD.voucher_number AND NEW.voucher_date = OLD.voucher_date) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: posted journals are immutable';
    END IF;
  ELSEIF OLD.status = 'reversed' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: reversed journals are immutable';
  END IF;
END;
CREATE TRIGGER trg_journals_frozen_del BEFORE DELETE ON journals FOR EACH ROW
BEGIN
  IF OLD.status <> 'draft' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: posted journals cannot be deleted';
  END IF;
END;
CREATE TRIGGER trg_journal_lines_frozen_upd BEFORE UPDATE ON journal_lines FOR EACH ROW
BEGIN
  IF (SELECT status FROM journals WHERE id = OLD.journal_id) <> 'draft' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: lines of posted journals are immutable';
  END IF;
END;
CREATE TRIGGER trg_journal_lines_frozen_del BEFORE DELETE ON journal_lines FOR EACH ROW
BEGIN
  IF (SELECT status FROM journals WHERE id = OLD.journal_id) <> 'draft' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: lines of posted journals cannot be deleted';
  END IF;
END;
CREATE TRIGGER trg_audit_trail_no_update BEFORE UPDATE ON audit_trail FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: audit trail is append-only'; END;
CREATE TRIGGER trg_audit_trail_no_delete BEFORE DELETE ON audit_trail FOR EACH ROW
BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ACC: audit trail is append-only'; END;
```
sqlite note: the sqlite test suite (`phpunit.xml`) skips trigger creation (`DB::getDriverName()` guard in the migration); trigger-behaviour tests live in the MySQL addons suite (`phpunit.addons.xml`) — same pattern the repo already uses for MySQL-only tests.

### 4.3 Inventory & costing

```php
Schema::create('uoms', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('code', 10); $t->string('name', 40);
    $t->string('uqc', 5);                    // e-invoice/e-way unit code: KGS, PCS, MTR…
    $t->timestamps(); $t->unique(['business_id','code']);
});
Schema::create('stock_balances', function (Blueprint $t) {   // lock target + fast on-hand
    $t->id(); biz();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
    $t->decimal('qty_on_hand', 20, 6)->default(0);
    $t->decimal('value_on_hand', 20, 4)->default(0);
    $t->timestamps();
    $t->unique(['business_id','product_id','warehouse_id']);
});
Schema::create('stock_ledger_entries', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
    $t->date('entry_date');
    $t->enum('direction', ['in','out']);
    $t->decimal('quantity', 20, 6);          // always > 0
    $t->decimal('rate', 20, 6);
    $t->decimal('value', 20, 4);             // round(qty*rate, 2) — §3.3.2 boundary
    $t->decimal('balance_qty_after', 20, 6);
    $t->decimal('balance_value_after', 20, 4);
    $t->foreignId('lot_id')->nullable();     // FK to stock_lots added after create
    $t->nullableMorphs('source');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamp('created_at')->nullable(); // append-only: no updated_at
    $t->index(['business_id','product_id','warehouse_id','entry_date','id'], 'ix_sle_running');
    $t->index(['source_type','source_id']);
});
DB::statement("ALTER TABLE stock_ledger_entries ADD CONSTRAINT chk_sle_qty_pos CHECK (quantity > 0)");
Schema::create('stock_lots', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
    $t->date('received_on');
    $t->decimal('qty_in', 20, 6); $t->decimal('qty_remaining', 20, 6);
    $t->decimal('unit_cost', 20, 6);
    $t->nullableMorphs('source');
    $t->timestamps();
    $t->index(['business_id','product_id','warehouse_id','received_on','id'], 'ix_lots_fifo');
});
Schema::create('landed_cost_vouchers', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();
    $t->date('voucher_date');
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->json('goods_receipt_ids');           // targeted GRNs
    $t->decimal('total_amount', 20, 4)->default(0);
    $t->string('narration', 1000)->nullable();
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('landed_cost_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('landed_cost_voucher_id')->constrained()->cascadeOnDelete();
    $t->enum('cost_type', ['freight','loading','packing','customs_duty','labour','insurance','other']);
    $t->enum('apportion_basis', ['by_value','by_quantity','by_weight']);
    $t->decimal('amount', 20, 4);
    $t->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('bank_account_id')->nullable();        // cash/bank when paid immediately
    $t->boolean('is_rcm')->default(false);
    $t->decimal('gst_rate', 9, 4)->default(0);
    $t->enum('itc_status', ['eligible','blocked_17_5','none'])->default('none');
    $t->json('weights')->nullable();          // per-GRN-line weights for by_weight
    $t->timestamps();
});
Schema::create('stock_transfers', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable(); $t->date('transfer_date');
    $t->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
    $t->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->string('narration', 1000)->nullable();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('stock_transfer_items', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->decimal('quantity', 20, 6);
    $t->decimal('transferred_cost', 20, 4)->nullable();  // set at finalise
    $t->timestamps();
});
Schema::create('stock_takes', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable(); $t->date('count_date');
    $t->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
    $t->enum('kind', ['count','nrv'])->default('count');
    $t->enum('status', ['draft','counted','approved','posted'])->default('draft');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('counted_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('stock_take_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->decimal('book_qty', 20, 6); $t->decimal('counted_qty', 20, 6)->nullable();
    $t->decimal('book_value', 20, 4);
    $t->decimal('nrv_per_unit', 20, 6)->nullable();      // kind = nrv
    $t->decimal('variance_value', 20, 4)->nullable();
    $t->timestamps();
});
Schema::create('boms', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();   // finished good
    $t->string('name', 120); $t->decimal('output_qty', 20, 6)->default(1);
    $t->boolean('is_active')->default(true); $t->timestamps();
});
Schema::create('bom_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('bom_id')->constrained()->cascadeOnDelete();
    $t->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
    $t->decimal('qty_per_output', 20, 6); $t->decimal('scrap_percent', 9, 4)->default(0);
    $t->timestamps();
});
Schema::create('stock_journals', function (Blueprint $t) {   // manufacture voucher (STJ)
    $t->id(); biz();
    $t->string('number', 40)->nullable(); $t->date('voucher_date');
    $t->foreignId('bom_id')->nullable()->constrained()->nullOnDelete();
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->decimal('conversion_cost', 20, 4)->default(0);
    $t->foreignId('conversion_cost_account_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete();
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('narration', 1000)->nullable();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('stock_journal_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('stock_journal_id')->constrained()->cascadeOnDelete();
    $t->enum('side', ['consume','produce']);
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
    $t->decimal('quantity', 20, 6);
    $t->decimal('value', 20, 4)->nullable();  // consume: engine; produce: allocated
    $t->timestamps();
});
```

### 4.4 Quote-to-Cash

```php
Schema::create('quotation_revisions', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
    $t->unsignedSmallInteger('revision_no');
    $t->json('header_snapshot'); $t->json('items_snapshot');
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamp('created_at')->nullable();
    $t->unique(['quotation_id','revision_no']);
});
Schema::create('delivery_notes', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();
    $t->foreignId('customer_id')->constrained()->restrictOnDelete();
    $t->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
    $t->date('delivery_date');
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->string('transporter_name', 120)->nullable(); $t->string('transporter_gstin', 15)->nullable();
    $t->string('vehicle_number', 20)->nullable(); $t->string('lr_number', 40)->nullable();
    $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->timestamp('finalised_at')->nullable();
    $t->foreignId('finalised_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('delivery_note_items', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
    $t->foreignId('sales_order_item_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('product_id')->constrained()->restrictOnDelete();
    $t->decimal('quantity', 20, 6);
    $t->decimal('issued_cost', 20, 4)->nullable();   // engine cost at finalise — frozen
    $t->decimal('billed_qty', 20, 6)->default(0);
    $t->timestamps();
});
Schema::create('delivery_note_invoice', function (Blueprint $t) {  // pivot
    $t->id();
    $t->foreignId('delivery_note_id')->constrained()->restrictOnDelete();
    $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
    $t->unique(['delivery_note_id','invoice_id']);
});
Schema::create('receipts', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();
    $t->foreignId('customer_id')->constrained()->restrictOnDelete();
    $t->date('receipt_date');
    $t->foreignId('bank_account_id')->nullable();    // FK added in §4.9; null = Cash ledger
    $t->string('mode', 20)->default('cash');         // display only; ledger comes from bank_account_id
    $t->decimal('amount', 20, 4);
    $t->decimal('tds_deducted', 20, 4)->default(0);  // customer-side TDS (§3.4.6)
    $t->string('reference_no', 100)->nullable();
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('narration', 1000)->nullable();
    $t->foreignId('legacy_payment_id')->nullable();  // backfill provenance (§7)
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('receipt_allocations', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('receipt_id')->constrained()->cascadeOnDelete();
    $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
    $t->decimal('amount', 20, 4);
    $t->timestamps();
    $t->index(['business_id','invoice_id']);
});
Schema::create('credit_notes', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();
    $t->foreignId('customer_id')->constrained()->restrictOnDelete();
    $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
    $t->date('note_date');
    $t->enum('reason', ['sales_return','post_sale_discount','rate_revision','deficiency']);
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    // totals + GST columns identical shape to invoices (§4.10.5): subtotal, discount…,
    // taxable_total, cgst_total, sgst_total, igst_total, cess_total, round_off_amount,
    // grand_total  — all MONEY; place_of_supply char(2); gst_treatment enum
    $t->decimal('subtotal', 20, 4)->default(0);
    $t->decimal('taxable_total', 20, 4)->default(0);
    $t->decimal('cgst_total', 20, 4)->default(0); $t->decimal('sgst_total', 20, 4)->default(0);
    $t->decimal('igst_total', 20, 4)->default(0); $t->decimal('cess_total', 20, 4)->default(0);
    $t->decimal('round_off_amount', 20, 4)->default(0);
    $t->decimal('grand_total', 20, 4)->default(0);
    $t->char('place_of_supply', 2)->nullable();
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('credit_note_items', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
    $t->foreignId('invoice_item_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $t->string('description', 500);
    $t->string('hsn_code', 8)->nullable();
    $t->decimal('quantity', 20, 6)->default(0);
    $t->decimal('rate', 20, 6)->default(0);
    $t->decimal('taxable_value', 20, 4)->default(0);
    $t->decimal('cgst_rate', 9, 4)->default(0); $t->decimal('cgst_amount', 20, 4)->default(0);
    $t->decimal('sgst_rate', 9, 4)->default(0); $t->decimal('sgst_amount', 20, 4)->default(0);
    $t->decimal('igst_rate', 9, 4)->default(0); $t->decimal('igst_amount', 20, 4)->default(0);
    $t->decimal('cess_rate', 9, 4)->default(0); $t->decimal('cess_amount', 20, 4)->default(0);
    $t->boolean('restock')->default(false);
    $t->timestamps();
});
```
`debit_notes` + `debit_note_items`: identical shape to credit notes with `vendor_id` + `purchase_bill_id` instead of customer/invoice, `reason` enum(`purchase_return`,`rate_dispute`,`shortage`,`other`), and `return_stock` toggle per line. (Same migration, TASK-053.)

### 4.5 Purchase-to-Pay

```php
Schema::create('purchase_bills', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();               // our voucher no
    $t->foreignId('vendor_id')->constrained()->restrictOnDelete();
    $t->string('vendor_invoice_number', 60);
    $t->date('vendor_invoice_date');
    $t->date('bill_date'); $t->date('due_date')->nullable();
    $t->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->decimal('taxable_total', 20, 4)->default(0);
    $t->decimal('cgst_total', 20, 4)->default(0); $t->decimal('sgst_total', 20, 4)->default(0);
    $t->decimal('igst_total', 20, 4)->default(0); $t->decimal('cess_total', 20, 4)->default(0);
    $t->decimal('round_off_amount', 20, 4)->default(0);
    $t->decimal('grand_total', 20, 4)->default(0);
    $t->decimal('tds_total', 20, 4)->default(0);
    $t->char('place_of_supply', 2)->nullable();
    $t->boolean('is_rcm')->default(false);
    $t->json('match_report')->nullable();               // §3.5.4 snapshot
    $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('attachment')->nullable();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','number']);
    $t->unique(['business_id','vendor_id','vendor_invoice_number'], 'uq_vendor_invoice'); // double-entry-of-bill guard
});
Schema::create('purchase_bill_items', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('purchase_bill_id')->constrained()->cascadeOnDelete();
    $t->foreignId('goods_receipt_item_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('expense_ledger_account_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete(); // non-stock lines
    $t->string('description', 500);
    $t->string('hsn_code', 8)->nullable();
    $t->decimal('quantity', 20, 6)->default(0);
    $t->decimal('rate', 20, 6)->default(0);
    $t->decimal('taxable_value', 20, 4)->default(0);
    $t->decimal('cgst_rate', 9, 4)->default(0); $t->decimal('cgst_amount', 20, 4)->default(0);
    $t->decimal('sgst_rate', 9, 4)->default(0); $t->decimal('sgst_amount', 20, 4)->default(0);
    $t->decimal('igst_rate', 9, 4)->default(0); $t->decimal('igst_amount', 20, 4)->default(0);
    $t->decimal('cess_rate', 9, 4)->default(0); $t->decimal('cess_amount', 20, 4)->default(0);
    $t->enum('itc_status', ['eligible','blocked_17_5','capital_goods','rcm_pending_payment','none'])->default('eligible');
    $t->boolean('is_rcm')->default(false);
    $t->foreignId('tds_section_id')->nullable();        // FK after tds_sections
    $t->boolean('is_capital')->default(false);
    $t->boolean('is_prepaid')->default(false);
    $t->date('period_from')->nullable(); $t->date('period_to')->nullable();
    $t->timestamps();
});
Schema::create('vendor_payments', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable();
    $t->foreignId('vendor_id')->constrained()->restrictOnDelete();
    $t->date('payment_date');
    $t->foreignId('bank_account_id')->nullable();       // FK §4.9; null = Cash
    $t->string('mode', 20)->default('bank');
    $t->decimal('amount', 20, 4);
    $t->decimal('tds_deducted', 20, 4)->default(0);     // payment-leg TDS (§3.7)
    $t->string('reference_no', 100)->nullable();
    $t->enum('status', ['draft','finalised','cancelled'])->default('draft');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('narration', 1000)->nullable();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
Schema::create('vendor_payment_allocations', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('vendor_payment_id')->constrained()->cascadeOnDelete();
    $t->foreignId('purchase_bill_id')->constrained()->restrictOnDelete();
    $t->decimal('amount', 20, 4);
    $t->timestamps(); $t->index(['business_id','purchase_bill_id']);
});
Schema::create('prepaid_schedules', function (Blueprint $t) {
    $t->id(); biz();
    $t->nullableMorphs('source');                        // purchase_bill_item | expense
    $t->foreignId('expense_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
    $t->decimal('total_amount', 20, 4);
    $t->date('period_from'); $t->date('period_to');
    $t->enum('status', ['active','completed','cancelled'])->default('active');
    $t->timestamps();
});
Schema::create('prepaid_schedule_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('prepaid_schedule_id')->constrained()->cascadeOnDelete();
    $t->date('post_on'); $t->decimal('amount', 20, 4);
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->timestamps();
    $t->unique(['prepaid_schedule_id','post_on']);
});
Schema::create('accrual_vouchers', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('number', 40)->nullable(); $t->date('voucher_date');
    $t->foreignId('expense_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
    $t->decimal('amount', 20, 4); $t->string('narration', 1000)->nullable();
    $t->enum('status', ['draft','posted','auto_reversed'])->default('draft');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('reversal_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
    $t->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps(); $t->unique(['business_id','number']);
});
```

### 4.6 GST

```php
Schema::create('tax_rates', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('name', 40);                  // 'GST 18%'
    $t->decimal('rate', 9, 4);               // 18.0000
    $t->decimal('cess_rate', 9, 4)->default(0);
    $t->date('active_from'); $t->date('active_to')->nullable();
    $t->boolean('is_active')->default(true); $t->timestamps();
    $t->unique(['business_id','name','active_from']);
});
Schema::create('gst_ledger_entries', function (Blueprint $t) {
    $t->id(); biz();
    $t->date('entry_date');
    $t->string('return_period', 7);          // '2026-05'
    $t->enum('gst_type', ['output_cgst','output_sgst','output_igst','output_cess',
        'itc_cgst','itc_sgst','itc_igst','itc_cess','rcm_output','rcm_itc']);
    $t->decimal('taxable_value', 20, 4);
    $t->decimal('amount', 20, 4);            // signed: credit notes negative
    $t->decimal('rate', 9, 4);
    $t->string('counterparty_gstin', 15)->nullable();
    $t->char('place_of_supply', 2)->nullable();
    $t->string('hsn_code', 8)->nullable();
    $t->enum('gstr1_bucket', ['B2B','B2CL','B2CS','CDNR','CDNUR','EXP','SEZ','NIL','AT','TXPD'])->nullable();
    $t->enum('itc_status', ['eligible','blocked_17_5','capital_goods','rcm_pending_payment',
        'reversed_rule37','reversed_rule42','reclaimed'])->nullable();
    $t->nullableMorphs('source');
    $t->foreignId('journal_id')->constrained()->restrictOnDelete();
    $t->timestamp('created_at')->nullable();  // append-only; corrections are negations
    $t->index(['business_id','return_period','gst_type']);
    $t->index(['source_type','source_id']);
});
Schema::create('einvoices', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('credit_note_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('irn', 64)->nullable();
    $t->string('ack_no', 30)->nullable(); $t->timestamp('ack_date')->nullable();
    $t->text('signed_qr')->nullable();
    $t->enum('status', ['pending','generated','cancelled','failed'])->default('pending');
    $t->string('cancel_reason', 200)->nullable();
    $t->json('request_payload')->nullable(); $t->json('response_payload')->nullable();
    $t->unsignedTinyInteger('attempts')->default(0);
    $t->timestamps();
    $t->unique(['business_id','irn']);
});
Schema::create('eway_bills', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
    $t->foreignId('delivery_note_id')->nullable()->constrained()->nullOnDelete();
    $t->string('ewb_number', 20)->nullable();
    $t->timestamp('generated_at')->nullable(); $t->timestamp('valid_until')->nullable();
    $t->string('vehicle_number', 20)->nullable(); $t->unsignedInteger('distance_km')->nullable();
    $t->enum('status', ['pending','generated','cancelled','expired','failed'])->default('pending');
    $t->json('request_payload')->nullable(); $t->json('response_payload')->nullable();
    $t->timestamps();
});
Schema::create('gst_returns', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('return_period', 7);
    $t->enum('return_type', ['GSTR1','GSTR3B']);
    $t->enum('status', ['draft','built','filed'])->default('draft');
    $t->json('payload')->nullable();          // snapshot at build/file
    $t->json('books_diff')->nullable();       // must be empty at 'filed'
    $t->date('filed_on')->nullable();
    $t->foreignId('built_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','return_period','return_type']);
});
Schema::create('gstr2b_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('return_period', 7);
    $t->string('supplier_gstin', 15); $t->string('supplier_name', 200)->nullable();
    $t->string('doc_number', 60); $t->date('doc_date');
    $t->enum('doc_type', ['invoice','credit_note','debit_note'])->default('invoice');
    $t->decimal('taxable_value', 20, 4);
    $t->decimal('cgst', 20, 4)->default(0); $t->decimal('sgst', 20, 4)->default(0);
    $t->decimal('igst', 20, 4)->default(0); $t->decimal('cess', 20, 4)->default(0);
    $t->boolean('itc_available')->default(true);
    $t->enum('match_status', ['unmatched','matched','value_mismatch','missing_in_books','accepted'])->default('unmatched');
    $t->foreignId('purchase_bill_id')->nullable()->constrained()->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','return_period','supplier_gstin','doc_number','doc_type'], 'uq_2b_line');
});
```

### 4.7 TDS

```php
Schema::create('tds_sections', function (Blueprint $t) {   // GLOBAL (§3.1.9)
    $t->id();
    $t->string('code', 12)->unique();        // '194C','194J_A','194J_B','194H','194I_A','194I_B','194Q','206C_1H'
    $t->string('nature', 120);
    $t->decimal('rate_individual', 9, 4); $t->decimal('rate_other', 9, 4);
    $t->decimal('rate_no_pan', 9, 4)->default(20);
    $t->decimal('threshold_single', 20, 4)->nullable();
    $t->decimal('threshold_aggregate', 20, 4)->nullable();
    $t->enum('threshold_mode', ['full_on_cross','excess_only'])->default('full_on_cross');
    $t->enum('threshold_basis', ['per_fy','per_month'])->default('per_fy');
    $t->boolean('is_tcs')->default(false);
    $t->date('active_from'); $t->date('active_to')->nullable();
    $t->timestamps();
});
Schema::create('party_tds_profiles', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
    $t->foreignId('default_tds_section_id')->nullable()->constrained('tds_sections')->nullOnDelete();
    $t->enum('deductee_type', ['individual_huf','other'])->default('other');
    $t->boolean('is_transporter_nil')->default(false);   // 194C(6)
    $t->boolean('force_higher_rate')->default(false);    // legacy 206AB style
    $t->string('ldc_certificate_no', 40)->nullable();
    $t->decimal('ldc_rate', 9, 4)->nullable();
    $t->decimal('ldc_cap_amount', 20, 4)->nullable();
    $t->date('ldc_valid_from')->nullable(); $t->date('ldc_valid_to')->nullable();
    $t->timestamps();
    $t->unique(['business_id','vendor_id']);
});
Schema::create('tds_entries', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('vendor_id')->constrained()->restrictOnDelete();
    $t->foreignId('tds_section_id')->constrained()->restrictOnDelete();
    $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
    $t->enum('trigger', ['bill','payment']);
    $t->date('deducted_on');
    $t->decimal('base_amount', 20, 4); $t->decimal('rate', 9, 4); $t->decimal('amount', 20, 4);
    $t->unsignedTinyInteger('quarter');       // 1..4 (Q1 = Apr-Jun)
    $t->nullableMorphs('source');             // purchase_bill | vendor_payment | expense
    $t->foreignId('journal_id')->constrained()->restrictOnDelete();
    $t->foreignId('tds_challan_id')->nullable();  // FK after challans
    $t->timestamps();
    $t->index(['business_id','vendor_id','tds_section_id','fiscal_year_id'], 'ix_tds_threshold');
});
Schema::create('tds_challans', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('bsr_code', 10); $t->string('challan_number', 10);
    $t->date('deposited_on'); $t->decimal('amount', 20, 4);
    $t->string('period', 7);                  // month it pays for
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->timestamps();
});
```

### 4.8 Fixed assets

```php
Schema::create('it_blocks', function (Blueprint $t) {      // seeded per business (rates are law but editable history matters)
    $t->id(); biz();
    $t->string('name', 80);                   // 'Plant & Machinery @15%'
    $t->decimal('rate', 9, 4);
    $t->timestamps(); $t->unique(['business_id','name']);
});
Schema::create('fixed_assets', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('code', 30); $t->string('name', 160);
    $t->foreignId('asset_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
    $t->foreignId('accum_dep_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
    $t->foreignId('it_block_id')->constrained()->restrictOnDelete();
    $t->nullableMorphs('source');             // purchase_bill_item | expense
    $t->unsignedBigInteger('operational_asset_id')->nullable();  // link to existing assets table (no FK — module optional)
    $t->date('capitalised_on'); $t->date('put_to_use_on')->nullable();  // null = CWIP
    $t->decimal('gross_cost', 20, 4);
    $t->unsignedSmallInteger('useful_life_months');
    $t->enum('method', ['slm','wdv'])->default('slm');
    $t->decimal('residual_percent', 9, 4)->default(5);
    $t->enum('status', ['cwip','active','disposed'])->default('active');
    $t->date('disposed_on')->nullable(); $t->decimal('disposal_proceeds', 20, 4)->nullable();
    $t->timestamps(); $t->unique(['business_id','code']);
});
Schema::create('fixed_asset_events', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('fixed_asset_id')->constrained()->cascadeOnDelete();
    $t->enum('kind', ['addition','improvement','disposal','transfer_from_cwip']);
    $t->date('event_date'); $t->decimal('amount', 20, 4);
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->string('narration', 500)->nullable(); $t->timestamps();
});
Schema::create('depreciation_runs', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('accounting_period_id')->constrained()->restrictOnDelete();
    $t->enum('status', ['draft','posted'])->default('draft');
    $t->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
    $t->foreignId('run_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','accounting_period_id']);
});
Schema::create('depreciation_run_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('depreciation_run_id')->constrained()->cascadeOnDelete();
    $t->foreignId('fixed_asset_id')->constrained()->restrictOnDelete();
    $t->decimal('amount', 20, 4);
    $t->decimal('book_value_after', 20, 4);
    $t->timestamps();
});
Schema::create('it_block_fy_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('it_block_id')->constrained()->restrictOnDelete();
    $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
    $t->decimal('opening_wdv', 20, 4)->default(0);
    $t->decimal('additions_180_plus', 20, 4)->default(0);
    $t->decimal('additions_under_180', 20, 4)->default(0);
    $t->decimal('disposal_proceeds', 20, 4)->default(0);
    $t->decimal('depreciation', 20, 4)->default(0);
    $t->decimal('closing_wdv', 20, 4)->default(0);
    $t->decimal('stcg_stcl', 20, 4)->default(0);
    $t->timestamps();
    $t->unique(['business_id','it_block_id','fiscal_year_id']);
});
```

### 4.9 Banking, CA layer

```php
Schema::create('bank_accounts', function (Blueprint $t) {
    $t->id(); biz();
    $t->string('name', 120);                  // 'HDFC CA **3421'
    $t->enum('kind', ['bank','cash','od','wallet'])->default('bank');
    $t->string('bank_name', 120)->nullable(); $t->string('account_number', 30)->nullable();
    $t->string('ifsc', 11)->nullable();
    $t->foreignId('ledger_account_id')->constrained()->restrictOnDelete();  // auto-created leaf
    $t->boolean('is_active')->default(true); $t->timestamps();
});
DB::statement('ALTER TABLE ledger_accounts ADD CONSTRAINT fk_la_bank FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)');
// + add the deferred bank_account_id FKs on receipts / vendor_payments / landed_cost_lines / expenses here
Schema::create('bank_statement_imports', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('bank_account_id')->constrained()->restrictOnDelete();
    $t->string('filename', 255); $t->enum('format', ['csv','xlsx','mt940']);
    $t->date('from_date')->nullable(); $t->date('to_date')->nullable();
    $t->decimal('closing_balance', 20, 4)->nullable();
    $t->unsignedInteger('line_count')->default(0);
    $t->foreignId('imported_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
});
Schema::create('bank_statement_lines', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('bank_statement_import_id')->constrained()->cascadeOnDelete();
    $t->foreignId('bank_account_id')->constrained()->restrictOnDelete();
    $t->date('txn_date'); $t->string('description', 500)->nullable();
    $t->string('reference', 100)->nullable();
    $t->decimal('debit', 20, 4)->default(0); $t->decimal('credit', 20, 4)->default(0);
    $t->decimal('balance', 20, 4)->nullable();
    $t->string('dedupe_hash', 64);            // sha256(date|amount|ref|running-ordinal)
    $t->enum('match_status', ['unmatched','auto','manual','ignored'])->default('unmatched');
    $t->foreignId('matched_journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
    $t->timestamps();
    $t->unique(['bank_account_id','dedupe_hash']);
});
Schema::create('bank_match_rules', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('bank_account_id')->nullable()->constrained()->cascadeOnDelete();
    $t->string('contains_text', 120);
    $t->foreignId('suggest_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
    $t->timestamps();
});
Schema::create('bank_reconciliations', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('bank_account_id')->constrained()->restrictOnDelete();
    $t->date('as_on'); $t->decimal('statement_balance', 20, 4); $t->decimal('book_balance', 20, 4);
    $t->json('unmatched_summary')->nullable();
    $t->foreignId('prepared_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
});
Schema::create('ca_users', function (Blueprint $t) {
    $t->id();
    $t->string('name', 120); $t->string('email')->unique(); $t->string('password');
    $t->string('firm_name', 160)->nullable(); $t->string('membership_no', 20)->nullable();
    $t->rememberToken(); $t->timestamps();
});
Schema::create('ca_access_grants', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ca_user_id')->constrained()->cascadeOnDelete();
    $t->date('valid_from'); $t->date('valid_until');
    $t->timestamp('revoked_at')->nullable();
    $t->foreignId('granted_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
    $t->unique(['business_id','ca_user_id']);
});
Schema::create('audit_queries', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ca_user_id')->constrained()->restrictOnDelete();
    $t->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
    $t->nullableMorphs('subject');            // any document
    $t->string('title', 200); $t->text('description')->nullable();
    $t->enum('status', ['open','responded','resolved'])->default('open');
    $t->timestamps();
});
Schema::create('audit_query_messages', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('audit_query_id')->constrained()->cascadeOnDelete();
    $t->enum('author_kind', ['ca','client']);
    $t->unsignedBigInteger('author_id');
    $t->text('body'); $t->string('attachment')->nullable();
    $t->timestamps();
});
Schema::create('document_requests', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ca_user_id')->constrained()->restrictOnDelete();
    $t->string('title', 200); $t->text('description')->nullable();
    $t->enum('status', ['requested','uploaded','accepted'])->default('requested');
    $t->string('file')->nullable();
    $t->timestamps();
});
Schema::create('closing_entry_proposals', function (Blueprint $t) {
    $t->id(); biz();
    $t->foreignId('ca_user_id')->constrained()->restrictOnDelete();
    $t->date('proposed_date'); $t->string('narration', 1000);
    $t->json('lines');                        // [{account_code|name, debit, credit, note}]
    $t->enum('status', ['proposed','approved','rejected','posted'])->default('proposed');
    $t->text('client_note')->nullable();
    $t->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->foreignId('posted_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
    $t->timestamps();
});
Schema::create('ca_pack_runs', function (Blueprint $t) {
    $t->id(); biz();
    $t->date('from_date'); $t->date('to_date');
    $t->enum('status', ['queued','building','ready','failed'])->default('queued');
    $t->string('zip_path')->nullable(); $t->string('checksum', 64)->nullable();
    $t->json('manifest')->nullable(); $t->json('reconciliation')->nullable();
    $t->foreignId('generated_by')->nullable()->constrained('admins')->nullOnDelete();
    $t->timestamps();
});
Schema::create('opening_bills', function (Blueprint $t) {   // bill-wise opening balances (§7)
    $t->id(); biz();
    $t->nullableMorphs('party');              // customer | vendor
    $t->string('bill_reference', 60); $t->date('bill_date'); $t->date('due_date')->nullable();
    $t->decimal('open_amount', 20, 4);
    $t->enum('side', ['receivable','payable']);
    $t->timestamps();
});
```

### 4.10 Alterations to existing tables (one migration per backlog task that owns it)

1. **`businesses`** — add: `accounting_enabled` bool default false; `books_start_date` date null; `state_code` char(2) null; `gst_registration_type` enum(regular, composition, unregistered) default regular; `default_valuation_method` enum(weighted_average, fifo, standard) default weighted_average; `allow_negative_stock` bool default false; `enforce_credit_limit` bool default false; `auto_delivery_note` bool default true; `gst_on_advances` bool default false; `rule37_auto_post` bool default false; `maker_checker_journals` bool default true; `match_tolerance_percent` PCT default 2; `grn_tolerance_percent` PCT default 0; `expense_attachment_threshold` MONEY default 5000; `einvoice_enabled` bool default false; `einvoice_credentials` text null (encrypted cast); `eway_credentials` text null (encrypted cast); `hsn_digits` tinyint default 4.
2. **`states`** — add `gst_state_code` char(2) null unique, `is_ut` bool default false; data migration fills all 36 codes (27=Maharashtra, 07=Delhi, 29=Karnataka, … full official list in the TASK-060 migration body).
3. **`products`** — add: `item_type` enum(goods, service, raw_material, finished_good, consumable) default goods; `uom_id` FK uoms null; `alt_uom_id` FK uoms null; `alt_conversion_factor` QTY null; `valuation_method` enum(weighted_average, fifo, standard) null; `standard_cost` RATE null; `is_batch_tracked` bool default false; `is_serial_tracked` bool default false; change `reorder_level` int → QTY.
4. **`customers` / `vendors`** — add: `gst_registration_type` enum(regular, composition, unregistered, overseas, sez, uin) default unregistered; `state_id` FK states null; `pan` char(10) null; `msme_registered` bool default false; `udyam_number` string(20) null; `credit_days` smallint default 30 (vendors also `tds_deductee_type`); `ledger_account_id` FK ledger_accounts null. (`gst_number` column stays; new validated writes only.)
5. **`invoices`** — add: `taxable_total`, `cgst_total`, `sgst_total`, `igst_total`, `cess_total`, `round_off_amount` (MONEY, default 0); `place_of_supply` char(2) null; `gst_treatment` enum per §3.6.2 default taxable; `journal_id` FK journals null; `finalised_at` timestamp null; `finalised_by` FK admins null; `project_id` FK projects null; `legacy_totals` bool default false; `legacy_numbering` bool default false; status enum gains `cancelled`, `pending_irn`. **`invoice_items`** — add `taxable_value` MONEY, the 8 GST rate/amount columns (as §4.4 credit_note_items), `delivery_note_item_id` FK null.
6. **`quotations`** — add `revision_no` smallint default 1, `project_id` FK null. **`quotation_items`** — add `cost_snapshot` RATE null (filled at save from `products.purchase_price` — later from stock WA rate).
7. **`sales_orders`** — add `project_id` FK null. **`sales_order_items`** — add `reserved_qty`, `delivered_qty`, `billed_qty` (QTY default 0).
8. **`purchase_orders`** — add `project_id` FK null. **`goods_receipts`** — add `journal_id` FK null, `finalised_at`, `warehouse_id` FK null (header default). **`goods_receipt_items`** — add `rate_at_receipt` RATE null, `warehouse_id` FK null, `billed_qty` QTY default 0.
9. **`expenses`** — add: `vendor_id` FK null; `bank_account_id` FK null; `is_capital` bool default false; `fixed_asset_id` FK null; `taxable_value` MONEY null; the 8 GST columns; `itc_status` enum as §4.5 default none; `tds_section_id` FK null; `cost_centre_id` FK null; `project_id` FK null; `is_prepaid` bool default false; `period_from`/`period_to` date null; `journal_id` (recording) + `payment_journal_id` FK null; `is_rcm` bool default false.
10. **`expense_categories`** — add `ledger_account_id` FK null, `nature` enum(direct, indirect) default indirect.
11. **`departments`** — add `cost_centre_id` FK null (auto-created per department on first payroll posting).
12. **`payslips`** — add `journal_id` FK null (batch JV reference on each slip of the month is the batch journal id).
13. **`requisitions`** — add `purchase_order_id` FK null, `settlement_status` enum(open, settled) default open, `advance_journal_id` FK null.
14. **`payments`** — no schema change; table becomes read-only legacy after TASK-046 (writes blocked in `PaymentService`, routes redirect).
15. **`reimbursement_claims`** — add `approval_journal_id`, `disbursement_journal_id` FK null.
16. **`leads`** — add `project_id` FK null (set when a project is spawned from a won lead).

---
## 5. Golden tests

These are the acceptance tests for the entire accounting core. Milestone M2 is done when §5.1 passes end-to-end through the real services (no mocks); §5.2 gates M3. Test files: `tests/Feature/Accounting/GoldenTradingTest.php`, `tests/Feature/Accounting/GoldenServicesTest.php` (MySQL addons suite — they exercise triggers).

### 5.1 Golden Test 1 — "Raw Material 1000 + 200 + 600 margin, half sold"

**Scenario (all figures exclusive of GST):**

| Event | Amount |
|---|---|
| Purchase of raw material on credit | ₹1,000 |
| Additional cost — freight/labour, paid in cash | ₹200 |
| Total cost of goods available | ₹1,200 |
| Margin loaded (50% on cost) | ₹600 |
| Quoted / sale value of full lot | ₹1,800 |
| Goods sold — 50% of the lot, at sale value | ₹900 |

Concrete setup for the test: business with `accounting_enabled`, `books_start_date = 2026-04-01`, valuation weighted average; product `RM-GOLD`, 10 units; vendor V; customer C; all events dated April 2026.

**Steps → documents → journals:**

| # | Action in the system | Voucher | Debit | Credit | Amount |
|---|---|---|---|---|---|
| 1 | PO 10 units @ ₹100 → GRN 10 units → Purchase Bill (no GST in base case) | RCN + PUR | Inventory (Stock-in-Hand 1211) [via GRN Clearing 2424, which nets to 0] | Sundry Creditors → V | 1,000 |
| 2 | Landed-cost voucher on the GRN: freight/labour ₹200, paid cash, basis by_value | Landed Cost (JRN, `line_role=landed_cost`) | Inventory (Stock-in-Hand) | Cash (1231) | 200 |
| 3 | Quotation 10 units @ ₹180 (margin 600 = 50% on cost 1,200) → SO → **sell 5 units**: DN 5 units + Invoice 5 × 180 = 900 | SAL | Sundry Debtors → C | Sales (4001) | 900 |
| 4 | The DN finalisation (automatic with the invoice when `auto_delivery_note`) | DLN | Cost of Goods Sold (5101) | Inventory (Stock-in-Hand) | 600 |

Entry 2 is the critical one: the ₹200 is **capitalised into inventory, not charged to P&L** (AS-2). Inventory value becomes ₹1,200 for the full lot — WA rate = 1,200 / 10 = ₹120/unit.

Entry 4 is the second critical one: COGS = 5 units × ₹120 = **₹600 — 50% of actual cost, NOT derived from the quoted margin** and not from `products.purchase_price`.

**Answers to the owner's questions — these are the assertions:**

| Question | Answer |
|---|---|
| ₹1,000 kuthe? | Balance Sheet → Current Assets → Stock-in-Hand on purchase; flows to P&L as COGS proportionately as goods sell |
| ₹200 kuthe? | Added to Stock-in-Hand (capitalised, AS-2). **Not** an Indirect Expense |
| ₹900 (sale) kuthe? | P&L credit side → Sales ₹900; Balance Sheet → Sundry Debtors ₹900 |
| Remaining ₹900 kuthe show karnar? | **Nowhere at ₹900.** The remaining 5 units appear on the Balance Sheet at cost — unsold margin is never booked |
| Remaining chi original value kay ahe? | **₹600** (₹1,200 × 50% unsold, = 5 units × ₹120). The unrealised margin of ₹300 is not recognised anywhere |

**Resulting ledger balances:** Stock-in-Hand 1,000 + 200 − 600 = **600 Dr** · Sundry Creditors **1,000 Cr** · Cash **200 Cr** (overdrawn in this tiny world — see Balance Sheet note) · Sundry Debtors **900 Dr** · Sales **900 Cr** · COGS **600 Dr**.

**Trial Balance (must balance):**

| Account | Debit | Credit |
|---|---|---|
| Stock-in-Hand | 600.00 | |
| Sundry Debtors | 900.00 | |
| Cost of Goods Sold | 600.00 | |
| Sundry Creditors | | 1,000.00 |
| Cash | | 200.00 |
| Sales | | 900.00 |
| **Total** | **2,100.00** | **2,100.00** |

**Profit & Loss Account (Trading section, closing-stock method — derived per §3.3.8):**

| Particulars | Amount |
|---|---|
| Sales | 900 |
| Opening Stock | 0 |
| Add: Purchases | 1,000 |
| Add: Direct Expenses (freight/labour) | 200 |
| Less: Closing Stock | (600) |
| **Cost of Goods Sold** | **600** |
| **Gross Profit** | **300** |

(Cross-check the perpetual route: Sales 900 − COGS ledger 600 = 300. Both derivations must agree to the paisa.)

**Balance Sheet (as at test date).** In this deliberately tiny world the ₹200 cash was paid out of an empty cash box, so Cash is a ₹200 *credit* balance. The arithmetically correct presentation shows it as a negative asset (Tally shows exactly this):

| Liabilities | Amount | Assets | Amount |
|---|---|---|---|
| Sundry Creditors | 1,000 | Sundry Debtors | 900 |
| Profit for the period | 300 | Closing Stock (at cost) | 600 |
| | | Cash-in-Hand | (200) |
| **Total** | **1,300** | **Total** | **1,300** |

Equivalent presentation (also correct, per §3.9 render rule a persistent credit cash/bank balance may present on the liabilities side as a borrowing): Liabilities 1,000 + 300 + 200 = 1,500 = Assets 900 + 600. Both tie; the test asserts the **1,300 = 1,300** totals via the negative-asset presentation, which is the default renderer behaviour for Cash-in-Hand. (The owner's brief contained a draft table with Cash (200) as a *deduction on the liabilities side*, totalling 1,100 against assets of 1,500 — that does not balance and is deliberately not reproduced; the two presentations above are the arithmetically correct options.) In any realistic tenant the opening-balance wizard would have seeded cash/capital first and Cash never goes credit.

**Assertions the automated test must make (in order):**

1. Trial Balance balances: `TrialBalanceService` totals equal (2,100.00 = 2,100.00) **and** raw `SUM(journal_lines.debit) = SUM(journal_lines.credit)` for the business.
2. Closing Stock: ledger 1211 balance = **600.00**, and `StockValuationReport::asOn(date)` total = **600.00** (5 units × 120.000000).
3. COGS ledger balance = **600.00**, and the DLN journal's source is the delivery note — *not* the quotation; assert the value ≠ 5 × (180 − `products.purchase_price`) unless coincidental: explicitly assert COGS was computed from `stock_ledger_entries` (`issued_cost` on the DN item = 120.000000 × 5).
4. Gross Profit per `TradingAndPlService` = **300.00**; and equals Sales − COGS.
5. Unrealised margin ₹300 appears in **no** income or equity account: Σ credits of all `nature IN (income)` accounts = 900.00 exactly; P&L closing balance = 300.00 only.
6. Balance Sheet totals equal (1,300.00 = 1,300.00) and assert Cash renders at −200.00 on the asset side.
7. **Sell the remaining half at ₹900** (DN 5 + invoice 900, same flow): cumulative Gross Profit = **600.00**, Closing Stock = **0.00**, Stock-in-Hand ledger = 0.00, TB still balances (Dr = Cr = 3,000.00: Debtors 1,800 + COGS 1,200 vs Creditors 1,000 + Cash 200 + Sales 1,800).
8. **FIFO variant** (fresh business): two purchase lots — lot A 10 @ ₹100 (+ ₹200 landed → ₹120/unit), lot B 10 @ ₹150 (no landed cost). Sell 12 units @ ₹180 = ₹2,160. FIFO COGS = 10 × 120 + 2 × 150 = **1,500.00**; closing stock = 8 × 150 = **1,200.00**; check 1,200 + 1,500 = 2,700 = total cost available (1,000 + 200 + 1,500 = 2,700 ✓); stock report ties to ledger 1211 = 1,200.00; GP = 2,160 − 1,500 = 660.00. Re-run same trade under weighted average: WA rate after both lots = 2,700 / 20 = 135.000000; COGS = 12 × 135 = **1,620.00**, closing stock = 8 × 135 = **1,080.00**, GP = 540.00; ledger ties again. Assert the two methods differ exactly by 120.00 on COGS and mirror-differ on closing stock.
9. **GST variant** (fresh business, all parties same state, 18%): Purchase: `Dr Stock 1,000 + Dr Input CGST 90 + Dr Input SGST 90 / Cr Creditor 1,180`. Freight (registered GTA-style vendor billed with 18% forward charge for test simplicity): `Dr Stock 200 + Dr Input CGST 18 + Dr Input SGST 18 / Cr Cash 236`. Sale of half: `Dr Debtor 1,062 / Cr Sales 900 + Cr Output CGST 81 + Cr Output SGST 81`. Assert: output tax total = **162.00** (= 900 × 18%); input tax total = **216.00** (= 1,200 × 18%); **P&L is identical to the base case** (Sales 900, COGS 600, GP 300 — GST never touches income or expense); Balance Sheet Duties & Taxes shows net ITC credit of 54.00 as an asset-side net debit (Input 216 Dr vs Output 162 Cr within the 241x group); `gst_ledger_entries` Σ by type equals each 241x ledger movement (§3.6.8); GSTR-3B builder for the month shows 3.1(a) taxable 900 / tax 162 and 4(A)(5) ITC 216.
10. Immutability: attempting `UPDATE journals SET total_debit = 999` on the posted SAL journal raises the trigger SIGNAL; `DELETE` likewise; the invoice model refuses `->update(['grand_total' => 1])` with `DocumentFinalisedException`.
11. Idempotency: calling the DN finalise endpoint twice posts exactly one COGS journal (assert count).
12. `php artisan accounting:verify-integrity --business=<id>` exits 0 with all checks PASS at every step above.

### 5.2 Golden Test 2 — services / project job (no inventory)

**Scenario:** a service business quotes a two-milestone automation project at **₹1,500** (M1 = ₹750, M2 = ₹750). During milestone 1, labour cost **₹400** is paid by bank and capitalised to WIP (the crew worked only on this job). Milestone 1 is completed and invoiced; revenue is recognised on completion (AS-9), and the WIP releases to cost of services proportionally.

| # | Event | Voucher | Debit | Credit | Amount |
|---|---|---|---|---|---|
| 1 | Labour paid, tagged to project P, capitalise-to-WIP | PAY | WIP (Services) 1212 | Bank | 400 |
| 2 | Milestone-1 invoice ₹750 finalised | SAL | Sundry Debtors | Sales — Services | 750 |
| 3 | WIP release on milestone billing: 400 × (750 / 1,500) = 200 | JRN (auto with #2) | Cost of Services (5104) | WIP (Services) | 200 |

**Assertions:**

1. TB balances: Dr(Debtors 750 + CoS 200 + WIP 200) = 1,150 = Cr(Bank 400 + Sales 750) — Bank shows 400 Cr in this tiny world (same negative-asset presentation note as §5.1).
2. Project P&L (§3.9.13) for project P: revenue 750, cost 200, margin **550**; quoted-margin panel shows quoted M1 margin = 750 − 200 (est. cost snapshot 400 × 50%) and variance 0 in the base case; WIP on BS = **200** (unbilled effort is an asset, not an expense).
3. No revenue exists before the milestone invoice: after step 1 alone, income accounts total 0.00 and the ₹400 sits entirely on the Balance Sheet.
4. Complete milestone 2 with a further ₹300 labour → WIP 300 + release on M2 invoice of remaining WIP (200 + 300 − released… release = full remaining 500): cumulative revenue 1,500, cumulative cost 700, project margin 800, WIP = 0.00, TB balanced.
5. GST-on-advance leg: customer pays ₹590 advance before M1 invoice (services, 18%): `Dr Bank 590 / Cr Advances from Customers 500 + Cr Output CGST 45 + Cr Output SGST 45` (590 × 18/118 = 90). On M1 invoice allocation, the 90 reverses against the invoice's output tax and the 500 nets the debtor. Assert Advances = 0, output tax net for the period = tax on 750 only, and the AT/TXPD buckets appear in GSTR-1 for the two periods.
6. `accounting:verify-integrity` passes at every step.

### 5.3 Where these tests live in CI-less reality

There is no CI in this repo (§1.1). Until one exists, the definition of done for M2/M3 includes pasting the local output of `php artisan test --configuration=phpunit.addons.xml --filter=Golden` into the PR/commit description (§0 rule 3), and the integrity command is scheduled nightly (§3.11.4) as the production backstop.

---
## 6. Implementation backlog

87 tasks in 8 milestones. Work strictly in ID order unless a task's **Parallel** note says otherwise. Every milestone ends with a **Definition of Done** including an integrity gate. Sizes: S ≤ ½ day, M ≈ 1 day, L ≈ 2 days, XL = split it if it grows. Each task fits one Claude Code session. "FormRequest", "permission migration", "screen" mean: follow the §1.6 repo conventions exactly. Every task that touches money states its rounding boundary; "MB-2dp" = the §3.1.6 ledger boundary (round half-up to 2 dp at posting, never re-round).

### Milestone M0 — Data integrity (ship independently: today's documents get honest)

Parallel: TASK-002/004/005/008/009 are independent of 001/003 and of each other.

### TASK-001 — Money value object (BCMath)

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** every service does float arithmetic on money (§1.3); ledgers built on floats drift by paise and never tie.
- **Files to create:** `app/Support/Money/Money.php`, `tests/Unit/MoneyTest.php`
- **Files to modify:** `composer.json` (add `"ext-bcmath": "*"` to `require`)
- **Schema changes:** none
- **Implementation notes:** immutable final class; internal string at scale 6; constructors `Money::of(string|int $amount)`, `Money::zero()`; ops `plus`, `minus`, `times(string $factor)`, `dividedBy(string $divisor, int $scale = 6)`, `allocate(array $ratios): array` (largest-remainder at 2 dp — parts sum exactly), `roundTo(int $scale)` half-up via `bcadd` with 5-at-next-digit trick, comparisons, `isNegative()`, `toDecimalString(int $scale = 2)`, `toStorage()` (scale 4). Reject float inputs (`TypeError`). No currency field — INR-only app (`businesses.currency_code` is display).
- **Acceptance criteria:** 1) `Money::of('0.1')->plus(Money::of('0.2'))->toDecimalString()` === `'0.30'`; 2) `allocate` of ₹200.00 across values [1000] → ['200.00'] and across [3,3,3] paise-exact (66.67+66.67+66.66 = 200.00); 3) half-up: `'2.345'`→`'2.35'`, `'2.344'`→`'2.34'`.
- **Test to write:** `tests/Unit/MoneyTest.php::test_allocate_largest_remainder_sums_exactly` (+ 12 more cases)
- **Rollback:** delete class; nothing depends yet
- **Estimated size:** M

### TASK-002 — Kill the `percent`/`percentage` discount bug

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** §1.5.2 — a % discount on an SO/PO silently applies as flat rupees; stored grand totals are wrong today.
- **Files to create:** `tests/Feature/SalesOrderDiscountTest.php`, `tests/Feature/PurchaseOrderDiscountTest.php`
- **Files to modify:** `app/Services/SalesOrderService.php:237`, `app/Services/PurchaseOrderService.php:222` (accept both `percent` and `percentage`, matching `QuotationService.php:146`)
- **Schema changes:** none
- **Implementation notes:** do NOT recompute stored documents here (TASK-003 handles drafts); this stops the bleeding. Rounding boundary: unchanged (existing `round(,2)`).
- **Acceptance criteria:** 1) SO with `discount_type='percent'`, value 10, subtotal 1000 → discount 100 not 10; 2) existing tests stay green.
- **Test to write:** `SalesOrderDiscountTest::test_percent_discount_applies_as_percentage`
- **Rollback:** revert the two-line change
- **Estimated size:** S

### TASK-003 — One totalling algorithm: `DocumentTotalsService`

- [ ] Status: not started
- **Depends on:** TASK-001
- **Why:** §1.5.1 — quotations double-tax and invoices disagree with the SO the customer accepted.
- **Files to create:** `app/Services/Accounting/DocumentTotalsService.php`, `tests/Unit/DocumentTotalsServiceTest.php`, migration `add_legacy_totals_flags_to_documents` + `recompute_draft_document_totals`
- **Files to modify:** `app/Services/{QuotationService,SalesOrderService,PurchaseOrderService,ProformaInvoiceService,InvoiceService}.php` (delete private `normalizeItems`/`calculateTotals`, delegate), `app/Http/Controllers/Admin/QuotationController.php` (pdf method — see TASK-004 overlap note: this task changes stored values, 004 changes the PDF)
- **Schema changes:** `legacy_totals` bool default false on quotations, proforma_invoices, sales_orders, purchase_orders, invoices
- **Implementation notes:** algorithm exactly §3.4.2 (Money end-to-end; taxable_value rounding boundary = per line 2 dp; header discount allocated by `Money::allocate` proportional to net line value). `line_total` now means taxable (tax-exclusive) everywhere. Data migration recomputes **drafts only** (`status='draft'` quotations/POs; `pending` SOs; `unpaid AND amount_paid=0 AND created within accounting-disabled world` invoices are left alone — invoices are never recomputed); everything non-draft gets `legacy_totals=1`. Blade line-total labels unchanged (column meaning is documented in the form help text).
- **Acceptance criteria:** 1) same items produce identical totals on all 5 document types; 2) quotation with line-tax 18% + header tax 0 no longer adds tax twice — 100 qty1 rate100 disc0: line_total 100.00, tax 18.00, grand 118.00; 3) SO→invoice conversion preserves grand_total to the paisa; 4) drafts recomputed, sent/accepted untouched with flag set.
- **Test to write:** `DocumentTotalsServiceTest::test_all_document_types_agree` + `test_header_discount_allocation_is_paise_exact`
- **Rollback:** restore service methods from git; migration down drops flags (recompute is one-way — acceptable on drafts)
- **Estimated size:** L

### TASK-004 — Quotation PDF prints stored totals

- [ ] Status: not started
- **Depends on:** TASK-003
- **Why:** §1.5.3 — the printed document must equal the stored document.
- **Files to create:** none
- **Files to modify:** `app/Http/Controllers/Admin/QuotationController.php:141-175` (drop `$pdfSubtotal…` recompute; pass model fields), `resources/views/admin/quotations/pdf.blade.php` (bind to stored columns)
- **Schema changes:** none
- **Acceptance criteria:** PDF grand total string equals `number_format($quotation->grand_total, 2)` for a fixture with line discount + line tax + header discount.
- **Test to write:** `tests/Feature/QuotationPdfTest.php::test_pdf_uses_stored_totals`
- **Rollback:** git revert
- **Estimated size:** S

### TASK-005 — Interim numbering race fix

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** §1.3 — two concurrent creates 500 on the unique index; real FY sequences arrive in M1, but creates must stop failing now.
- **Files to create:** `app/Support/RetryOnDuplicate.php` (helper: run closure, catch `UniqueConstraintViolationException`, regenerate number, retry ≤ 3)
- **Files to modify:** `app/Services/{QuotationService,InvoiceService,PaymentService,SalesOrderService,PurchaseOrderService,ExpenseService}.php` create paths
- **Schema changes:** none
- **Acceptance criteria:** 8 parallel quotation creates (addons suite) all succeed with 8 distinct numbers.
- **Test to write:** `tests/Feature/Addons/NumberingRaceTest.php::test_parallel_creates_do_not_collide`
- **Rollback:** remove helper usage
- **Estimated size:** S

### TASK-006 — Statutory `audit_trail`

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** Rule 3(1) Companies (Accounts) Rules 2014 (§3.1.10) — the current activity log is bypassable and truncatable.
- **Files to create:** migration `create_audit_trail_table` (§4.1 shape + §4.2 two triggers + `PARTITION BY RANGE (YEAR(occurred_at))` with yearly partitions 2026-2036 + MAXVALUE; sqlite guard skips triggers/partitions), `app/Support/Accounting/AuditTrail.php`, `app/Observers/FinancialAuditObserver.php`, `tests/Feature/Addons/AuditTrailTest.php`
- **Files to modify:** `app/Providers/AppServiceProvider.php` (register observer for Quotation, SalesOrder, Invoice, InvoiceItem, Payment, PurchaseOrder, GoodsReceipt, Expense, Payslip — accounting models self-register later)
- **Schema changes:** new table (no FK on business_id — survives tenant deletion)
- **Implementation notes:** `AuditTrail::record(string $action, Model $subject, array $old = [], array $new = [])` resolves guard/user from `Auth`, IP/UA from request when present, `occurred_at = now()`; called inside the caller's transaction. Do not log reads.
- **Acceptance criteria:** 1) editing an invoice writes a row with old/new diffs; 2) `UPDATE audit_trail SET action='x'` raises SIGNAL (addons); 3) `DELETE` likewise; 4) row written even when activitylog is disabled via config.
- **Test to write:** `AuditTrailTest::test_trail_is_append_only`
- **Rollback:** drop table + observer (pre-accounting only; after M1 forbidden by §0 rule 8)
- **Estimated size:** M

### TASK-007 — Audit-trail retention command

- [ ] Status: not started
- **Depends on:** TASK-006
- **Why:** 8-year retention (Sec 128(5) Companies Act, Sec 36 CGST) with a refusal guard beats an unbounded table nobody dares touch.
- **Files to create:** `app/Console/Commands/Accounting/PruneAuditTrail.php`, `tests/Feature/PruneAuditTrailTest.php`
- **Files to modify:** `routes/console.php` (no schedule — manual only, by design)
- **Schema changes:** none
- **Implementation notes:** `accounting:prune-audit-trail {--before=}`: refuse if `--before` > (first day of FY 8 full FYs ago); logs its own execution into the trail **before** dropping the partition; drops whole-year partitions only.
- **Acceptance criteria:** `--before=2031-04-01` run in 2026 refuses with exit 1 and explanatory message.
- **Test to write:** `PruneAuditTrailTest::test_refuses_recent_cutoff`
- **Rollback:** delete command
- **Estimated size:** S

### TASK-008 — Stop deletion of paid money documents (interim guard)

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** §1.5.7 — today a paid invoice or a payment can be soft-deleted leaving no financial trace; the full cancel-with-reversal flow lands in M3, but the hole closes now.
- **Files to create:** `tests/Feature/DocumentDeletionGuardTest.php`
- **Files to modify:** `app/Services/InvoiceService.php::delete` (throw `RuntimeException` when `payments()->exists()` or status `paid/partial`), `app/Services/PaymentService.php::delete` (require a `reason` param, write `AuditTrail::record('deleted', …, ['reason'=>…])`; controller collects reason via SweetAlert prompt), `app/Http/Controllers/Admin/{InvoiceController,PaymentController}.php`
- **Schema changes:** none
- **Acceptance criteria:** 1) deleting a partially-paid invoice → 422 flash with reason; 2) payment deletion without reason → validation error; with reason → audit row.
- **Test to write:** `DocumentDeletionGuardTest::test_paid_invoice_cannot_be_deleted`
- **Rollback:** git revert
- **Estimated size:** S

### TASK-009 — Backup hardening + restore drill

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** books you cannot restore are books you do not have (§3.11.5).
- **Files to create:** `docs/restore-drill.md` (step-by-step: pull latest spatie zip, restore to scratch DB, run `accounting:verify-integrity`, checklist table)
- **Files to modify:** `config/backup.php` (weekly full incl. `storage/app/businesses`), `routes/console.php` (`backup:run` weekly Sun 02:30 full; keep nightly `--only-db`), notifications → `MAIL_FROM_ADDRESS`
- **Schema changes:** none
- **Acceptance criteria:** `php artisan backup:run` produces a zip containing both dump and attachment dirs (assert in test via local disk).
- **Test to write:** `tests/Feature/BackupConfigTest.php::test_full_backup_includes_attachments`
- **Rollback:** config revert
- **Estimated size:** S

### TASK-010 — Per-business accounting flags

- [ ] Status: not started
- **Depends on:** nothing
- **Why:** §0 rule 11 — everything ships dark behind `accounting_enabled` + `books_start_date`.
- **Files to create:** migration `add_accounting_flags_to_businesses` (full §4.10.1 list), `tests/Feature/BusinessAccountingFlagsTest.php`
- **Files to modify:** `app/Models/Business.php` (fillable + casts incl. `encrypted` for the two credentials), `resources/views/admin/businesses/{edit,show}.blade.php` (new "Accounting" card, Super Admin only), `app/Http/Controllers/Admin/BusinessController.php`, `app/Http/Requests/Admin/UpdateBusinessRequest.php`
- **Schema changes:** §4.10.1
- **Implementation notes:** `books_start_date` immutable once any journal exists (guard in controller). Defaults exactly as §4.10.1.
- **Acceptance criteria:** flags persist; `books_start_date` locked after first posting (test stub uses a fake journal in M1 — here assert the guard method).
- **Test to write:** `BusinessAccountingFlagsTest::test_flags_default_off`
- **Rollback:** migration down
- **Estimated size:** S

**M0 Definition of Done:** all 10 tasks ticked; `php artisan test` + addons suite green; **integrity gate:** `NumberingRaceTest`, `AuditTrailTest` (trigger SIGNALs), `DocumentDeletionGuardTest` pass on MySQL; a manual smoke: create quotation → SO → invoice with line tax + header % discount and confirm all three totals agree.

### Milestone M1 — Ledger core (CoA, journals, TB)

Parallel: TASK-011+012 first (in order); then 013/015 parallel; 014 after 013; 016 after 013+015; 017-020 after 016.

### TASK-011 — Fiscal years, periods, voucher types, sequences

- [ ] Status: not started
- **Depends on:** TASK-010
- **Why:** Indian FY (Apr–Mar) and gapless per-FY numbering are the spine of statutory books (§3.1.8).
- **Files to create:** migrations `create_fiscal_years_and_periods`, `create_voucher_types_and_sequences` (+ 13-row voucher_types seed in-migration), models `app/Models/Accounting/{FiscalYear,AccountingPeriod,VoucherType,VoucherSequence}.php`, `tests/Feature/Accounting/FiscalYearTest.php`
- **Files to modify:** none
- **Schema changes:** §4.1 four tables
- **Implementation notes:** `FiscalYear::forDate($businessId, $date)` creates FY + 12 periods idempotently (`firstOrCreate` on unique key); label `'2026-27'`; FY spans Apr 1–Mar 31 computed from the date's month (Jan–Mar belongs to prior April's FY).
- **Acceptance criteria:** date 2027-02-15 → FY '2026-27'; 12 periods seeded; double-call creates nothing new.
- **Test to write:** `FiscalYearTest::test_for_date_maps_jan_to_prior_fy`
- **Rollback:** migrations down (pre-data only)
- **Estimated size:** M

### TASK-012 — Chart of Accounts + seeder + cost centres + projects

- [ ] Status: not started
- **Depends on:** TASK-011
- **Why:** §3.1.1-3.1.3 — every posting needs a CA-recognisable account tree.
- **Files to create:** migrations `create_ledger_accounts`, `create_cost_centres`, `create_projects`; models (`LedgerAccount` with tree helpers `isDescendantOf`, `pathName()`; `CostCentre`; `Project`); `database/seeders/ChartOfAccountsSeeder.php` (28 groups + all §3.1.3 leaves, exact codes/flags from the tables); `tests/Feature/Accounting/ChartOfAccountsTest.php`
- **Files to modify:** `app/Services/BusinessService.php` (seed CoA on business create), one-off command `app/Console/Commands/Accounting/SeedCoaForExistingBusinesses.php`
- **Schema changes:** §4.1 three tables
- **Implementation notes:** seeder idempotent per business (firstOrCreate on business+code). `is_system` rows: block delete/rename via model guard (display alias editable later via tally_alias only).
- **Acceptance criteria:** new business → 28 groups + ≥ 40 system leaves; deleting `Stock-in-Hand` throws; re-seed adds nothing.
- **Test to write:** `ChartOfAccountsTest::test_seed_is_idempotent_and_complete`
- **Rollback:** migrations down
- **Estimated size:** L

### TASK-013 — Journals & lines & balances tables + models

- [ ] Status: not started
- **Depends on:** TASK-012
- **Why:** §3.1.5 — the immutable heart.
- **Files to create:** migrations `create_journals`, `create_journal_lines`, `create_account_balances` (+ CHECK constraints via `DB::statement`, sqlite-guarded), models `Journal`, `JournalLine`, `AccountBalance` (+ `ImmutableJournalException` model guards per §3.1), `tests/Feature/Accounting/JournalModelTest.php`
- **Files to modify:** none
- **Schema changes:** §4.1 three tables + CHECKs
- **Acceptance criteria:** model-level: posted journal `->update()` throws; `->delete()` throws; sqlite CHECK skip verified (suite green on both drivers).
- **Test to write:** `JournalModelTest::test_posted_journal_is_immutable_at_model_layer`
- **Rollback:** migrations down
- **Estimated size:** M

### TASK-014 — DB triggers (MySQL)

- [ ] Status: not started
- **Depends on:** TASK-013, TASK-006
- **Why:** §3.1.5 layer 1 — the database itself must refuse an unbalanced or mutated posted voucher, even from a buggy code path or a raw query.
- **Files to create:** migration `create_accounting_triggers` (§4.2 verbatim, `if (DB::getDriverName() !== 'mysql') return;`), `tests/Feature/Addons/JournalTriggerTest.php`
- **Files to modify:** none
- **Schema changes:** 6 triggers
- **Acceptance criteria:** raw `DB::statement` attempts to (a) post an unbalanced journal, (b) update a posted header, (c) delete posted lines — each raises SQLSTATE 45000 (addons suite).
- **Test to write:** `JournalTriggerTest::test_triggers_block_all_three_attacks`
- **Rollback:** drop triggers
- **Estimated size:** M

### TASK-015 — VoucherNumberService + PeriodGuard

- [ ] Status: not started
- **Depends on:** TASK-011
- **Why:** §3.1.8 — gapless concurrency-safe numbers; locked periods must reject writes.
- **Files to create:** `app/Services/Accounting/Posting/{VoucherNumberService,PeriodGuard}.php`, exceptions `App\Exceptions\Accounting\{PeriodLockedException,BeforeBooksStartException}`, `tests/Feature/Addons/VoucherNumberConcurrencyTest.php`, `tests/Feature/Accounting/PeriodGuardTest.php`
- **Files to modify:** none
- **Schema changes:** none
- **Implementation notes:** `next()` MUST be called inside an open transaction (assert `DB::transactionLevel() > 0`); `SELECT … FOR UPDATE` on the sequence row, `firstOrCreate` outside-then-lock pattern for the lazy seed; format `{prefix}{FYshort}/{number padded}`.
- **Acceptance criteria:** 32 parallel posts → numbers 1..32 exactly once (addons); locked month rejects with named exception; date < books_start_date rejects.
- **Test to write:** `VoucherNumberConcurrencyTest::test_32_workers_get_consecutive_numbers`
- **Rollback:** delete classes
- **Estimated size:** M

### TASK-016 — JournalPostingService + balances + integrity v1

- [ ] Status: not started
- **Depends on:** TASK-013, TASK-014, TASK-015
- **Why:** §3.1.6 — the single write path everything else calls.
- **Files to create:** `app/Services/Accounting/Posting/{JournalPostingService,JournalDraft}.php`, `app/Services/Accounting/AccountBalanceService.php`, `app/Console/Commands/Accounting/{VerifyIntegrity,RebuildBalances}.php`, `tests/Feature/Accounting/JournalPostingServiceTest.php`, `tests/Feature/Addons/PostingConcurrencyTest.php`
- **Files to modify:** `routes/console.php` (schedule `accounting:verify-integrity` daily 03:00)
- **Schema changes:** none
- **Implementation notes:** pipeline exactly §3.1.6 incl. idempotency SELECT FOR UPDATE and `DB::transaction(attempts: 3)`; `AccountBalanceService::apply` uses `INSERT … ON DUPLICATE KEY UPDATE period_debit = period_debit + VALUES(period_debit)` style upsert per (account, period). Rounding boundary: MB-2dp asserted (`Money->toStorage()` of a 2 dp-rounded value). Integrity v1 = checks 1,2,3,7,8 of §3.11.4 with `--business=` and table output.
- **Acceptance criteria:** 1) draft→posted writes header+lines+balances+audit atomically; 2) same idempotency key twice → same journal id, one row; 3) reverse() mirrors lines and links both ids; 4) kill-switch test: force an exception after lines insert — nothing persists; 5) integrity v1 all-PASS on seeded data and FAILs when a balance row is manually corrupted (then `--fix-balances` repairs).
- **Test to write:** `JournalPostingServiceTest::test_post_is_atomic_idempotent_and_balanced`
- **Rollback:** delete classes/commands (no data)
- **Estimated size:** L

### TASK-017 — Party sub-ledgers

- [ ] Status: not started
- **Depends on:** TASK-016
- **Why:** §3.1.4 — receivables/payables need per-party ledgers that always tie to the control groups.
- **Files to create:** `app/Services/Accounting/ChartOfAccountsService.php` (with `ensurePartyLedger`), migration `add_ledger_account_id_to_customers_and_vendors`, `tests/Feature/Accounting/PartyLedgerTest.php`
- **Files to modify:** `app/Models/{Customer,Vendor}.php` (relation + observer to sync name)
- **Schema changes:** §4.10.4 `ledger_account_id` columns only
- **Acceptance criteria:** first call creates leaf under 1220/2420 with code `1220-0007` style; second call returns same; rename syncs name, keeps `tally_alias`.
- **Test to write:** `PartyLedgerTest::test_ensure_is_idempotent`
- **Rollback:** migration down
- **Estimated size:** S

### TASK-018 — Manual Journal UI + CoA screen + M1 permissions

- [ ] Status: not started
- **Depends on:** TASK-016, TASK-017
- **Why:** the CA's daily tool; also the first UI proof of the core.
- **Files to create:** `app/Http/Controllers/Admin/Accounting/{ChartOfAccountsController,JournalController}.php`, FormRequests `StoreJournalRequest`, views `resources/views/admin/accounting/{coa/index,journals/index,journals/create,journals/show}.blade.php`, migration `add_accounting_core_permissions` (`accounting.view`, `accounting.manage_coa`, `accounting.lock_period`, `accounting.unlock_period`, `vouchers.view/create/post/reverse`, `vouchers.post_to_controlled`, `statements.view/export`), `tests/Feature/Accounting/JournalUiTest.php`
- **Files to modify:** `routes/web.php` (accounting prefix block), `resources/views/components/admin/sidebar.blade.php` (Accounting section, permission-gated), `database/seeders/RolePermissionSeeder.php` (modules map + Accounts role grants)
- **Schema changes:** permissions rows only
- **Implementation notes:** journal create = Alpine line-grid (account search select2-style via existing `searchable-select` component, Dr/Cr columns, live Money-formatted imbalance banner); maker-checker: `vouchers.create` saves draft, `vouchers.post` posts, `posted_by != created_by` enforced when `maker_checker_journals` (§3.11.3); controlled-ledger block per §3.1 edge cases.
- **Acceptance criteria:** draft→post→show renders voucher with number; imbalance cannot submit; maker cannot post own draft (when flag on); Viewer role sees nothing.
- **Test to write:** `JournalUiTest::test_maker_checker_enforced`
- **Rollback:** remove routes/screens
- **Estimated size:** L

### TASK-019 — Day Book, Ledger statement, Trial Balance v1

- [ ] Status: not started
- **Depends on:** TASK-018
- **Why:** first read surface; the TB is the tie-out artefact everything else quotes.
- **Files to create:** `app/Services/Accounting/Statements/{DayBookService,LedgerStatementService,TrialBalanceService}.php`, controller `StatementController`, views `admin/accounting/statements/{day-book,ledger,trial-balance}.blade.php`, exports `app/Exports/Accounting/TrialBalanceExport.php`, pdf views `admin/accounting/pdf/{trial-balance,ledger}.blade.php`, `tests/Feature/Accounting/TrialBalanceTest.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** none
- **Implementation notes:** TB reads `account_balances` (opening = Σ periods < from), drill panel lazy-loads lines; red refuse-export banner on imbalance (§3.9.3). FY default range helper shared (`App\Support\Accounting\FyRange`).
- **Acceptance criteria:** golden-style seed (3 manual journals) → TB balances, ledger running balance correct, Day Book groups by type; PDF + Excel export.
- **Test to write:** `TrialBalanceTest::test_totals_equal_and_opening_rolls`
- **Rollback:** remove screens
- **Estimated size:** L

### TASK-020 — Period locking UI + tenancy isolation suite

- [ ] Status: not started
- **Depends on:** TASK-018
- **Why:** §3.1.8 locking; §3.11.1 isolation proof.
- **Files to create:** controller `PeriodController` + view `admin/accounting/periods/index.blade.php`, `tests/Feature/Accounting/{PeriodLockTest,TenancyIsolationTest}.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** none
- **Acceptance criteria:** lock month → posting rejected; unlock demands reason, writes `period_unlocked` audit row; TenancyIsolationTest per §3.11.1 passes.
- **Test to write:** `TenancyIsolationTest::test_two_businesses_never_bleed`
- **Rollback:** remove screen
- **Estimated size:** M

**M1 Definition of Done:** manual journals post/reverse through the full pipeline; TB balances and exports; `accounting:verify-integrity` v1 all-PASS on a two-business seeded DB **and** the trigger suite passes on MySQL; existing 79 tests still green; sidebar shows Accounting only with permission + `accounting_enabled`.

### Milestone M2 — Inventory & costing (golden test passes)

Parallel: TASK-021/022/023 in order; then 024+026 parallel with 025; 027 after 025; 029/030/031 parallel after 025; 032 after 027; 033-035 last.

### TASK-021 — UoMs + item master upgrade

- [ ] Status: not started
- **Depends on:** TASK-012
- **Why:** §3.3.1 — costing needs item types, base units, and per-item valuation method.
- **Files to create:** migrations `create_uoms` (+22-row seed per business incl. UQC codes) and `alter_products_for_accounting` (§4.10.3), `tests/Feature/Accounting/ItemMasterTest.php`
- **Files to modify:** `app/Models/Product.php` (fillable/casts/relations), `resources/views/admin/products/{create,edit}.blade.php`, `app/Http/Requests/Admin/{StoreProductRequest,UpdateProductRequest}.php` (HSN 4/6/8-digit rule, alt-unit factor > 0), `app/Services/ProductService.php` (valuation-method change guard §3.3.3; serial tracking stored but engines defer — note in code comment)
- **Schema changes:** §4.10.3 + uoms
- **Acceptance criteria:** product saved with KG base + BOX alt ×24; HSN '12345' rejected; method change with stock on hand rejected.
- **Test to write:** `ItemMasterTest::test_valuation_method_locked_while_stock_exists`
- **Rollback:** migrations down
- **Estimated size:** M

### TASK-022 — Stock tables

- [ ] Status: not started
- **Depends on:** TASK-021
- **Why:** §3.3.2 — the costed, append-only stock ledger replacing quantity-only movements.
- **Files to create:** migrations `create_stock_balances`, `create_stock_ledger_entries` (+CHECK), `create_stock_lots`, models `app/Models/Accounting/{StockBalance,StockLedgerEntry,StockLot}.php`
- **Files to modify:** none (old `stock_movements` untouched, frozen in TASK-028)
- **Schema changes:** §4.3 three tables
- **Acceptance criteria:** models save; append-only enforced at model layer (no update/delete on SLE).
- **Test to write:** `tests/Feature/Accounting/StockLedgerEntryTest.php::test_entries_are_append_only`
- **Rollback:** migrations down
- **Estimated size:** S

### TASK-023 — Costing engines (pure)

- [ ] Status: not started
- **Depends on:** TASK-001, TASK-022
- **Why:** §3.3.3 — the arithmetic heart of "remaining chi original value".
- **Files to create:** `app/Services/Accounting/Costing/{CostingEngine.php (interface), WeightedAverageEngine.php, FifoEngine.php, StandardCostEngine.php}`, `tests/Unit/CostingEnginesTest.php`
- **Files to modify:** none
- **Schema changes:** none
- **Implementation notes:** engines are pure given (balances, lots, qty) — DB access stays in StockPostingService; WA rate scale 6; issue value single-round 2 dp (§3.3.3 boundary); FIFO consumes by (received_on, id).
- **Acceptance criteria:** §5.1 assertion-8 numbers reproduced in unit tests (WA 135 → 1,620/1,080; FIFO 1,500/1,200).
- **Test to write:** `CostingEnginesTest::test_golden_fifo_vs_wa_numbers`
- **Rollback:** delete classes
- **Estimated size:** M

### TASK-024 — Costed goods receipt (RCN posting)

- [ ] Status: not started
- **Depends on:** TASK-016, TASK-022, TASK-023
- **Why:** §3.5.1-3.5.2 — stock gets value the moment it lands; GRN Clearing starts its life.
- **Files to create:** `app/Services/Accounting/Posting/GrnPostingService.php`, `app/Services/Accounting/Costing/StockPostingService.php` (receive path), migration `alter_goods_receipts_for_accounting` (§4.10.8), `tests/Feature/Accounting/GrnPostingTest.php`
- **Files to modify:** `app/Services/PurchaseOrderService.php::receiveGoods` (rate snapshot, per-line warehouse required, idempotency key, call posting when `accounting_enabled`), `resources/views/admin/purchase-orders/*receive*` blade, `StoreGoodsReceiptRequest`
- **Schema changes:** §4.10.8
- **Implementation notes:** lock order §3.1.6: sequence → `stock_balances` rows (`lockForUpdate`, ordered by id) → inserts. Journal: `Dr Stock-in-Hand / Cr GRN Clearing` Σ round(qty × PO rate, 2) per line (line values 2 dp; MB-2dp). WA/lot update per engine. Over-receipt beyond tolerance rejected.
- **Acceptance criteria:** GRN 10 @ 100 → SLE in 10 @ 100 = 1000, balance qty/value updated, journal posted, double-submit no-op; `stock_movements` also written (legacy readers) until TASK-028.
- **Test to write:** `GrnPostingTest::test_receipt_posts_and_costs_atomically`
- **Rollback:** revert receiveGoods wiring (flag-gated anyway)
- **Estimated size:** L

### TASK-025 — Stock issue engine + negative-stock policy

- [ ] Status: not started
- **Depends on:** TASK-024
- **Why:** §3.3.4 — issues must cost correctly under concurrency.
- **Files to create:** issue path in `StockPostingService` (+ `InsufficientStockException`), `tests/Feature/Addons/StockIssueConcurrencyTest.php`
- **Files to modify:** none
- **Schema changes:** none
- **Acceptance criteria:** two parallel issues of 6 from a stock of 10 → one succeeds, one throws (blocked policy); with `allow_negative_stock` both succeed and balance goes −2 with integrity warning listed.
- **Test to write:** `StockIssueConcurrencyTest::test_no_double_spend_of_stock`
- **Rollback:** n/a (service internal)
- **Estimated size:** M

### TASK-026 — Landed cost vouchers (the ₹200)

- [ ] Status: not started
- **Depends on:** TASK-024
- **Why:** §3.3.5 / AS-2 — freight capitalises into stock, not P&L.
- **Files to create:** migrations `create_landed_cost_tables`, `app/Services/Accounting/Costing/LandedCostService.php`, controller `LandedCostController`, views `admin/accounting/landed-costs/*`, FormRequest, permission rows (`inventory_valuation.*` set), `tests/Feature/Accounting/LandedCostTest.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** §4.3 landed_cost_vouchers/lines
- **Implementation notes:** apportion by chosen basis via `Money::allocate` (boundary: per-GRN-line 2 dp shares summing exactly); already-sold fraction → `Dr COGS / Cr Stock-in-Hand` corrective lines in the same journal (`line_role=landed_cost`); vendor vs cash credit path; RCM/GST flags stored now, register rows from TASK-049.
- **Acceptance criteria:** ₹200 on a 1,000 GRN (all on hand) → stock value 1,200, WA 120; same voucher when 50% already sold → stock +100, COGS +100.
- **Test to write:** `LandedCostTest::test_capitalises_and_corrects_sold_portion`
- **Rollback:** cancel voucher = reversal; migrations down pre-data
- **Estimated size:** L

### TASK-027 — Delivery Notes + COGS posting

- [ ] Status: not started
- **Depends on:** TASK-025
- **Why:** §3.3.4/§3.4.4 — profit becomes real only when goods move; this is the entry the owner's question 4 hinges on.
- **Files to create:** migrations `create_delivery_notes_tables` (+pivot), `app/Services/DeliveryNoteService.php`, `app/Services/Accounting/Posting/CogsPostingService.php`, controller `DeliveryNoteController`, views `admin/accounting/delivery-notes/*`, permissions `delivery_notes.*`, `tests/Feature/Accounting/CogsOnDeliveryTest.php`
- **Files to modify:** routes, sidebar, `app/Models/SalesOrder.php`/`SalesOrderItem.php` (relations, counters), migration `alter_sales_order_items_counters` (§4.10.7)
- **Schema changes:** §4.4 delivery notes + §4.10.7
- **Implementation notes:** finalise: PeriodGuard → per line `StockPostingService::issue` (freeze `issued_cost`) → single DLN journal Dr 5101 / Cr 1211 (dims per line; MB-2dp) → `delivered_qty +=` under row lock; idempotency `delivery_notes:{id}:finalise`; cancel = reversal + re-receipt at `issued_cost`.
- **Acceptance criteria:** the prompt's own example: DN for 50% of a 1,200-cost lot posts Dr COGS 600 / Cr Inventory 600; re-finalise no-op; TB balanced after.
- **Test to write:** `CogsOnDeliveryTest::test_partial_dispatch_posts_proportional_cogs`
- **Rollback:** reverse journal via `JournalPostingService::reverse`; no schema undo
- **Estimated size:** L

### TASK-028 — Retire quantity-only stock writes

- [ ] Status: not started
- **Depends on:** TASK-024, TASK-027
- **Why:** §1.5.5 — SO-confirm stock-outs double-count once DNs exist.
- **Files to create:** migration `freeze_stock_movements` (nothing structural — adds table comment), `tests/Feature/Accounting/ReservationTest.php`
- **Files to modify:** `app/Services/SalesOrderService.php::updateStatus` (replace movement writes with `reserved_qty`), `app/Services/InventoryService.php` (`getProductStock` reads `stock_balances` when accounting enabled; adjust writes route to StockPostingService::adjust), `app/Http/Controllers/Admin/InventoryController.php` + views (available = on-hand − reserved)
- **Schema changes:** none beyond §4.10.7 (done in 027)
- **Acceptance criteria:** SO confirm changes no stock ledger rows; availability math on product page correct; legacy screens still render history.
- **Test to write:** `ReservationTest::test_confirm_reserves_without_movement`
- **Rollback:** git revert
- **Estimated size:** M

### TASK-029 — Stock transfers

- [ ] Status: not started
- **Depends on:** TASK-025
- **Why:** §3.3.9 godowns.
- **Files to create:** migrations + `StockTransferController` + service methods + views + `tests/Feature/Accounting/StockTransferTest.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** §4.3 transfers tables
- **Acceptance criteria:** transfer 5 units moves qty+value between warehouse balances, total business value unchanged, no P&L line.
- **Test to write:** `StockTransferTest::test_value_conserved`
- **Rollback:** cancel = paired reversal entries
- **Estimated size:** M

### TASK-030 — Stock takes + NRV

- [ ] Status: not started
- **Depends on:** TASK-025
- **Why:** §3.3.6/§3.3.9 — physical truth beats book truth; AS-2 lower-of-cost-or-NRV.
- **Files to create:** migrations, `StockTakeController`, `NrvAssessmentService`, views (count sheet, variance review, NRV working), `tests/Feature/Accounting/StockTakeTest.php`
- **Files to modify:** routes, sidebar, permissions (`stock_takes.count/approve`)
- **Schema changes:** §4.3 stock_takes/lines
- **Acceptance criteria:** counted 9 vs book 10 → variance posts Dr Stock Adjustment 1 × cost / Cr Stock-in-Hand; NRV writedown posts and stores working paper; approve ≠ counter (maker-checker).
- **Test to write:** `StockTakeTest::test_variance_and_nrv_post_correctly`
- **Rollback:** reversal journal
- **Estimated size:** L

### TASK-031 — BOM & manufacture (Stock Journal)

- [ ] Status: not started
- **Depends on:** TASK-025
- **Why:** §3.3.7 — RM → FG with conversion cost per AS-2 ¶8.
- **Files to create:** migrations (boms, stock_journals), `BomController`, STJ posting in `StockPostingService::manufacture`, views, `tests/Feature/Accounting/ManufactureTest.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** §4.3 four tables
- **Acceptance criteria:** consume 2×RM(₹120) + conversion ₹60 → FG unit cost 300; Stock-in-Hand net change = +60 (conversion), credit to the conversion ledger; TB balanced.
- **Test to write:** `ManufactureTest::test_conversion_cost_flows_into_fg`
- **Rollback:** cancel = reversal
- **Estimated size:** L

### TASK-032 — Stock reports

- [ ] Status: not started
- **Depends on:** TASK-027
- **Why:** §3.9.12 + the P0 tie badge.
- **Files to create:** `StockValuationReport.php`, `StockLedgerController` screens (`stock/ledger`, `stock/valuation`, movement, slow-moving), exports, `tests/Feature/Accounting/StockValuationReportTest.php`
- **Files to modify:** routes, sidebar; `app/Http/Controllers/Admin/InventoryController.php` links
- **Schema changes:** none
- **Acceptance criteria:** valuation as-on any historical date recomputes from SLE ≤ date and equals ledger 1211 as-on same date (badge PASS); slow-moving lists items with no issue in N days.
- **Test to write:** `StockValuationReportTest::test_as_on_date_ties_to_ledger`
- **Rollback:** remove screens
- **Estimated size:** M

### TASK-033 — Integrity v2 (stock checks)

- [ ] Status: not started
- **Depends on:** TASK-032
- **Why:** §3.11.4 checks 4 join the nightly gate.
- **Files to create:** extend `VerifyIntegrity` + `tests/Feature/Accounting/IntegrityStockTest.php`
- **Files to modify:** `app/Console/Commands/Accounting/VerifyIntegrity.php`
- **Schema changes:** none
- **Acceptance criteria:** corrupting a `balance_value_after` chain or a stock_balances row is detected and named; clean data passes.
- **Test to write:** `IntegrityStockTest::test_detects_broken_running_chain`
- **Rollback:** n/a
- **Estimated size:** S

### TASK-034 — Backdated stock entry recompute

- [ ] Status: not started
- **Depends on:** TASK-033
- **Why:** §3.3.2 — late-arriving GRNs happen in real life; history must heal without editing posted journals.
- **Files to create:** `RecomputeForwardService` inside Costing namespace, permission `inventory_valuation.backdate`, `tests/Feature/Accounting/BackdatedEntryTest.php`
- **Files to modify:** `StockPostingService` (gate + call)
- **Schema changes:** none
- **Implementation notes:** recompute SLE chain forward from insertion point; where a posted DLN's issue value differs under the new chain, post a value-adjustment JV (Dr/Cr COGS↔Stock, `line_role=cogs`) per affected DN dated the backdated entry's date; never touch the old journal. Wrap whole thing in one transaction; refuse if any affected period is locked.
- **Acceptance criteria:** insert a GRN before an existing issue → WA rates shift, adjustment JV equals the delta, integrity passes after.
- **Test to write:** `BackdatedEntryTest::test_adjustment_journal_heals_history`
- **Rollback:** the adjustment JV is reversible
- **Estimated size:** L

### TASK-035 — GOLDEN TEST 1 (gates M2)

- [ ] Status: not started
- **Depends on:** TASK-026, TASK-027, TASK-033, TASK-034 (and implicitly all M2)
- **Why:** §5.1 is the acceptance test for the entire accounting core — the owner's own words.
- **Files to create:** `tests/Feature/Accounting/GoldenTradingTest.php` (assertions 1-8 and 10-12; assertion 9 GST joins at TASK-049 as `GoldenTradingGstTest`)
- **Files to modify:** none
- **Schema changes:** none
- **Acceptance criteria:** all §5.1 assertions except #9 pass on MySQL addons suite; paste output into commit message (§0 rule 3, §5.3).
- **Test to write:** the file itself
- **Rollback:** n/a
- **Estimated size:** M

**M2 Definition of Done:** GoldenTradingTest green (MySQL); `accounting:verify-integrity` v2 all-PASS after the golden run **and** after a 40-event random-walk seeder (`database/seeders/StockFuzzSeeder.php` — part of TASK-033); valuation badge PASS on screen; no code path writes quantity-only movements when accounting is enabled.
### Milestone M3 — Quote-to-Cash & Purchase-to-Pay wired to the ledger

Parallel: 036+037 first (order). Then three independent tracks: sales (038→039→040→041), purchase (042→043→044), org (045, 046, 047, 048) — tracks can interleave; 049/050 last.

### TASK-036 — Party & state statutory identity

- [ ] Status: not started
- **Depends on:** TASK-017
- **Why:** §3.6.1 — POS and CGST/SGST-vs-IGST are functions of validated state codes; garbage in, wrong government paid.
- **Files to create:** migrations `add_gst_state_codes_to_states` (full 36-code data map: 01 JK, 02 HP, 03 PB, 04 CH, 05 UK, 06 HR, 07 DL, 08 RJ, 09 UP, 10 BR, 11 SK, 12 AR, 13 NL, 14 MN, 15 MZ, 16 TR, 17 ML, 18 AS, 19 WB, 20 JH, 21 OD, 22 CG, 23 MP, 24 GJ, 26 DNHDD, 27 MH, 29 KA, 30 GA, 31 LD, 32 KL, 33 TN, 34 PY, 35 AN, 36 TG, 37 AP, 38 LA; UT flags on 04, 26, 31, 34, 35, plus JK/LA per current status) and `alter_parties_statutory` (§4.10.4 rest), `app/Services/Accounting/Gst/{GstinValidator,PlaceOfSupplyResolver}.php`, `tests/Unit/GstinValidatorTest.php`
- **Files to modify:** customer/vendor FormRequests + forms (state dropdown from states, GSTIN field with live checksum hint, PAN auto-derive), `app/Models/{Customer,Vendor}.php`, legacy-state string mapper command `accounting:map-party-states` (report of unmatched)
- **Schema changes:** §4.10.2 + §4.10.4
- **Acceptance criteria:** valid GSTIN `27AAPFU0939F1ZV` passes checksum; wrong check digit fails; state 27 derived; PAN `AAPFU0939F` extracted; unmatched legacy states listed not guessed.
- **Test to write:** `GstinValidatorTest::test_checksum_and_state_derivation`
- **Rollback:** migrations down
- **Estimated size:** M

### TASK-037 — Tax rates, GST columns, GstCalculator

- [ ] Status: not started
- **Depends on:** TASK-036, TASK-003
- **Why:** §3.6.2-3.6.4 — one pure function decides the split; documents store it forever.
- **Files to create:** migrations `create_tax_rates` (+seed 0/0.1/0.25/3/5/12/18/28), `add_gst_columns_to_invoices` (§4.10.5), `app/Services/Accounting/Gst/GstCalculator.php`, fixture `tests/Unit/GstCalculatorTest.php` (40-case table §3.6.2)
- **Files to modify:** `DocumentTotalsService` (call calculator per line; write GST columns; header five totals + round_off per §3.6.4 — **boundary: per-line tax 2 dp; invoice rupee rounding only here**), invoice create/edit blades (POS display, treatment select, per-line rate from product default)
- **Schema changes:** §4.6 tax_rates + §4.10.5
- **Acceptance criteria:** intra 18% on 1,000 → 90/90; inter → 180 IGST; export_lut → zeros with bucket EXP; 3 × 333.335 lines round per-line then round-off balances grand total to whole rupee.
- **Test to write:** `GstCalculatorTest` (fixture-driven)
- **Rollback:** migrations down (columns default 0 — safe)
- **Estimated size:** L

### TASK-038 — Invoice finalise → SAL posting

- [ ] Status: not started
- **Depends on:** TASK-027, TASK-037
- **Why:** §3.4.5 — revenue exists only when the tax invoice posts; this replaces SUM-of-drafts "revenue".
- **Files to create:** `app/Services/Accounting/Posting/SalesInvoicePostingService.php`, migration `add_posting_columns_to_invoices` (journal_id, finalised_*, project_id, status enum extension), `tests/Feature/Accounting/InvoiceFinaliseTest.php`
- **Files to modify:** `InvoiceController` (finalise/cancel actions, Postings tab), `InvoiceService` (block edit after finalise via `HasPostedJournals` — create trait `app/Support/Accounting/HasPostedJournals.php` here), invoice show blade, permission migration (`invoices.finalise`, `invoices.cancel`; remap `invoices.delete` UI), `routes/web.php`
- **Schema changes:** §4.10.5 remainder
- **Implementation notes:** posting per §3.2 #1 with auto-DN branch (§3.4.5) in the same transaction; gapless number assigned here; idempotency `invoices:{id}:finalise`; party ledger via `ensurePartyLedger`; dims: project/cost centre from header, product/qty/hsn per line; MB-2dp.
- **Acceptance criteria:** finalise posts Dr Debtor 1,062 / Cr Sales 900 / Cr CGST 81 / Cr SGST 81 for the §5.1-GST fixture; uncovered goods lines auto-DN when flag on, hard error when off; edit after finalise throws; number sequence gapless across a cancel.
- **Test to write:** `InvoiceFinaliseTest::test_posts_revenue_gst_and_auto_dn`
- **Rollback:** cancel action (reversal); schema stays
- **Estimated size:** L + M (XL split into two commits: (a) trait + posting service, (b) controller/UI)

### TASK-039 — Invoice cancel + Credit Notes

- [ ] Status: not started
- **Depends on:** TASK-038
- **Why:** §3.4.7 / Sec 34 — corrections must be documents, not edits.
- **Files to create:** migrations `create_credit_notes_tables`, `CreditNotePostingService`, `CreditNoteController`, views, permissions, `tests/Feature/Accounting/CreditNoteTest.php`
- **Files to modify:** invoice cancel path (same-period + no-receipts guard; else UI points to CN)
- **Schema changes:** §4.4 credit notes
- **Acceptance criteria:** CN with restock returns stock at DN `issued_cost` (not current WA) and posts §3.2 #8; register shows negative GST rows; cancelled invoice keeps number, appears in doc-series list as cancelled.
- **Test to write:** `CreditNoteTest::test_restock_at_original_cost`
- **Rollback:** CN cancel = reversal
- **Estimated size:** L

### TASK-040 — Receipts, allocations, advances, bank/cash master

- [ ] Status: not started
- **Depends on:** TASK-038
- **Why:** §3.4.6 — money in must hit a real bank/cash ledger and settle specific invoices (or sit as a visible advance).
- **Files to create:** migrations `create_bank_accounts` + `create_receipts_tables`, `ReceiptPostingService`, `BankAccountController` (master CRUD), `ReceiptController` + allocation UI, permissions (`banking.manage`, `receipts.*`), `tests/Feature/Accounting/ReceiptTest.php`
- **Files to modify:** routes, sidebar
- **Schema changes:** §4.9 bank_accounts (+deferred FKs), §4.4 receipts/allocations
- **Implementation notes:** posting §3.2 #4 incl. TDS-deducted leg (Dr 1261) and GST-on-advance for services (§3.4.6; 18/118 fraction, 2 dp — boundary stated); over-allocation blocked at grand_total − existing allocations (row-lock invoice); `invoices.amount_paid/balance_due/status` maintained from allocations inside the txn (single writer).
- **Acceptance criteria:** part-payment sets invoice `partial`; unallocated ₹590 on a services business posts advance 500 + output 45/45; refund flow reverses GST-on-advance.
- **Test to write:** `ReceiptTest::test_advance_gst_and_allocation_math`
- **Rollback:** receipt cancel = reversal + allocation rollback
- **Estimated size:** L

### TASK-041 — Legacy payments migration

- [ ] Status: not started
- **Depends on:** TASK-040
- **Why:** DECISION-06 — one receipt system; history preserved paisa-perfect.
- **Files to create:** migration `backfill_receipts_from_payments` (copy rows → receipts+one allocation, `legacy_payment_id`, `mode` mapped, **no journals for pre-books rows** — §7 explains), `tests/Feature/Accounting/PaymentsBackfillTest.php`
- **Files to modify:** `PaymentService` (writes throw), `PaymentController` (index/show read-only, create/destroy redirect to receipts with flash), routes, sidebar label "Receipts"
- **Schema changes:** none structural
- **Acceptance criteria:** Σ payments = Σ migrated allocations per business; old URLs render read-only; new receipt numbering unaffected.
- **Test to write:** `PaymentsBackfillTest::test_sums_reconcile_exactly`
- **Rollback:** receipts rows with `legacy_payment_id` deletable pre-books; PaymentService un-freeze
- **Estimated size:** M

### TASK-042 — Purchase bills + 3-way match

- [ ] Status: not started
- **Depends on:** TASK-024, TASK-037
- **Why:** §3.5.3-3.5.4 — the ITC source document and the creditor's birth certificate.
- **Files to create:** migrations `create_purchase_bills_tables`, `PurchaseBillPostingService`, `ThreeWayMatchService`, `PurchaseBillController` + from-GRN builder UI, permissions, `tests/Feature/Accounting/PurchaseBillTest.php`
- **Files to modify:** routes, sidebar, vendor show (bills tab)
- **Schema changes:** §4.5 bills tables
- **Implementation notes:** posting §3.2 #2; GRN-line balance allocation under `FOR UPDATE`; PPV vs stock-true-up branch by remaining on-hand qty (§3.5.3); vendor-invoice unique guard; ITC statuses stored per line (register rows in TASK-049); TDS hook point left as event `PurchaseBillFinalised` (TASK-058 listens). Boundary: line taxable 2 dp, PPV rounded 2 dp on the difference.
- **Acceptance criteria:** bill @ PO rate → GRN Clearing zeroes; bill @ rate+5 with stock on hand adjusts Stock; with stock sold posts PPV; match FAIL blocks without override permission.
- **Test to write:** `PurchaseBillTest::test_clearing_ppv_and_trueup_branches`
- **Rollback:** bill cancel = reversal (blocked if payments allocated)
- **Estimated size:** L + M (XL split into two commits: (a) tables + posting, (b) match + UI)

### TASK-043 — Vendor payments + Rule 37 tracker

- [ ] Status: not started
- **Depends on:** TASK-042, TASK-040 (bank master)
- **Why:** §3.5 + §3.5.8 — payables settled, 180-day ITC clock watched.
- **Files to create:** migrations `create_vendor_payments_tables`, `VendorPaymentPostingService`, `VendorPaymentController` (+bulk due-this-week screen), `app/Console/Commands/Accounting/WarnRule37Unpaid.php` (+schedule daily 07:30), `tests/Feature/Accounting/VendorPaymentTest.php`
- **Files to modify:** routes, sidebar, notification settings (event `rule37.warning` through the existing permission-gated `NotificationDispatcher`)
- **Schema changes:** §4.5 vendor payments
- **Acceptance criteria:** payment allocation ages bills correctly; 150-day warning fires once per bill (idempotent by log); 181-day draft reversal JV appears with pro-rata unpaid fraction (test: 40% unpaid → 40% ITC).
- **Test to write:** `VendorPaymentTest::test_rule37_prorata_draft`
- **Rollback:** payment cancel = reversal
- **Estimated size:** L

### TASK-044 — Debit notes

- [ ] Status: not started
- **Depends on:** TASK-042
- **Why:** §3.2 #7 — purchase returns/rate disputes without editing bills.
- **Files to create:** migrations, posting service, controller, views, `tests/Feature/Accounting/DebitNoteTest.php`
- **Schema changes:** §4.4 note (debit_notes mirror)
- **Acceptance criteria:** DBN with return-stock issues at original GRN cost; creditor reduced; ITC reversal rows staged.
- **Test to write:** `DebitNoteTest::test_return_reduces_creditor_and_itc`
- **Rollback:** cancel = reversal
- **Estimated size:** M

### TASK-045 — Expense module → ledger

- [ ] Status: not started
- **Depends on:** TASK-040 (bank master), TASK-037
- **Why:** §3.5.6 — the owner's expense screen finally answers "which money moved".
- **Files to create:** migrations `alter_expenses_for_accounting` + `alter_expense_categories_ledger` (+auto-create leaf per existing category, command `accounting:map-expense-categories`), `ExpensePostingService`, `tests/Feature/Accounting/ExpensePostingTest.php`
- **Files to modify:** `ExpenseService` (record→post, markPaid→payment post), `ExpenseController`, expense form blade (vendor, bank account, GST/ITC block, capital toggle, prepaid period, cost centre/project), `StoreExpenseRequest` (attachment-over-threshold rule)
- **Schema changes:** §4.10.9-10
- **Implementation notes:** postings §3.5.6; recurring generator copies new fields; `is_capital` routes to CWIP/asset ledger and emits `CapitalExpenseRecorded` event (TASK-067 listens). Prepaid flags (`is_prepaid`, period columns) are captured here; the amortisation engine lands in TASK-046. Boundary: MB-2dp.
- **Acceptance criteria:** unpaid expense → Dr category ledger / Cr Expenses Payable; pay → Dr payable / Cr Bank; ITC-eligible GST splits to Input ledgers; attachment enforced > threshold.
- **Test to write:** `ExpensePostingTest::test_record_and_pay_legs`
- **Rollback:** reversal; flags off restore old markPaid
- **Estimated size:** L

### TASK-046 — Prepaid/accrual engine + employee money

- [ ] Status: not started
- **Depends on:** TASK-045
- **Why:** §3.5.5/§3.5.7 — monthly P&L truth (insurance ≠ one bad month) and reimbursements/requisitions that actually move money.
- **Files to create:** migrations `create_prepaid_and_accrual_tables`, `PrepaidAmortisationService` + command (schedule monthly 1st 02:30), reimbursement/requisition posting hooks, `tests/Feature/Accounting/{PrepaidTest,EmployeeMoneyTest}.php`
- **Files to modify:** `ReimbursementService::transition` (approve/disburse postings §3.5.7), `RequisitionService::disburse` (advance posting + settlement screen), employee ledger creation via `ensurePartyLedger(Employee)`
- **Schema changes:** §4.5 prepaid/accrual + §4.10.13/15
- **Acceptance criteria:** ₹12,000 insurance Jul–Jun → 12 lines summing exactly, monthly command idempotent; reimbursement disburse leaves employee ledger at zero; requisition advance sits in 1262 until settled.
- **Test to write:** `PrepaidTest::test_twelve_month_schedule_is_paise_exact`
- **Rollback:** reversals; command no-op when disabled
- **Estimated size:** L

### TASK-047 — Payroll posting

- [ ] Status: not started
- **Depends on:** TASK-046
- **Why:** §3.5.7 — the biggest expense of a service business enters the P&L, department-wise.
- **Files to create:** migration `add_journal_to_payslips` + `add_cost_centre_to_departments` (+auto-create cost centres), `PayrollPostingService`, batch-finalise UI hook in `Hr\PayrollController`, `tests/Feature/Accounting/PayrollPostingTest.php`
- **Files to modify:** `PayrollService` (freeze after post via HasPostedJournals on Payslip), payroll screens (Post to Books button, month status)
- **Schema changes:** §4.10.11-12
- **Implementation notes:** one JV per month per business per §3.5.7 (employer PF/ESI from `StatutoryService` math — reuse, don't re-derive); dims: cost centre per department via allocation of each payslip; idempotency `payroll:{business}:{year}-{month}`; boundary MB-2dp on the summed components (component sums are already 2 dp).
- **Acceptance criteria:** generated month posts JV whose net-pay credit equals Σ payslips.net_pay; PF payable credit = employee PF + employer EPF+EPS; payslip edit after post throws; salary payment voucher clears 2432.
- **Test to write:** `PayrollPostingTest::test_batch_jv_ties_to_payslips`
- **Rollback:** reversal + unfreeze command (audit-logged)
- **Estimated size:** L

### TASK-048 — Projects, quotation revisions, credit limit

- [ ] Status: not started
- **Depends on:** TASK-038
- **Why:** §3.4.1/§3.4.3/§3.4.9 — the estimate-vs-actual spine and controlled SO edits.
- **Files to create:** migrations `create_quotation_revisions` + `add_project_links` (§4.10.6/7/8 project_id + §4.10.16), `QuotationRevisionService`, `ProjectController` (CRUD + spawn-from-lead/quote), views, `tests/Feature/Accounting/{QuotationRevisionTest,CreditLimitTest}.php`
- **Files to modify:** `QuotationService` (snapshot on edit of sent/accepted; `cost_snapshot` on items — §4.10.6), `SalesOrderService` (credit-limit gate on confirm; delivered/billed-line freeze on update), quotation/SO blades
- **Schema changes:** as listed
- **Acceptance criteria:** editing an accepted quotation forces revision R2 and re-send; SO confirm over credit limit warns/blocks per flag using posted-debtor + unbilled-DN exposure; project carries through quote→SO→DN→invoice lines.
- **Test to write:** `QuotationRevisionTest::test_accepted_edit_snapshots`
- **Rollback:** migrations down
- **Estimated size:** L

### TASK-049 — GST register write-side + integrity v3

- [ ] Status: not started
- **Depends on:** TASK-038, TASK-039, TASK-042, TASK-044, TASK-045
- **Why:** §3.6.7-3.6.8 — every GST rupee gets a register row born in the same transaction as its journal; the P0 reconciliation becomes checkable.
- **Files to create:** migration `create_gst_ledger_entries`, `GstRegisterService` (write API used by all posting services + `reconcile()`), integrity checks 5+9, `tests/Feature/Accounting/GstRegisterTest.php`, `tests/Feature/Accounting/GoldenTradingGstTest.php` (§5.1 assertion 9)
- **Files to modify:** the five posting services (emit rows), `VerifyIntegrity`
- **Schema changes:** §4.6 gst_ledger_entries
- **Acceptance criteria:** §5.1 assertion 9 passes; register Σ = 241x movements for a busy seeded month; a manual JV touching 2411 without register flags in integrity.
- **Test to write:** `GoldenTradingGstTest` (whole scenario)
- **Rollback:** n/a (append-only register; corrections are negations)
- **Estimated size:** L

### TASK-050 — GOLDEN TEST 2 (gates M3)

- [ ] Status: not started
- **Depends on:** TASK-046, TASK-047, TASK-048, TASK-049
- **Why:** §5.2 — services/WIP/advance flow proven end-to-end.
- **Files to create:** `tests/Feature/Accounting/GoldenServicesTest.php` (all §5.2 assertions), WIP release logic (`app/Services/Accounting/Posting/WipReleaseService.php`) if not landed via 038 — it lands here
- **Files to modify:** `SalesInvoicePostingService` (WIP release hook §3.4.8 incl. final-milestone full release)
- **Schema changes:** none
- **Acceptance criteria:** §5.2 assertions 1-6 green on MySQL.
- **Test to write:** the file itself
- **Rollback:** n/a
- **Estimated size:** M

**M3 Definition of Done:** both golden tests green; a full trade cycle (PO→GRN→bill→payment; quote→SO→DN→invoice→receipt; expense→pay; payroll month) runs from the UI on a pilot business with **every** document showing its Postings tab; integrity v3 all-PASS; the executive dashboard still renders (unchanged — replaced in M7).

### Milestone M4 — GST engine & returns + TDS

Parallel: 051-055 sequential-ish (051→052→053; 054, 055 parallel after 052); 056/057 parallel anytime after 051; 058→059; 060 last.

### TASK-051 — RCM + self-invoices

- [ ] Status: not started
- **Depends on:** TASK-049
- **Why:** §3.6.5 — GTA freight is the single most common SME RCM miss.
- **Files to create:** `RcmService`, RCM category seed (in `config/gst.php` + tax_rates JSON), self-invoice PDF view + numbering (RCM series), settlement validation, `tests/Feature/Accounting/RcmTest.php`
- **Files to modify:** purchase bill/expense forms (RCM auto-flag by category), posting services (RCM legs §3.6.5)
- **Schema changes:** none (columns exist)
- **Acceptance criteria:** GTA bill 10,000 @5% RCM → Cr RCM Payable 500, no vendor-side tax, self-invoice PDF numbered; ITC row `rcm_pending_payment` flips eligible only after GST cash payment voucher.
- **Test to write:** `RcmTest::test_itc_waits_for_cash_payment`
- **Rollback:** reversal
- **Estimated size:** M

### TASK-052 — GSTR-1 builder

- [ ] Status: not started
- **Depends on:** TASK-049
- **Why:** §3.6.9 — the monthly artefact every GST-registered owner owes their CA.
- **Files to create:** `Gstr1Builder`, `HsnSummaryBuilder`, migration `create_gst_returns`, `GstReturnController` + period screen, JSON+Excel exports, `tests/Feature/Accounting/Gstr1Test.php` (fixture month incl. B2B/B2CS/CDNR/cancelled doc)
- **Schema changes:** §4.6 gst_returns
- **Acceptance criteria:** fixture JSON equals hand-built portal-schema file byte-for-byte (stored fixture); Table 13 counts cancelled; books_diff zero.
- **Test to write:** `Gstr1Test::test_fixture_month_matches_expected_json`
- **Rollback:** builds are snapshots; delete rows
- **Estimated size:** L

### TASK-053 — GSTR-3B builder + filed lock

- [ ] Status: not started
- **Depends on:** TASK-052
- **Why:** §3.6.9 — 3B is where cash leaves; every cell must be traceable.
- **Files to create:** `Gstr3bBuilder`, screen tab, `tests/Feature/Accounting/Gstr3bTest.php`
- **Files to modify:** `GstReturnController`; mark-filed flow (snapshot + period GST-flag)
- **Schema changes:** none
- **Acceptance criteria:** 3.1(a) = output register Σ; 4(A)(3) RCM ITC appears only post-payment; post-filing document dated in period lands in next period's amendment bucket with banner.
- **Test to write:** `Gstr3bTest::test_cells_trace_to_register`
- **Rollback:** unfile (audit-logged, Admin only)
- **Estimated size:** M

### TASK-054 — GSTR-2B reconciliation

- [ ] Status: not started
- **Depends on:** TASK-052
- **Why:** §3.6.9 — ITC is only as good as its 2B match (Sec 16(2)(aa)).
- **Files to create:** migration `create_gstr2b_lines`, `Gstr2bReconciliationService` (JSON import + matcher), workbench screen (buckets, accept/link/create-bill), `tests/Feature/Accounting/Gstr2bTest.php` (8-case crafted file)
- **Schema changes:** §4.6 gstr2b_lines
- **Acceptance criteria:** 8-case fixture buckets exactly as designed (§3.6.9 matching rules); accept action links bill; headline ITC-per-2B vs books renders.
- **Test to write:** `Gstr2bTest::test_matcher_buckets_all_cases`
- **Rollback:** delete import batch
- **Estimated size:** L

### TASK-055 — ITC register + Rule 42/43 wizard

- [ ] Status: not started
- **Depends on:** TASK-053
- **Why:** §3.6.7 — common-credit apportionment; audit-proof working papers.
- **Files to create:** ITC register screen, Rule 42/43 wizard (turnover mix → D1/D2 → JV + stored working paper), `tests/Feature/Accounting/Rule42Test.php`
- **Schema changes:** none (working paper JSON on gst_returns row of the period)
- **Acceptance criteria:** exempt 20% of turnover → 20% of common ITC reversed, JV posted, paper stored; register filters by status.
- **Test to write:** `Rule42Test::test_d1_d2_math`
- **Rollback:** reversal
- **Estimated size:** M

### TASK-056 — E-invoice (IRN)

- [ ] Status: not started
- **Depends on:** TASK-038, TASK-052
- **Why:** §3.6.6 / Rule 48(4) — without IRN, mandated taxpayers' invoices aren't invoices.
- **Files to create:** migration `create_einvoices`, `EInvoiceService` + `Adapters/NicIrpAdapter` (+interface + `FakeIrpAdapter` for tests), queue job `GenerateIrn`, PDF QR block, cancel flow, screen, `tests/Feature/Accounting/EInvoiceTest.php`
- **Schema changes:** §4.6 einvoices
- **Acceptance criteria:** INV-01 payload from fixture invoice validates against bundled JSON schema (store schema in `resources/schemas/einv-1.1.json`); fake adapter round-trip stores IRN+QR and renders on PDF; >24 h cancel blocked; retries backoff, `pending_irn` status gates PDF issue when mandated.
- **Test to write:** `EInvoiceTest::test_payload_schema_and_cancel_window`
- **Rollback:** feature flag off per business
- **Estimated size:** L

### TASK-057 — E-way bill

- [ ] Status: not started
- **Depends on:** TASK-056
- **Why:** §3.6.6 — goods move under EWB or get detained.
- **Files to create:** migration `create_eway_bills`, `EwayBillService` + NIC adapter + fake, screens (generate from invoice/DN, Part-B update, validity list), `tests/Feature/Accounting/EwayBillTest.php`
- **Schema changes:** §4.6 eway_bills
- **Acceptance criteria:** threshold logic (≥50,000 default, per-state override map honored); validity = ceil(km/200) days; Part-B update extends payload; expiry listed on dashboard.
- **Test to write:** `EwayBillTest::test_threshold_and_validity_math`
- **Rollback:** flag off
- **Estimated size:** M

### TASK-058 — TDS engine

- [ ] Status: not started
- **Depends on:** TASK-042, TASK-043
- **Why:** §3.7 — deduct-or-be-disallowed; earlier of credit or payment.
- **Files to create:** migrations `create_tds_sections` (+§3.7 seed with effective dates) + `create_party_tds_profiles` + `create_tds_entries`, `TdsEngine`, listeners on `PurchaseBillFinalised`/`VendorPaymentFinalised`, vendor profile UI tab, `tests/Feature/Accounting/TdsEngineTest.php`
- **Schema changes:** §4.7 (challans in 059)
- **Acceptance criteria:** §3.7 edge cases: threshold-cross retro (full_on_cross), 194Q excess_only, LDC cap, no-PAN 20%, advance-then-bill nets base; JV legs per §3.2 #2/#3.
- **Test to write:** `TdsEngineTest::test_earlier_of_credit_or_payment_both_orders`
- **Rollback:** reversal; engine flag per business (`businesses` toggle piggybacked in migration: `tds_enabled` default true — include here)
- **Estimated size:** L

### TASK-059 — Challans + 26Q + integrity v4

- [ ] Status: not started
- **Depends on:** TASK-058
- **Why:** §3.7 — deposit tracking and quarterly return data.
- **Files to create:** migration `create_tds_challans`, challan UI + entry linking, `Exports/Accounting/TdsReturn26QExport.php` (+27Q variant for non-residents flagged parties), register screens, integrity check 6, `tests/Feature/Accounting/TdsChallanTest.php`
- **Schema changes:** §4.7 challans + entries FK
- **Acceptance criteria:** challan allocation zeroes section payable; 26Q workbook rows match register fixture; integrity flags unremitted > due date.
- **Test to write:** `TdsChallanTest::test_payable_ties_to_register_minus_challans`
- **Rollback:** unlink challan (audit-logged)
- **Estimated size:** M

### TASK-060 — Rule 46 invoice PDF + HSN summary polish

- [ ] Status: not started
- **Depends on:** TASK-056
- **Why:** §3.6.9/Rule 46 — the printed tax invoice must carry every mandatory particular or it's not a tax invoice.
- **Files to create:** new `resources/views/admin/invoices/pdf.blade.php` revision (GSTIN both sides, POS + state name/code, HSN per line, rate-wise tax summary table, round-off line, amount-in-words helper `app/Support/Accounting/AmountInWords.php` (Indian numbering: lakh/crore), signed-QR block, RCM declaration line, LUT declaration for exports), `tests/Feature/Accounting/InvoicePdfComplianceTest.php`
- **Files to modify:** CN/DBN PDFs same treatment
- **Schema changes:** none
- **Acceptance criteria:** rendered HTML contains all Rule 46 fields for a fixture; ₹12,34,567.89 → "Twelve Lakh Thirty-Four Thousand Five Hundred Sixty-Seven Rupees and Eighty-Nine Paise".
- **Test to write:** `InvoicePdfComplianceTest::test_rule46_fields_present`
- **Rollback:** old blade from git
- **Estimated size:** M

**M4 Definition of Done:** for a seeded pilot month: GSTR-1 JSON + 3B build with zero books_diff; 2B fixture reconciles; RCM cycle closes; TDS register ties to payable ledgers (integrity v4 all-PASS); e-invoice/e-way pass against fake adapters + JSON schema; invoice PDF is Rule 46-complete.

### Milestone M5 — Financial statements, Schedule III, assets, banking, openings

Parallel: 061→062→063 sequential; 064/065/066 parallel after 063; 067→068→069 sequential; 070/071/072 after 063 (071 also needs 066 for bank openings).

### TASK-061 — Trial Balance full + drill

- [ ] Status: not started
- **Depends on:** TASK-019 (upgrade), M3 complete
- **Why:** §3.9.3 — opening/period/closing columns, group subtotals, the CA's first ask.
- **Files to create:** upgraded service + screen + export, `tests/Feature/Accounting/TrialBalanceFullTest.php`
- **Schema changes:** none
- **Acceptance criteria:** TB across FY boundary rolls prior-year closing into opening; drill account → vouchers → source doc links work.
- **Test to write:** `TrialBalanceFullTest::test_opening_rollover_across_fy`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-062 — Trading + P&L (Tally & Schedule III)

- [ ] Status: not started
- **Depends on:** TASK-061
- **Why:** §3.9.4-3.9.5 — the owner's literal ask.
- **Files to create:** `TradingAndPlService`, screens (format toggle), PDF/Excel, `tests/Feature/Accounting/PlFormatsTest.php`
- **Schema changes:** none
- **Acceptance criteria:** §3.3.8 equality (derived Trading GP = perpetual GP) asserted; Sch III P&L maps every account (unmapped = 0 via integrity check 10); golden data renders GP 300 / NP 300.
- **Test to write:** `PlFormatsTest::test_trading_derivation_equals_perpetual`
- **Rollback:** n/a
- **Estimated size:** L

### TASK-063 — Balance Sheet (both formats, comparatives)

- [ ] Status: not started
- **Depends on:** TASK-062
- **Why:** §3.9.6.
- **Files to create:** `BalanceSheetService`, screens, exports, `tests/Feature/Accounting/BalanceSheetTest.php`
- **Schema changes:** none
- **Acceptance criteria:** golden data: totals 1,300/1,300 with Cash −200 asset-side; credit-balance bank flips to short-term borrowings per §3.9 render rule; prior-year column correct; MSME payables split renders.
- **Test to write:** `BalanceSheetTest::test_golden_totals_and_negative_cash_presentation`
- **Rollback:** n/a
- **Estimated size:** L

### TASK-064 — Cash Flow (AS-3 indirect)

- [ ] Status: not started
- **Depends on:** TASK-063
- **Why:** §3.9.7.
- **Files to create:** `CashFlowService`, screen, export, `tests/Feature/Accounting/CashFlowTest.php`
- **Schema changes:** none (uses `cash_flow_bucket` overrides)
- **Acceptance criteria:** CFO+CFI+CFF = Δ(cash+bank ledgers) on a seeded quarter; depreciation added back; debtor increase subtracts.
- **Test to write:** `CashFlowTest::test_three_sections_tie_to_delta_cash`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-065 — Registers + ageing + confirmations

- [ ] Status: not started
- **Depends on:** TASK-061
- **Why:** §3.9.10-3.9.11.
- **Files to create:** `RegisterService`, `AgeingService`, screens, exports, confirmation-letter PDF batch, `tests/Feature/Accounting/AgeingTest.php`
- **Schema changes:** none
- **Acceptance criteria:** bill-wise ageing from allocations (30-day boundary case in-bucket); Sch III ageing buckets; 43B(h) >45-day MSME flag column; sales register GST columns = invoice stored values.
- **Test to write:** `AgeingTest::test_bucket_boundaries_and_msme_flag`
- **Rollback:** n/a
- **Estimated size:** L

### TASK-066 — Bank statement import + auto-match + BRS

- [ ] Status: not started
- **Depends on:** TASK-040
- **Why:** §3.9.9 — unreconciled books are unaudited books.
- **Files to create:** migrations (imports/lines/rules/reconciliations), `StatementImportService` (CSV/XLSX mapper with saved templates), `Mt940Parser`, `AutoMatcher`, `BankingController` screens (import wizard, match workbench, BRS), `tests/Feature/Accounting/{Mt940ParserTest,AutoMatchTest,BrsTest}.php`
- **Schema changes:** §4.9 four tables
- **Acceptance criteria:** 20-line fixture: 16 auto-matched (amount+date, then ref-fuzzy), 4 left; rule creates draft voucher suggestion never auto-posts; BRS closes to the paisa and prints; duplicate re-import dedupes by hash.
- **Test to write:** `BrsTest::test_reconciliation_closes_exactly`
- **Rollback:** delete import batch (unmatched only; matched requires unmatch first)
- **Estimated size:** L + L (XL split into two commits: (a) import + parser, (b) matcher + BRS)

### TASK-067 — Fixed asset register + capitalisation + CWIP

- [ ] Status: not started
- **Depends on:** TASK-045
- **Why:** §3.8 — laptops out of lunch, CWIP for the under-construction.
- **Files to create:** migrations (FA tables + it_blocks seed), `CapitalisationService`, `FixedAssetController` + screens, listener on `CapitalExpenseRecorded`/bill capital lines, `tests/Feature/Accounting/CapitalisationTest.php`
- **Schema changes:** §4.8 (runs in 068)
- **Acceptance criteria:** capital bill line → FAR draft with cost incl. non-creditable GST; CWIP accumulates and commissions to asset; per-class ledgers auto-created under Fixed Assets.
- **Test to write:** `CapitalisationTest::test_bill_to_far_with_blocked_gst_in_cost`
- **Rollback:** decapitalise (reversal) pre-depreciation only
- **Estimated size:** L

### TASK-068 — Schedule II depreciation runs

- [ ] Status: not started
- **Depends on:** TASK-067
- **Why:** §3.8 Companies-Act leg.
- **Files to create:** migrations (runs tables), `ScheduleIIDepreciationService`, `RunMonthlyDepreciation` command + screen button, `tests/Feature/Accounting/DepreciationTest.php`
- **Schema changes:** §4.8 runs
- **Acceptance criteria:** the §3.8 laptop fixture month-1 value exact; SLM and WDV both; idempotent per month; PeriodGuard respected.
- **Test to write:** `DepreciationTest::test_slm_wdv_day_apportioned_fixture`
- **Rollback:** run reversal (draft-then-post pattern)
- **Estimated size:** L

### TASK-069 — IT blocks + disposals + deferred tax note

- [ ] Status: not started
- **Depends on:** TASK-068
- **Why:** §3.8 IT-Act leg + profit/loss on sale.
- **Files to create:** `ItBlockService`, `DisposalService`, FY working screen, deferred-tax proposed-JV builder, `tests/Feature/Accounting/ItBlockTest.php`
- **Schema changes:** §4.8 it_block_fy_lines
- **Acceptance criteria:** 180-day split; disposal reduces block by proceeds while books show profit/loss line; DTL = (CA WDV − IT WDV) × rate appears as proposal only.
- **Test to write:** `ItBlockTest::test_dual_books_diverge_correctly`
- **Rollback:** n/a (working paper)
- **Estimated size:** L

### TASK-070 — Notes, ratios, budgets

- [ ] Status: not started
- **Depends on:** TASK-063
- **Why:** §3.9.8/3.9.14.
- **Files to create:** notes working-paper exports, `RatioService`, `ledger_budgets` migration + `BudgetVsActualService` + screens, `tests/Feature/Accounting/RatioBudgetTest.php`
- **Schema changes:** §4.1 ledger_budgets
- **Acceptance criteria:** ratio math fixture (current ratio, DSO); budget monthly spread vs actuals variance; FA movement note grid ties to FAR.
- **Test to write:** `RatioBudgetTest::test_dso_and_variance_math`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-071 — Opening balance wizard + dry run (§7)

- [ ] Status: not started
- **Depends on:** TASK-063, TASK-066
- **Why:** §7 — a two-year-old tenant must start clean books without retro-posting.
- **Files to create:** migration `create_opening_bills`, `app/Services/Accounting/OpeningBalanceService.php`, wizard screens (ledger grid, bill-wise debtors/creditors, stock lots, bank), `accounting:opening-dry-run` command, `tests/Feature/Accounting/OpeningBalanceTest.php`
- **Schema changes:** §4.9 opening_bills
- **Implementation notes:** posts ONE `JRN` "Opening Balances as at {books_start_date − 1}" journal per business (idempotent; editable only while 1901 suspense non-zero and period unlocked); debtor/creditor control lines must equal Σ opening_bills per party; stock openings create SLE `in` rows + lots dated day-zero and the Stock-in-Hand line auto-computes from them (never hand-typed); difference → 1901 with red banner; §7 pre-fill queries provided verbatim.
- **Acceptance criteria:** wizard with §7's worked example produces balanced opening TB, zero suspense, stock report = 1211; dry-run prints the exact would-be journal without writing.
- **Test to write:** `OpeningBalanceTest::test_wizard_posts_balanced_opening_with_stock`
- **Rollback:** reverse opening journal (only while books empty of later postings)
- **Estimated size:** L

### TASK-072 — Project & cost-centre P&L + quote-vs-actual (wedge #1)

- [ ] Status: not started
- **Depends on:** TASK-062, TASK-048
- **Why:** §3.9.13/§2.3 — the report no competitor gives an SME.
- **Files to create:** `ProjectPlService`, screens (project P&L, variance panel with reason tags), export, `tests/Feature/Accounting/ProjectPlTest.php`
- **Schema changes:** none (variance reason tags stored JSON on projects)
- **Acceptance criteria:** §5.2 project shows revenue 1,500 / cost 700 / margin 800; quoted-vs-actual panel shows snapshot cost vs actual with drill; cross-project consolidated equals company P&L for project-tagged lines.
- **Test to write:** `ProjectPlTest::test_project_margin_and_variance`
- **Rollback:** n/a
- **Estimated size:** L

**M5 Definition of Done:** full statement suite renders + exports for the pilot business; **integrity gate:** checks 1-10 all-PASS; Sch III has zero unmapped accounts; BRS closes on a real imported statement; opening wizard signed off on a copy of a real tenant DB (suspense = 0).

### Milestone M6 — CA Pack, CA Portal, Tally XML

Parallel: 073→074→075 sequential; 076→077→078 sequential; 079→080→081 sequential; the three chains run in parallel.

### TASK-073 — CA Pack skeleton + folders 01-03

- [ ] Status: not started
- **Depends on:** M5 complete
- **Why:** §3.10.1 — one button starts existing.
- **Files to create:** migration `create_ca_pack_runs`, `CaPackGenerator` (queued), `FinancialStatementsBuilder`, `LedgersBuilder`, `RegistersBuilder`, `00-INDEX` PDF view, screen (runs list/progress/download), permissions, `tests/Feature/Accounting/CaPackSkeletonTest.php`
- **Schema changes:** §4.9 ca_pack_runs
- **Acceptance criteria:** run produces ZIP with 00-INDEX + folders 01-03, SHA-256 manifest verifies, unlocked-period banner logic.
- **Test to write:** `CaPackSkeletonTest::test_manifest_hashes_verify`
- **Rollback:** delete run row + file
- **Estimated size:** L

### TASK-074 — CA Pack folders 04-08

- [ ] Status: not started
- **Depends on:** TASK-073
- **Why:** §3.10.1 statutory folders.
- **Files to create:** `GstFolderBuilder`, `TdsFolderBuilder`, `InventoryFolderBuilder`, `FixedAssetsFolderBuilder`, `BankFolderBuilder`, `tests/Feature/Accounting/CaPackStatutoryTest.php`
- **Schema changes:** none
- **Acceptance criteria:** each file non-empty for pilot data; 2B recon + valuation working + dual dep schedules present.
- **Test to write:** `CaPackStatutoryTest::test_all_statutory_files_present`
- **Rollback:** n/a
- **Estimated size:** L

### TASK-075 — CA Pack 09-11 + Reconciliation Summary

- [ ] Status: not started
- **Depends on:** TASK-074
- **Why:** §3.10.1 — the PASS/FAIL page is the trust artefact.
- **Files to create:** `PartiesFolderBuilder`, `VouchersFolderBuilder`, `AuditTrailFolderBuilder`, `ReconciliationSummaryBuilder` (+watermark), `tests/Feature/Accounting/CaPackReconTest.php`
- **Schema changes:** none
- **Acceptance criteria:** golden dataset → all-PASS summary; deliberately broken copy → FAIL lines + watermark.
- **Test to write:** `CaPackReconTest::test_pass_and_watermarked_fail_paths`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-076 — CA portal auth + read-only browse

- [ ] Status: not started
- **Depends on:** TASK-073
- **Why:** §3.10.2 — the auditor becomes a user.
- **Files to create:** migrations (`ca_users`, `ca_access_grants`), guard config, `EnsureCaAccess` middleware, `Ca\{AuthController,DashboardController,VoucherBrowserController}`, portal layout `resources/views/ca/…` (reuse admin components, badge "AUDITOR — READ ONLY"), invite flow from `accounting/ca-access`, `tests/Feature/Ca/CaPortalAccessTest.php`
- **Schema changes:** §4.9 two tables + `config/auth.php` guard `ca`
- **Acceptance criteria:** invite → set-password mail (existing mail patterns); expired/revoked grant → 403; every mutating route except the three workflows → 405; TB/statements render identically to admin's numbers.
- **Test to write:** `CaPortalAccessTest::test_time_boxed_read_only`
- **Rollback:** disable routes
- **Estimated size:** L

### TASK-077 — Audit queries + document requests

- [ ] Status: not started
- **Depends on:** TASK-076
- **Why:** §3.10.2(a)(b) — the audit conversation, anchored to vouchers.
- **Files to create:** migrations, controllers both sides, thread UI, notifications (events `ca.query_raised`, `ca.query_responded` via NotificationDispatcher), `tests/Feature/Ca/AuditQueryTest.php`
- **Schema changes:** §4.9 three tables
- **Acceptance criteria:** full lifecycle open→respond(attachment)→resolve with badges both sides; queries land in CA Pack 10 appendix on next run.
- **Test to write:** `AuditQueryTest::test_lifecycle_with_attachment`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-078 — Closing entry proposals

- [ ] Status: not started
- **Depends on:** TASK-077
- **Why:** §3.10.2(c) — CA proposes, client approves, ledger stays sovereign.
- **Files to create:** migration, `Ca\ClosingEntryController` + admin approval screen, posting bridge (maps name→account, `vouchers.post_to_controlled` semantics), `tests/Feature/Ca/ClosingProposalTest.php`
- **Schema changes:** §4.9 closing_entry_proposals
- **Acceptance criteria:** proposal with unknown account name forces mapping at approval; posted JV links back; rejection with note; audit trail rows at every step.
- **Test to write:** `ClosingProposalTest::test_propose_approve_post_chain`
- **Rollback:** reject/reverse
- **Estimated size:** M

### TASK-079 — Tally XML export

- [ ] Status: not started
- **Depends on:** M5 complete
- **Why:** §3.10.3 — the trust bridge; CA verifies our books inside Tally.
- **Files to create:** `TallyXmlExporter`, `TallyNameMapper`, export screen (masters/vouchers, period, inventory toggle), Mapping-Report.txt writer, byte-fixture `tests/fixtures/tally/sales-voucher.xml`, `tests/Feature/Accounting/TallyExportTest.php`
- **Schema changes:** none (`tally_alias` exists)
- **Acceptance criteria:** §3.10.3 envelope rules exactly (sign conventions Dr negative; ISDEEMEDPOSITIVE); collision suffixing stable across re-export; fixture voucher byte-equal; every §3.2 type maps or falls back per the toggle with report line.
- **Test to write:** `TallyExportTest::test_envelope_fixture_and_sign_convention`
- **Rollback:** n/a (read-only export)
- **Estimated size:** L

### TASK-080 — Tally XML import (onboarding)

- [ ] Status: not started
- **Depends on:** TASK-079, TASK-071
- **Why:** §3.10.3 — removes the #1 switching objection.
- **Files to create:** `TallyXmlImporter`, import screen (upload → preview tree → commit), opening-wizard pre-fill bridge, row-level report, `tests/Feature/Accounting/TallyImportTest.php`
- **Schema changes:** none
- **Acceptance criteria:** self-round-trip: export golden business → import into fresh business → ledgers, parties, items, opening balances equal (report zero diffs); idempotent re-import; malformed nodes reported not fatal.
- **Test to write:** `TallyImportTest::test_round_trip_losslessness`
- **Rollback:** delete imported (pre-books) masters
- **Estimated size:** L

### TASK-081 — CA Pack folder 12 + full-pack golden test

- [ ] Status: not started
- **Depends on:** TASK-075, TASK-079
- **Why:** §3.10.1 completes.
- **Files to create:** `TallyFolderBuilder`, `tests/Feature/Accounting/CaPackFullTest.php`
- **Schema changes:** none
- **Acceptance criteria:** full pack on golden dataset: all 13 folders + all-PASS recon + manifest verifies + Tally XMLs parse.
- **Test to write:** `CaPackFullTest::test_thirteen_folders_all_pass`
- **Rollback:** n/a
- **Estimated size:** S

**M6 Definition of Done:** a real CA (pilot's auditor) receives a generated pack and can open every file; portal grant issued, one query round-trips, one closing entry proposed→posted; Tally export imports into a TallyPrime instance with zero manual fixes (manual verification step — record the TallyPrime version used in Section 9 notes); integrity all-PASS.

### Milestone M7 — Analytics, project P&L polish, differentiators

Parallel: all six tasks are independent after M5; 082 waits for M4 (GST) + M5 (statements).

### TASK-082 — Executive dashboard: fiction → ledger

- [ ] Status: not started
- **Depends on:** M5 complete
- **Why:** §1.4 Flow C — the dashboard that lies today starts telling the ledger's truth.
- **Files to create:** `tests/Feature/Accounting/ExecutiveDashboardTest.php`
- **Files to modify:** `app/Http/Controllers/Admin/DashboardsController.php::executive` (replace: revenue = Sales group net of returns **taxable**, GP/NP from `TradingAndPlService`, cash position = bank+cash ledgers, receivables/payables = control accounts, margin trend = monthly GP from balances, top products by **actual** margin = Σ(line revenue) − Σ(COGS per DN line) per product; delete `gross_margin`, `net_cash_flow`, `topMargin` fictions), `resources/views/admin/dashboards/executive.blade.php`
- **Schema changes:** none
- **Acceptance criteria:** dashboard GP equals P&L GP for the same range; a March bulk purchase no longer craters March "margin"; per-product margin uses issued costs.
- **Test to write:** `ExecutiveDashboardTest::test_dashboard_numbers_equal_statements`
- **Rollback:** git revert (old fiction — don't)
- **Estimated size:** M

### TASK-083 — Stock intelligence

- [ ] Status: not started
- **Depends on:** TASK-032
- **Why:** §3.9.12 — dead stock is dead cash.
- **Files to create:** stock ageing report (by receipt lot age), dead-stock screen polish, reorder suggestions vs valuation, `tests/Feature/Accounting/StockAgeingTest.php`
- **Schema changes:** none
- **Acceptance criteria:** lot-age buckets tie to valuation total; dead-stock value at WA cost.
- **Test to write:** `StockAgeingTest::test_buckets_tie_to_valuation`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-084 — Ratio & trend pack

- [ ] Status: not started
- **Depends on:** TASK-070
- **Why:** §3.9.14 — owner-facing pulse without a CA visit.
- **Files to create:** trends screen (GP%, NP%, DSO, DPO, stock turns, interest cover — 12-month lines using the dataviz-consistent existing chart assets), export
- **Schema changes:** none
- **Acceptance criteria:** ratios equal hand-computed fixture; charts render from `account_balances` only (fast).
- **Test to write:** `tests/Feature/Accounting/RatioTrendTest.php::test_ratio_fixture`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-085 — Compliance cockpit

- [ ] Status: not started
- **Depends on:** TASK-043, TASK-059
- **Why:** §2 rows 12/17 — the misses that cost real money, on one screen: Rule-37 clocks, 43B(h) MSME >45d, TDS thresholds at 80%, GSTR filing status, e-invoice failures, unreconciled bank items, unlocked closed months.
- **Files to create:** `ComplianceCockpitController` + screen + daily digest notification event, `tests/Feature/Accounting/ComplianceCockpitTest.php`
- **Schema changes:** none
- **Acceptance criteria:** each tile counts match their source screens; digest fires once daily per business (idempotent by date).
- **Test to write:** `ComplianceCockpitTest::test_tiles_match_sources`
- **Rollback:** n/a
- **Estimated size:** M

### TASK-086 — User guide for accountants

- [ ] Status: not started
- **Depends on:** M6 complete
- **Why:** the product change is behavioural; the office person needs the "why" in plain words (and Marathi-friendly examples mirroring §5.1).
- **Files to create:** `docs/accounting-user-guide.md` (day-in-the-life flows: bill entry, DN+invoice, receipt allocation, month-end checklist, year-end checklist, CA pack how-to; the golden example retold with screenshots placeholders as figure captions), in-app help links (sidebar "Help" per §1.6 documentation module pattern — register in `DocumentationController` sections)
- **Schema changes:** none
- **Acceptance criteria:** documentation route renders the guide; month-end checklist enumerates: reconcile banks, post depreciation, amortise prepaids, review accruals, build GSTR-1/3B, lock month.
- **Test to write:** `tests/Feature/DocumentationTest.php` extension: guide section loads
- **Rollback:** n/a
- **Estimated size:** M

### TASK-087 — Performance & index audit

- [ ] Status: not started
- **Depends on:** M5 complete
- **Why:** §3.11.7 budgets stop regressions before tenants feel them.
- **Files to create:** `database/seeders/VolumeSeeder.php` (100k journal lines), `tests/Feature/Addons/PerformanceBudgetTest.php` (TB ≤ 2 s, 50-line post ≤ 250 ms on the CI-less local benchmark — assert generously ×3 headroom), `Model::preventLazyLoading` in accounting `TestCase`
- **Files to modify:** add any missing composite indexes surfaced by `EXPLAIN` runs (document each in the migration)
- **Schema changes:** index-only migration if needed
- **Acceptance criteria:** budgets met on volume-seeded DB; zero lazy-load violations across accounting feature tests.
- **Test to write:** `PerformanceBudgetTest::test_tb_under_budget_at_100k_lines`
- **Rollback:** n/a
- **Estimated size:** M

**M7 Definition of Done:** executive dashboard's every number provably equals a statement figure; compliance cockpit live; volume benchmark green; `docs/accounting-user-guide.md` published; full `accounting:verify-integrity --deep` all-PASS on pilot + volume DBs.

---
## 7. Migration & backfill strategy — how a two-year-old tenant gets books

**The rule (DECISION-01): do not retro-post history.** Two years of quotations, invoices, POs and expenses were created without costing, without GST splits, and with the §1.5 totalling bugs. Re-deriving journals from them would manufacture precise-looking fiction and the CA would rightly reject it. Instead the ledger **starts** at a chosen `books_start_date` from a **CA-signed opening Trial Balance**, exactly the way a business moving from any system to Tally does it. History remains fully visible as documents; the books begin clean.

### 7.1 Choreography per tenant

1. **Choose `books_start_date`.** Default: the 1st of the next month (mid-FY start is fine — the first FY row simply runs books_start_date → 31 March). A 1 April start is cleaner for the CA; offer both, never backdate before data quality allows.
2. **Enable `accounting_enabled`** (TASK-010 flags). Posting paths wake up for *new* documents dated ≥ books_start_date; everything older is `legacy_numbering`/`legacy_totals` and stays read-only where finalised.
3. **Run the pre-fill dry run:** `php artisan accounting:opening-dry-run {business}` (TASK-071). It prints — without writing anything — the proposed opening journal, the bill-wise lists, the stock suggestion, and every source query result, as a reviewable table. Sources:
   - **Debtors (bill-wise):** open invoices — `SELECT customer_id, invoice_number, invoice_date, due_date, balance_due FROM invoices WHERE business_id = ? AND deleted_at IS NULL AND balance_due > 0 AND invoice_date < {books_start_date}` → one `opening_bills` row each (side `receivable`); control total = Σ balance_due.
   - **Creditors:** the system has **no historical purchase bills** (§1.3 — P2P stopped at GRN), so vendor openings cannot be derived from data. The wizard presents a manual bill-wise entry grid per vendor, keyed from vendor statements/ledgers the owner already has. Unpaid `expenses` rows (`status='unpaid'`, `expense_date < books_start_date`) are offered as pre-fill payables (side `payable`, party = vendor if set, else the "Expenses Payable" ledger line).
   - **Stock:** a mandatory physical count as at books_start_date (use the TASK-030 stock-take screen in `kind='count'`, pre-books mode). Cost suggestion per item = latest PO rate: `SELECT poi.product_id, poi.rate FROM purchase_order_items poi JOIN purchase_orders po ON po.id = poi.purchase_order_id WHERE po.business_id = ? AND po.deleted_at IS NULL ORDER BY po.po_date DESC` (first row per product) — shown as *suggestion only*; the CA confirms rates (AS-2: cost, not current price). Wizard writes SLE `in` rows + FIFO lots dated day-zero; the Stock-in-Hand opening line is **computed** from them, never typed.
   - **Bank/cash:** per bank account, the statement balance at books_start_date (reconciled: statement ± cheques in transit entered as opening unpresented items in the BRS module).
   - **Everything else** (capital, loans, deposits, TDS/GST balances carried from the old regime, fixed-asset WDVs): manual grid, from the CA's signed TB. Fixed assets additionally enter the FAR (TASK-067) with their Companies-Act WDV as deemed cost and IT-block openings into `it_block_fy_lines.opening_wdv`.
   - **GST carry-forward:** closing ITC per the last filed GSTR-3B → Input CGST/SGST/IGST opening debits; unpaid output liability → Output ledgers credit. These must equal the portal's electronic ledgers — print both on the sign-off sheet.
4. **Post the opening journal** (one `JRN`, narration "Opening Balances as at {date−1}", source = the wizard). Any imbalance lands in `1901 Opening Balance Difference` and the wizard screen shows a red banner with the amount.
5. **Sign-off gate:** the business cannot lock its first period, and the CA Pack watermarks every run, while 1901 ≠ 0. Target: suspense zero, opening TB printed, CA signature on paper. Then lock the opening date.
6. **Parallel run (recommended, 1 month):** staff work normally; at month-end compare the ledger P&L against whatever the owner trusted before. Differences are explained by design (§1.4 fiction vs real COGS) — this month is where the owner *sees* why the old numbers were wrong. Exit criteria in §8.
7. **Payments history:** TASK-041 copies `payments` → `receipts` for continuity of screens and party history, with **no journals** for rows dated before books_start_date (their effect is inside the opening debtor balances — posting them too would double-count).

### 7.2 What deliberately does not happen

- No journal is ever created for a pre-books document. Old reports keep reading documents; new statements read only the ledger. The two meet at the opening TB and nowhere else.
- Historical "profit" is not restated inside the app. If the owner wants FY 2025-26 accounts, that year is done the old way (CA re-keys) — the pitch is that it's the **last** year anyone re-keys.
- No opening entry is editable after first lock: corrections post as dated JVs like any other correction (the CA will typically propose them via §3.10.2(c)).

### 7.3 Tenant-by-tenant rollout order

Backfill order for the existing multi-tenant install: (1) the owner's own business as pilot; (2) tenants with inventory-light service models (fewer opening lots to count); (3) inventory-heavy tenants last (their stock counts need scheduling). The `accounting_enabled` flag makes this per-tenant sequencing trivial — there is no big-bang.

---

## 8. Rollout plan

| Phase | Ships | Feature gate | Exit criteria |
|---|---|---|---|
| 0 (= M0) | correctness hotfixes, audit trail, backups | none — applies to everyone (bug fixes + always-on trail) | M0 DoD; one week of clean `audit_trail` growth in prod |
| 1 (= M1) | ledger core, manual JVs, TB | `accounting_enabled` per business (off everywhere) | M1 DoD on staging + pilot business enabled with CoA seeded |
| 2 (= M2) | costed inventory, DNs, landed cost | same flag + `books_start_date` | Golden Test 1 in prod-shadow (run against a copy of pilot DB); stock count executed |
| 3 (= M3) | posting on invoices/receipts/bills/payments/expenses/payroll | same | pilot parallel-run month closed; TB signed by pilot's CA; golden tests green in repo |
| 4 (= M4) | GST returns, TDS, e-invoice/e-way | `einvoice_enabled`, `tds_enabled` per business | pilot files one GSTR-1 and one 3B prepared **from** the app (filed on portal as usual); 26Q quarter data accepted by the CA |
| 5 (= M5) | statements, Sch III, FAR, BRS, openings wizard | same | pilot's opening suspense = 0; BS/P&L/BRS reviewed by CA; second + third tenants onboarded per §7.3 |
| 6 (= M6) | CA pack, CA portal, Tally XML | `ca_access.manage` grants | pilot's CA uses the portal for one real query cycle; Tally import verified in TallyPrime |
| 7 (= M7) | dashboards, cockpit, docs | none | old executive fiction deleted; user guide published |

**Pilot tenant:** the owner's own business (ALTechnics) — highest tolerance, fastest feedback, and the owner's Marathi test case becomes the live acceptance demo: enter the ₹1,000 purchase, the ₹200 freight, sell half at ₹900, open the Balance Sheet, point at ₹600.

**Kill switch:** setting `accounting_enabled = false` (Super Admin, audited) stops all *new* postings and hides accounting UI; it never deletes or unfreezes anything — posted journals and finalised documents stay immutable (that immutability *is* the statutory feature, not a bug to bypass). Individual risky integrations have their own gates (`einvoice_enabled`, `tds_enabled`, `rule37_auto_post` default off, `maker_checker_journals`). Nightly `accounting:verify-integrity` mails FAILs to the Super Admin (wire through the existing `NotificationDispatcher` + `notification_settings`).

**Ops notes for deployment (read before M1 goes anywhere):**
- Triggers (TASK-014) on MySQL with binary logging need either `SUPER` or `log_bin_trust_function_creators = 1` — set it in `docker/mysql/my.cnf` and on the managed DB *before* running migrations, or the migration fails mid-flight.
- The `audit_trail` partition migration takes a metadata lock — run in a low-traffic window (the table is new and empty, so it's instant unless something else holds locks).
- `.env` on prod has `APP_DEBUG=true` (§1.1) — turn it off in the same release as M0; debug pages leak schema to browsers.
- There is no CI (§1.1). Before M1, add the two-command gate to whatever runs deploys (even the `Makefile`): `php artisan test` and `php artisan test --configuration=phpunit.addons.xml`. The addons suite needs the `erptechindia_test` MySQL database that already exists in the compose file.
- Queue workers: e-invoice generation and CA-pack builds run on the `database` queue — supervisor config already runs a worker (`deploy/supervisor/`); confirm `--queue=default` covers new jobs (they use the default queue deliberately).

---

## 9. Decisions & open questions

Every DECISION has a recommended default that is safe to build without asking. Deviate only with the owner's sign-off, and record the deviation here.

| # | Decision | Recommended default | Rationale |
|---|---|---|---|
| DECISION-01 | Historical data → books | **No retro-posting; opening TB at `books_start_date`** (§7) | Historical documents carry known totalling bugs (§1.5) and no cost data; retro-posted books would be unauditable fiction. This is also how every Tally migration works, so CAs trust it. |
| DECISION-02 | Voucher numbering concurrency | **`voucher_sequences` row + `SELECT … FOR UPDATE` inside the posting transaction; number assigned only at finalisation** | Gapless (GSTR-1 Table 13), race-free under InnoDB row locks, no MySQL native sequences exist, and MAX()+1 is the current proven failure (§1.3). Cost: sequence row is a serialisation point per (type, FY) — fine at SME volume; deadlock retry ×3 handles crossings. |
| DECISION-03 | Inventory accounting model | **Perpetual postings + derived Tally-style Trading account (§3.3.8)** | Only perpetual keeps "Stock-in-Hand ledger = stock report on any date" continuously true (the P0 acceptance test). The derived presentation keeps the CA's familiar Purchases/Closing-Stock view without double bookkeeping. |
| DECISION-04 | Party master | **Keep separate `customers`/`vendors`; auto-created party sub-ledgers under Debtors/Creditors (§3.1.4)** | A unified party refactor would touch every module and migration for zero accounting gain; sub-ledgers give bill-wise books regardless. A customer who is also a vendor gets two ledgers — same as Tally practice. |
| DECISION-05 | GRN valuation timing | **Value stock at GRN using PO rate via `GRN Clearing`; true-up at bill (Stock if on-hand, else PPV) (§3.5.1-3.5.3)** | Stock must be usable and costed the day it lands (dispatches can't wait for the vendor's bill); GRNI clearing is the textbook answer and gives auditors the "received not billed" number free. |
| DECISION-06 | Customer receipts | **New `receipts` + allocations supersede `payments`; legacy rows copied read-only (TASK-041)** | `payments.invoice_id NOT NULL` structurally forbids advances and multi-invoice settlement; patching it would leave two write paths. |
| DECISION-07 | Weighted-average scope | **Per product per business (across warehouses); per-warehouse qty tracked, single WA rate** | Transfers between own godowns must not manufacture P&L; per-warehouse WA would do exactly that. FIFO lots remain per-warehouse for physical truth. |
| DECISION-08 | Negative stock | **Blocked by default; per-business `allow_negative_stock` opt-in with permanent integrity warning** | Negative stock destroys valuation math. The opt-in exists because Indian traders genuinely bill before GRN sometimes; the warning keeps it a debt, not a lifestyle. |
| DECISION-09 | Delivery notes for small traders | **`auto_delivery_note = true`: invoice finalise auto-creates+finalises the covering DN** | Counter-sale businesses will never operate a DN screen; the COGS entry must exist anyway. Explicit DNs remain for dispatch-driven businesses. |
| DECISION-10 | GST on advances | **On for services (auto when service items sold), off for goods** | Notification 66/2017-CT removed GST-on-advance for goods; services remain liable (Sec 12(2)). Toggle per business for edge profiles. |
| DECISION-11 | Salary expense grouping | **Indirect Expenses by default (`5203 Salaries & Wages`); re-parentable per business** | Trading/services SMEs show salaries below GP; a manufacturer can re-parent to Direct for factory wages without schema change. |
| DECISION-12 | TDS/TCS seed rates | **FY 2025-26 values as tabled in §3.7 (194H 2%, 194J(a) 2%/194J(b) 10%, 194I monthly ₹50k, 206C(1H) sunset 31-3-2025, 206AB omitted → optional override flag)** | Rates are law, not config guesses — but they change every Finance Act, so they're **effective-dated rows**, and go-live protocol includes verifying against the Act in force. |
| DECISION-13 | Fixed assets vs existing `assets` module | **Separate financial FAR (`fixed_assets`); optional link `operational_asset_id`. No revaluation model in scope.** | The existing module is IT-ops (assignments/repairs, no cost basis, §2 row 18); merging would break both. Revaluation is rare in SME books and adds a reserve model — excluded, documented. |
| DECISION-14 | E-invoice/e-way connectivity | **Direct NIC adapters behind interfaces + fakes; GSP swappable later; credentials per business, encrypted** | Keeps zero per-document vendor cost and full testability; a GSP adds SLA if/when volume justifies it. |
| DECISION-15 | Balance materialisation | **Synchronous upsert of `account_balances` inside the posting transaction; `accounting:rebuild-balances` escape hatch** | At SME volume the hotspot is theoretical; synchronous means reports are never stale and integrity check 3 is meaningful daily. |
| DECISION-16 | Maker-checker scope | **On for manual JVs + CA closing proposals when the business has >1 admin; document postings are exempt** | Documents already carry their own approval semantics; double-keying every invoice would kill adoption. Manual JVs are where fraud lives. |
| DECISION-17 | Money precision & rounding | **Storage `DECIMAL(20,4)` money / `(20,6)` qty-rates; ledger boundary = 2 dp half-up at posting; invoice rupee round-off via ledger 5201; allocations by largest remainder** | Matches GST-portal behaviour (per-line 2 dp taxes), keeps paise exact through allocation, and leaves headroom without float anywhere. |
| DECISION-18 | Public API | **Out of scope for M0–M7** | The competitive table (§2.3) is honest about it; building it before books exist would be decorating an empty ledger. The service layer (posting services with typed inputs) is written API-shaped so a later `routes/api.php` is additive. |

**Open items (each with a working default so nothing blocks):**
1. **Which TallyPrime version to certify export against** — default: latest TallyPrime 6.x available at M6; record the exact version in the M6 DoD note.
2. **E-invoice credentials source for the pilot** — default: NIC sandbox (`einv-apisandbox.nic.in`) until the pilot's GSTIN is production-registered on the IRP; the adapter's base URL is per-business config.
3. **Composition-scheme tenants** — default: supported for books (Bill of Supply, no ITC) from M3, but GSTR-4/CMP-08 builders are **not** in scope; revisit after M7 if such tenants sign up.
4. **Leave/gratuity provisioning (AS-15 style year-end provisions)** — default: handled as CA closing-entry proposals (§3.10.2(c)), not computed by the app in this programme.

---

## 10. Glossary — accounting terms → this codebase

| Term | Plain meaning | In this codebase |
|---|---|---|
| Books of account | The ledger record every statement derives from | `journals` + `journal_lines` (+ registers) |
| Double entry | Every transaction debits one account and credits another, equally | `JournalPostingService` invariant; §3.1.5 three-layer enforcement |
| Debit / Credit | Left/right of an account; assets & expenses grow by debit, liabilities/equity/income by credit | `journal_lines.debit/credit`; `ledger_accounts.normal_balance` |
| Chart of Accounts (CoA) | The tree of accounts money can sit in | `ledger_accounts`, seeded per §3.1.2-3.1.3 |
| Group vs Ledger | Group = folder (can't post), Ledger = leaf (can post) | `is_group` flag; posting guard in service |
| Voucher | One recorded transaction with a number and date | a `journals` row + its lines; types per §3.2 |
| Journal entry / JV | A manually keyed voucher | voucher type `JRN`, maker-checker |
| Narration | The human sentence on a voucher | `journals.narration` |
| Posting / Finalising | Making a document hit the ledger, irreversibly | `finalise()` actions → `JournalPostingService::post()` |
| Reversal | The only legal "undo": an equal-and-opposite voucher | `JournalPostingService::reverse()`; `reversed_by_journal_id` |
| Trial Balance (TB) | List of every account's balance; must sum to zero | `TrialBalanceService`; §3.9.3 |
| P&L / Balance Sheet | Performance for a period / position at a date | `TradingAndPlService`, `BalanceSheetService` |
| Trading account | The gross-profit section: Sales vs cost of goods | §3.3.8 derivation |
| Gross vs Net profit | Before vs after indirect expenses | `affects_gross_profit` flag drives the split |
| COGS | What the *dispatched* goods actually cost | ledger 5101; posted by delivery notes (`CogsPostingService`) |
| Closing stock | Unsold goods at cost (never at sale value) | Stock-in-Hand 1211 = Σ `stock_ledger_entries` |
| Landed cost | Freight/duty added into stock value (the ₹200) | `landed_cost_vouchers` (§3.3.5) |
| Weighted average / FIFO | Ways to decide which cost leaves with each sale | `WeightedAverageEngine` / `FifoEngine` + `stock_lots` |
| NRV | What stock could actually fetch, minus selling cost | `NrvAssessmentService`; AS-2 lower-of test |
| Sundry Debtors / Creditors | Customers who owe you / vendors you owe | groups 1220 / 2420 + party sub-ledgers (§3.1.4) |
| Bill-wise | Tracking which invoice a payment settles | `receipt_allocations`, `vendor_payment_allocations`, `opening_bills` |
| Ageing | How old each unpaid bill is | `AgeingService`; §3.9.11 buckets |
| Advance | Money received/paid before the invoice exists | `Advances from Customers` 2425 / `Employee Advances` 1262 |
| Provision / Accrual | Expense recognised before it's billed/paid | group 2430; `accrual_vouchers` |
| Prepaid | Paid now, expensed month by month | 1263 + `prepaid_schedules` |
| Contra | Cash↔bank movement (no P&L) | voucher `CON` |
| ITC | GST you paid on purchases, usable against sales GST | Input 2415-2418; `gst_ledger_entries.itc_status` |
| Output tax / POS | GST you owe on sales; the state that decides its split | 2411-2414; `PlaceOfSupplyResolver` |
| RCM | Buyer pays the GST instead of the seller | 2421; `RcmService`; self-invoice |
| GSTR-1 / 3B / 2B | Sales return / summary return / purchase-side statement | `Gstr1Builder`, `Gstr3bBuilder`, `gstr2b_lines` |
| IRN / e-way bill | Invoice registration hash / goods-movement permit | `einvoices`, `eway_bills` |
| TDS / TCS | Tax you withhold when paying / collect when selling | `tds_entries`, `TdsEngine` (§3.7); payroll's Sec 192 stays in HRMS |
| Challan 281 | The bank receipt for depositing TDS | `tds_challans` |
| Depreciation (Sch II / IT block) | Companies-Act books leg / income-tax leg | `depreciation_runs` / `it_block_fy_lines` |
| CWIP | Asset under construction, not yet depreciating | CWIP ledger + `fixed_assets.status='cwip'` |
| BRS | Proof bank statement and books agree | `bank_reconciliations` (§3.9.9) |
| Period lock | Freezing a closed month/FY against edits | `accounting_periods.is_locked`; `PeriodGuard` |
| Audit trail | The undisableable who-did-what log (Rule 3(1)) | `audit_trail` + §4.2 triggers |
| Opening balance | Where the old world hands over to the ledger | §7 wizard; suspense 1901 must end at zero |
| Cost centre / Project | Which department / which job a rupee belongs to | `cost_centres`, `projects` + line dimensions |
| Maker-checker | One person keys, another approves | draft/post permissions (§3.11.3) |
| Gapless series | Voucher numbers with no holes (GST expects this) | `voucher_sequences` + DECISION-02 |
| Round off | The paise dropped to print a whole-rupee invoice | ledger 5201; §3.6.4 |
| Suspense | Temporary parking for unexplained differences | 1900/1901 — must trend to zero, watched by integrity |
