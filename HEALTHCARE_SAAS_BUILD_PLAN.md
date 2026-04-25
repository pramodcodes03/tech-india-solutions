# Healthcare SaaS Platform — Complete Build Plan (Claude Code Prompt)

> **Instructions for Claude Code (receiving this prompt):**
> This document is the ground truth for building a multi-tenant, multi-specialty healthcare SaaS platform. Execute strictly phase-by-phase. **Do not skip phases.** At the end of each phase, verify all acceptance criteria listed before moving to the next. After every phase, commit to git with a clear message and pause for user review. Use Laravel 12 LTS or the latest stable. Prefer convention over configuration. Favour small, testable units. Write PHPUnit/Pest tests for every service class.

---

## 0. Business Context (read this before writing any code)

### 0.1 Product
**ClinicOS** — a multi-specialty healthcare management SaaS for Indian clinics, diagnostic labs, small hospitals, and chains. One platform, one codebase, multi-tenant, multi-specialty configurable.

### 0.2 Target customers
| Segment | India count | ARPU/month | Target |
|---|---|---|---|
| Dental clinics | 2.5 L | ₹3-8K | 50K customers by Y5 |
| Pathology labs / Diagnostic centers | 1.5 L | ₹5-15K | 15K |
| General OPD / Multi-specialty clinics | 3 L | ₹3-10K | 30K |
| Physiotherapy / Ayurveda / Homeopathy | 2 L | ₹2-5K | 20K |
| Small hospitals / Nursing homes (10-50 beds) | 70K | ₹15-50K | 5K |
| Eye / ENT / Skin specialty chains | 50K | ₹5-15K | 5K |

**5-year goal:** 60,000 paying tenants, ~₹40 Cr MRR / ₹480 Cr ARR, 1 lakh+ platform users.

### 0.3 Pricing Tiers
- **Starter (Solo Clinic):** ₹2,500-₹5,000/month — 1 practitioner, 1 location, core + 1 specialty, 500 WhatsApp msgs
- **Growth (Multi-Doctor / Diagnostic):** ₹8,000-₹20,000/month — up to 10 practitioners, 1-2 locations, all specialties, 5K WhatsApp, insurance, ABHA
- **Enterprise (Hospital / Chain):** ₹30,000-₹2,00,000/month — unlimited users, multi-branch, IPD+OT+pharmacy, dedicated support, custom integrations
- **Add-ons:** Pharmacy POS (₹1K/m), extra WhatsApp (₹0.50/msg), SMS (₹0.25/msg), ABHA (₹500/branch/month), payment gateway commission (0.5%), white-label patient app (₹2K/month)

### 0.4 Differentiators (non-negotiable features)
1. **Regional language UI**: Marathi, Hindi, Gujarati, Tamil, Kannada, Telugu, Bengali, English
2. **WhatsApp-first patient communication**: no patient app install needed
3. **ABDM / ABHA native integration**
4. **Tier-2/3 pricing** (starter at ₹3K/month)
5. **GST + Indian TPA (insurance) workflows** built-in
6. **Multi-specialty toggleable** (not forked per specialty)

---

## 1. Tech Stack & Prerequisites

### 1.1 Stack (strict)
- **Framework:** Laravel 12 LTS (or latest stable)
- **PHP:** 8.3+
- **Database:** MySQL 8.0+ (one central DB + one DB per tenant)
- **Cache/Queue:** Redis
- **Multi-tenancy:** **`stancl/tenancy` (https://tenancyforlaravel.com/)** — database-per-tenant mode, domain-based identification
- **Frontend:** Blade + Alpine.js + Tailwind CSS 3.x (match pattern from source project)
- **Auth:** Laravel built-in + Spatie Permission
- **Activity log:** Spatie Activitylog
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** maatwebsite/excel
- **Testing:** Pest PHP
- **Queue worker:** Supervisor
- **Assets:** Vite
- **Icons:** Heroicons (inline SVG)
- **File storage:** S3 (AWS Mumbai) via `league/flysystem-aws-s3-v3`
- **WhatsApp:** Gupshup / Interakt / WATI API (plug one behind an interface)
- **SMS:** MSG91
- **Email:** Amazon SES
- **Payments:** Razorpay (gateway) + Cashfree (alt)
- **Video (tele-consult):** Daily.co or Jitsi
- **ABDM/ABHA:** Official ABDM Sandbox API
- **Search:** Laravel Scout + Meilisearch (optional Phase 5)

### 1.2 Existing assets to copy
Source project: `/var/www/tech_school_erp/html`
Destination: `/var/www/healthcare`

Copy **only the visual theme + reusable Blade components** — not the business logic. Specifically:
- `resources/views/components/admin/*` (sidebar, header, breadcrumb, data-table, searchable-select, empty-state, footer)
- `resources/views/components/layout/*` (admin.blade.php, auth.blade.php)
- `resources/css/app.css` (and any referenced Tailwind config)
- `tailwind.config.js`, `postcss.config.js`, `vite.config.js`
- `public/assets/*` (fonts, icons, Alpine/Perfect Scrollbar JS)
- Admin login page design (`resources/views/admin/auth/login.blade.php`) — adapt branding
- `resources/views/components/layout/employee.blade.php` + `resources/views/components/employee/*` — will become the **patient / tenant-user portal layout**

Do **not** copy:
- Any HRMS-specific controllers/models
- ERP-specific controllers (sales, purchase, invoices, quotations, payments, leads)
- Any business-domain seeders

---

## 2. Multi-Tenancy Model

### 2.1 Architecture
- **Central database** — tenant registry, subscriptions, billing, plans, superadmin users, global settings
- **Per-tenant database** — each clinic/lab/hospital's patients, appointments, bills, staff, etc.
- **Domain identification:** `{slug}.clinicos.in` + optional custom domains
- **Tenant provisioning:** signup → stripe-style onboarding → creates DB → runs tenant migrations → seeds defaults → sends welcome email

### 2.2 Central DB tables (global)
- `tenants` (id, slug, name, practice_type, plan_id, trial_ends_at, status, data JSON)
- `tenant_domains`
- `plans` (id, name, price_monthly, price_annual, features JSON, limits JSON)
- `subscriptions` (tenant_id, plan_id, started_at, ends_at, status, payment_gateway_id)
- `payments` (subscription_id, gateway, amount, status, meta JSON)
- `super_admins` (internal staff who support tenants)
- `system_settings` (branding, feature flags, announcement banners)
- `feature_flags` (for gradual rollout per-tenant or globally)
- `webhook_deliveries` (log of outbound webhook calls)
- `support_tickets` (tenant ↔ platform support)
- `audit_logs_global` (cross-tenant audit for support actions)

### 2.3 Tenant DB tables (per clinic)
See Phase 2-6 for full list. Every table inherits `created_by`, `updated_by`, `deleted_by`, timestamps + soft deletes where it makes sense.

### 2.4 Request lifecycle
```
Request → hits {slug}.clinicos.in
  → stancl/tenancy middleware reads slug
  → loads tenant record from central DB
  → switches DB connection to tenant's DB
  → route middleware: auth + role/permission
  → controller runs with tenant context
```

### 2.5 Jobs / queues
- Central queue: billing, signup, provisioning, emails to prospects
- Tenant queue: WhatsApp sends, SMS, report generation, nightly aggregations
- Use `stancl/tenancy` built-in job tagging so queued jobs execute in the right tenant context

---

# BUILD PHASES

## PHASE 0 — Project Setup

### 0.1 Scaffold fresh Laravel project
```bash
cd /var/www
composer create-project laravel/laravel healthcare
cd healthcare
```

### 0.2 Install core packages
```bash
composer require stancl/tenancy
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require league/flysystem-aws-s3-v3 "^3.0"
composer require laravel/scout
composer require laravel/tinker
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
composer require --dev laravel/pint nunomaduro/collision
```

### 0.3 Copy theme assets from source project
```bash
# From /var/www/tech_school_erp/html to /var/www/healthcare
cp -r /var/www/tech_school_erp/html/resources/css ./resources/
cp /var/www/tech_school_erp/html/tailwind.config.js ./
cp /var/www/tech_school_erp/html/postcss.config.js ./
cp /var/www/tech_school_erp/html/vite.config.js ./
cp /var/www/tech_school_erp/html/package.json ./
cp -r /var/www/tech_school_erp/html/public/assets ./public/

# Copy Blade components (selectively)
mkdir -p resources/views/components/layout
mkdir -p resources/views/components/admin
cp /var/www/tech_school_erp/html/resources/views/components/layout/admin.blade.php ./resources/views/components/layout/
cp /var/www/tech_school_erp/html/resources/views/components/layout/auth.blade.php ./resources/views/components/layout/
cp /var/www/tech_school_erp/html/resources/views/components/admin/breadcrumb.blade.php ./resources/views/components/admin/
cp /var/www/tech_school_erp/html/resources/views/components/admin/searchable-select.blade.php ./resources/views/components/admin/
cp /var/www/tech_school_erp/html/resources/views/components/admin/data-table.blade.php ./resources/views/components/admin/
cp /var/www/tech_school_erp/html/resources/views/components/admin/empty-state.blade.php ./resources/views/components/admin/
cp /var/www/tech_school_erp/html/resources/views/components/admin/footer.blade.php ./resources/views/components/admin/
# sidebar + header will be rewritten for healthcare navigation

npm install
npm run build
```

### 0.4 Configure multi-tenancy
```bash
php artisan tenancy:install
```

This publishes:
- Central migrations for `tenants` + `domains`
- `config/tenancy.php`

**Key config changes in `config/tenancy.php`:**
- Bootstrappers: database, cache, filesystem, queue, redis — all enabled
- Database: `template_tenant_connection` — use a template DB approach for fast provisioning
- Domain identification: `tenancy.identification_middleware` = `InitializeTenancyByDomainOrSubdomain::class`
- Seed tenant migrations from `database/migrations/tenant/`

### 0.5 Directory structure
```
/var/www/healthcare
├── app/
│   ├── Central/              # Central DB models & controllers (tenants, plans, billing)
│   │   ├── Models/
│   │   ├── Http/Controllers/
│   │   └── Services/
│   ├── Tenant/               # Per-tenant application
│   │   ├── Models/
│   │   ├── Http/Controllers/
│   │   │   ├── Admin/        # Tenant admin (doctor/receptionist)
│   │   │   ├── Patient/      # Patient self-service portal
│   │   │   └── Api/
│   │   ├── Services/
│   │   └── Policies/
│   ├── Support/              # Cross-cutting helpers
│   └── Integrations/         # WhatsApp, SMS, ABDM, Razorpay wrappers
├── database/
│   ├── migrations/           # Central DB migrations
│   ├── migrations/tenant/    # Per-tenant migrations
│   └── seeders/
│       ├── CentralSeeder.php
│       └── TenantSeeder.php
├── resources/
│   └── views/
│       ├── central/          # Landing page, signup, superadmin
│       ├── tenant/           # Tenant admin portal
│       └── patient/          # Patient self-service portal
└── routes/
    ├── web.php               # Central routes
    ├── tenant.php            # Tenant admin routes
    └── patient.php           # Patient portal routes
```

### 0.6 Acceptance criteria for Phase 0
- [ ] `php artisan migrate` on central DB creates `tenants`, `domains`, and auth tables
- [ ] Can create a test tenant via tinker: `Tenant::create(['id' => 'demo']); $t->domains()->create(['domain' => 'demo.clinicos.test']);`
- [ ] Tenant creation triggers tenant migrations (can verify by connecting to the tenant DB)
- [ ] Visiting `demo.clinicos.test` switches DB context (add temp `dd(DB::getDatabaseName())` to confirm)
- [ ] Vite builds CSS and assets load
- [ ] Copied Blade components render without errors (create a test route)

### 0.7 Commit
`feat(phase-0): fresh laravel project with multi-tenancy scaffolding and theme assets`

---

## PHASE 1 — Central Platform (Landing, Signup, Superadmin)

### 1.1 Landing page (public, marketing)
- Hero: "ClinicOS — India's multi-specialty clinic OS"
- Sections: Features, Specialties, Pricing (3 tiers), Testimonials, FAQ, Footer
- CTA: "Start 14-day free trial" → signup flow
- Stack: Tailwind + Alpine, mobile-first, SEO-optimised

### 1.2 Signup flow (multi-step wizard)
**Step 1 — Practice info:**
- Clinic name, owner name, owner email, mobile (with OTP verification via MSG91)
- Practice type: Dental / General / Pathology / Ayurveda / Physio / Multi-specialty / Hospital
- Number of practitioners (dropdown), number of locations
- Subdomain preference (slug validation — alphanumeric, 3-30 chars, blocklist: www, api, app, admin, support)

**Step 2 — Plan selection:**
- Show 3 plans with toggle for monthly/annual (20% off annual)
- Start 14-day free trial on any plan (credit card not required for trial)

**Step 3 — Onboarding:**
- Create tenant record in central DB
- Create tenant domain `{slug}.clinicos.in`
- Create tenant DB, run tenant migrations
- Seed tenant defaults (admin user = owner, default specialty modules enabled per practice type, default treatment catalog)
- Send welcome email + WhatsApp with login link
- Redirect to `{slug}.clinicos.in/admin` with first-login setup wizard

### 1.3 Central models (app/Central/Models)
- `Tenant` (extends stancl/tenancy Tenant) with fillable: slug, name, owner_email, owner_mobile, practice_type, plan_id, trial_ends_at, billing_cycle, status, locale, timezone, currency, meta (JSON)
- `Plan` — name, slug, price_monthly, price_annual, limits (practitioners, patients/month, whatsapp_msgs, branches), features (JSON array), active
- `Subscription` — tenant_id, plan_id, started_at, current_period_end, cancel_at_period_end, status (trialing/active/past_due/canceled), razorpay_subscription_id
- `Payment` — subscription_id, gateway, gateway_payment_id, amount, currency, status, paid_at, receipt_url, meta JSON
- `SuperAdmin` — platform staff, full DB access for support
- `SupportTicket` — tenant_id, subject, status, priority, messages (JSON array with sender + body + timestamp)
- `FeatureFlag` — flag_key, enabled_globally, tenant_overrides JSON
- `SystemSetting` — key/value singleton pairs

### 1.4 Central migrations
Create in `database/migrations/`:
- Standard Laravel `users` (for SuperAdmins — rename table to `super_admins` OR use `type` column)
- `plans`
- Tenant tenancy package's `tenants` + `domains` (published by install)
- `subscriptions`, `payments`
- `support_tickets` + `support_ticket_messages`
- `feature_flags`
- `system_settings`
- `webhook_deliveries`

### 1.5 Central controllers
- `LandingController` — home, features, pricing, about, contact
- `Auth/SignupController` — multi-step signup with OTP
- `Auth/SuperAdminAuthController` — login for platform staff
- `SuperAdmin/DashboardController` — platform metrics
- `SuperAdmin/TenantController` — list, view, suspend, impersonate
- `SuperAdmin/PlanController` — CRUD plans
- `SuperAdmin/SubscriptionController` — view all subs, revenue
- `SuperAdmin/PaymentController` — payments + refunds
- `SuperAdmin/SupportController` — ticket inbox
- `Billing/WebhookController` — Razorpay webhook → update subscription status
- `Billing/CheckoutController` — initiate payment for upgrade / renewal

### 1.6 Superadmin portal
- Route prefix: `/superadmin`, guarded by `auth:super_admin`
- Pages:
  - Dashboard (MRR, ARR, churn rate, tenant count by plan, signups this month, active vs trialing)
  - Tenants list (search, filter by plan/status, view details, impersonate, suspend)
  - Tenant detail (DB size, last activity, subscription history, support tickets, raw data browser)
  - Plans management
  - Payments log + refund interface
  - Support ticket inbox
  - Feature flag toggles
  - Announcements (cross-tenant banner)
  - Revenue reports

### 1.7 Seed data
```php
// CentralSeeder
Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 3000, 'price_annual' => 30000, 'limits' => [...]]);
Plan::create(['name' => 'Growth', 'slug' => 'growth', 'price_monthly' => 10000, 'price_annual' => 100000, 'limits' => [...]]);
Plan::create(['name' => 'Enterprise', 'slug' => 'enterprise', 'price_monthly' => 40000, 'price_annual' => 400000, 'limits' => [...]]);

SuperAdmin::create(['name' => 'Platform Admin', 'email' => 'admin@clinicos.in', 'password' => Hash::make('...')]);
```

### 1.8 Acceptance criteria for Phase 1
- [ ] Landing page loads on main domain with all sections
- [ ] Signup wizard with OTP works end-to-end
- [ ] New signup creates tenant, runs migrations, seeds defaults, redirects to tenant subdomain
- [ ] SuperAdmin can log in at `/superadmin`, see all tenants, impersonate
- [ ] Plans CRUD works
- [ ] Razorpay checkout initiates payment (use test keys)
- [ ] Webhook correctly updates subscription status on payment success

### 1.9 Commit
`feat(phase-1): central platform with landing, signup, billing and superadmin`

---

## PHASE 2 — Tenant Core Platform (the 80% every healthcare provider needs)

### 2.1 Tenant-level user system
- `users` table (per-tenant) — tenant staff: owners, doctors, receptionists, nurses, lab techs, pharmacists, managers
- Columns: name, email, mobile, password, role, active, branch_id, specialty, registration_number (NMC/MCI for doctors), signature_image, meta JSON
- Uses Spatie Permission for fine-grained access

### 2.2 Branches (multi-location)
- `branches` — name, code, address, phone, gst_number, pan, drug_license, gps_lat, gps_lng, timezone, is_active
- Every subsequent business record has `branch_id` FK

### 2.3 Roles & Permissions (per-tenant)
Default roles seeded on tenant creation:
- **Owner** — all permissions
- **Doctor** — patient view/edit, appointments, prescriptions, lab reports view, treatment plans
- **Receptionist** — appointments, patient creation, billing, payments, no prescriptions
- **Nurse** — vitals entry, patient notes, medication administration, no billing
- **Lab Technician** — lab orders, sample collection, report entry
- **Pharmacist** — pharmacy POS, inventory, no patient notes
- **Accountant** — billing, payments, GST reports, no medical records

Permission granularity example:
- `patients.view`, `patients.create`, `patients.edit`, `patients.delete`, `patients.export`
- `appointments.view`, `appointments.create`, `appointments.edit`, `appointments.delete`, `appointments.cancel`
- `prescriptions.view_own`, `prescriptions.view_all`, `prescriptions.create`, `prescriptions.edit`
- `billing.view`, `billing.create`, `billing.edit`, `billing.delete`, `billing.discount`
- Similar for every module

### 2.4 Patient registry (the heart of the platform)
Migration: `patients`
- `uhid` (Unique Health ID, auto-generated: `{BRANCH}-{YYYY}-{0001}`)
- `abha_id` (nullable, Ayushman Bharat Health Account)
- `title` (Mr/Mrs/Ms/Dr/Master/Miss/Baby)
- `first_name`, `last_name`
- `date_of_birth`, `age_in_years_at_reg`, `age_in_months_at_reg`
- `gender` (male/female/other/not_disclosed)
- `mobile`, `alt_mobile`, `email`
- `whatsapp_opt_in` (boolean, default true)
- `sms_opt_in`, `email_opt_in`
- `preferred_language` (for WhatsApp/SMS)
- `blood_group`
- `marital_status`
- `occupation`
- Address: `address_line_1`, `address_line_2`, `city`, `state`, `pincode`, `country`
- `photo_path`
- `aadhaar_last_4` (only last 4, never store full)
- `father_name`, `mother_name`, `spouse_name` (culturally important for Indian healthcare)
- `emergency_contact_name`, `emergency_contact_relation`, `emergency_contact_phone`
- Clinical: `allergies` (JSON array), `chronic_conditions` (JSON array), `current_medications` (JSON array), `past_surgeries` (JSON array), `family_history` (JSON array)
- `smoking_status`, `alcohol_status` (never/occasional/regular), `tobacco_chewing`
- `height_cm`, `weight_kg` (latest; full history in vitals table)
- `primary_doctor_id` (FK users)
- `referred_by_type` (self/doctor/friend/ad/walk-in/camp), `referred_by_name`, `referred_by_id` (nullable FK to referring_doctors)
- `registered_at`, `registered_by` (FK users), `branch_id`
- `is_vip`, `notes_internal` (HR notes not visible to patient)
- `status` (active/inactive/deceased/transferred)
- Soft deletes
- Indexes on mobile, uhid, abha_id, branch_id, primary_doctor_id

### 2.5 Patient relations
- `patient_family_links` — link UHIDs (e.g. father-daughter for pediatric history)
- `patient_insurance_policies` — tpa_id, policy_number, coverage, valid_from, valid_till, balance_remaining
- `patient_documents` — uploaded ID proofs, old reports (PDF, images), doc_type, expires_at, uploaded_by
- `patient_vitals` — height, weight, BP, pulse, temp, SpO2, BMI (calculated), recorded_at, recorded_by

### 2.6 Appointment engine
Migration: `appointment_resources` (chairs, beds, consulting rooms, machines)
- branch_id, name, resource_type (chair/bed/room/machine), capacity, color, is_active

Migration: `appointment_slots` — working hours template per practitioner
- user_id, branch_id, day_of_week (0-6), start_time, end_time, slot_duration_minutes, break_start, break_end

Migration: `appointments`
- appointment_code (auto: `A-{YYYYMM}-{0001}`)
- patient_id, user_id (doctor/practitioner), branch_id, resource_id (chair/room)
- scheduled_start, scheduled_end, actual_start, actual_end
- visit_type (new/follow-up/emergency/tele-consult)
- chief_complaint, reason_for_visit
- status (scheduled/confirmed/arrived/in-progress/completed/cancelled/no-show)
- cancelled_by_type (patient/staff), cancel_reason
- source (walk-in/phone/online/whatsapp/referral/past-patient)
- referring_doctor_id
- reminder_sent_24h, reminder_sent_2h (booleans)
- confirmation_sent_at, reminder_sent_at
- Indexes on scheduled_start, patient_id, user_id, branch_id, status

Migration: `appointment_waiting_list` — when requested slot unavailable

**Controller features:**
- Calendar view (day, week, month) — resource-wise, practitioner-wise, patient-centric
- Drag-and-drop reschedule
- Conflict detection (no double-booking same resource or practitioner)
- Recurring appointments (e.g. physio thrice weekly for 4 weeks)
- Online patient booking widget — embeddable iframe or link for clinic's website/Google Business
- No-show tracking + auto-blacklist after 3 no-shows
- Token system for walk-ins (queue number)
- Waiting room display (TV screen mode showing "Now serving: Token 7 — Dr. Sharma")

### 2.7 Billing engine
Migration: `treatment_catalog` — services offered (reshape of product concept)
- code, name, name_regional (JSON by locale), category_id (service_categories), default_price, gst_rate (0/5/12/18), specialty_filter, duration_minutes, is_active
- Specialty-filter lets dental-only items hide from OPD, etc.

Migration: `service_categories` — Consultation, Diagnostic, Surgery, Dental, Pharmacy, etc.

Migration: `treatment_packages` — bundles (e.g. "10 physio sessions")
- name, total_sessions, price, validity_days

Migration: `patient_treatment_plans` — multi-visit plans (dental/ortho)
- patient_id, treating_doctor_id, plan_name, total_cost, status (active/completed/cancelled), started_at, completed_at
- Has many `patient_treatment_plan_items` — treatment_catalog_id, planned_date, performed_at, tooth_numbers (for dental)

Migration: `invoices`
- invoice_number (auto), patient_id, branch_id, appointment_id (nullable), treatment_plan_id (nullable)
- invoice_date, due_date
- subtotal, discount_type (percent/fixed), discount_value, discount_amount, tax_amount, grand_total
- paid_amount, balance_due
- status (draft/sent/partial/paid/cancelled/refunded)
- payment_terms, notes, internal_notes
- gst_treatment (registered/unregistered/consumer/overseas), place_of_supply
- created_by, updated_by, cancelled_by, cancel_reason

Migration: `invoice_items`
- invoice_id, treatment_catalog_id (nullable — for ad-hoc)
- description, hsn_sac_code, quantity, unit, rate, discount_percent, discount_amount, tax_rate, tax_amount, line_total
- performed_by (user_id), performed_at
- tooth_number (for dental)
- notes

Migration: `payments`
- payment_number, patient_id, invoice_id, branch_id
- paid_at, amount, payment_method (cash/upi/card/netbanking/wallet/cheque/bank_transfer)
- gateway (razorpay/cashfree/manual), gateway_transaction_id, gateway_reference
- status (pending/captured/failed/refunded)
- received_by (user_id)
- notes

Migration: `refunds`
- payment_id, amount, reason, refunded_at, refunded_by, gateway_refund_id, status

Migration: `insurance_claims` — TPA workflow
- patient_id, invoice_id, tpa_id, policy_number, claim_number, admission_date, discharge_date
- claim_amount, approved_amount, settlement_amount
- status (pre_auth_pending/pre_auth_approved/claim_submitted/settled/rejected)
- documents (JSON array of paths)
- coordinator (user_id), timeline (JSON audit trail)

**Billing features:**
- Multi-rate treatment catalog with GST rates
- Package pricing + session tracking
- Partial payment acceptance with auto-balance tracking
- Advance payment (deposit before treatment)
- Refund workflow
- Cashless (TPA) invoicing — split between patient copay and TPA claim
- GST-compliant invoice PDF (different templates: B2C, B2B, interstate, composition scheme)
- Auto-generate receipts via WhatsApp
- Branch-wise GSTIN handling
- Daily collection report + shift-wise receptionist collections

### 2.8 Prescription builder
Migration: `drugs_master` (pre-loaded with 50K+ Indian drugs)
- generic_name, brand_name, manufacturer, dosage_form (tablet/syrup/injection/topical), strength, pack_size, schedule (H/H1/X/OTC), is_scheduled
- Loaded from public CDSCO data (Central Drugs Standard Control Organisation) — seed one-time from a CSV

Migration: `prescription_templates` — doctor's common prescriptions
- user_id (doctor), name, diagnosis, items (JSON array), advice, diet, follow_up_days

Migration: `prescriptions`
- prescription_number, patient_id, doctor_id, appointment_id, branch_id
- prescribed_at, diagnosis (free-text + ICD-10 codes JSON)
- chief_complaints, examination_findings (JSON)
- advice, diet_plan, activity_restriction
- follow_up_in_days, follow_up_on_date
- doctor_signature_applied (boolean), signed_at
- is_digital (for ABDM)
- status (draft/finalized/cancelled)

Migration: `prescription_items`
- prescription_id, drug_id (nullable for free-text), drug_name, strength, dosage_form
- frequency (1-1-1 style or custom), duration_days, quantity, route (oral/IV/topical), timing (before_meal/after_meal/empty_stomach)
- instructions (free text), substitution_allowed, is_continuation

**Features:**
- Drug autocomplete with brand ↔ generic toggle
- Dose calculator (pediatric weight-based)
- Allergy warnings (compare with patient's allergies)
- Drug-drug interaction checker (integrate with a drug interaction API or bundled DB)
- Template system (doctor saves "Fever Standard" once, reuses forever)
- Print on clinic letterhead (WYSIWYG preview)
- Send via WhatsApp as PDF
- Patient can share with pharmacy

### 2.9 Inventory + Pharmacy
Migration: `inventory_categories`
Migration: `products` (drugs, consumables, non-drug items)
- code, name, category_id, drug_id (nullable, links to drugs_master), unit, hsn_sac, tax_rate
- is_batch_tracked (boolean), is_expiry_tracked (boolean)
- reorder_level, is_active
- manufacturer, mrp, cost_price, sale_price

Migration: `product_batches`
- product_id, batch_number, manufacturing_date, expiry_date, mrp, cost, is_active

Migration: `warehouses` — branch storage areas

Migration: `stock`
- product_id, branch_id, batch_id, warehouse_id, quantity_on_hand

Migration: `stock_movements`
- product_id, branch_id, batch_id, warehouse_id, quantity (signed), type (purchase/sale/adjustment/transfer/return/damage/expiry), reference_type (pharmacy_sale/consumption/PO/GRN), reference_id, notes, moved_by, moved_at

Migration: `vendors` — suppliers
Migration: `purchase_orders` + `purchase_order_items`
Migration: `goods_receipts` + `goods_receipt_items` — vendor deliveries
Migration: `pharmacy_sales` + `pharmacy_sale_items` — standalone pharmacy POS (walk-in without appointment)

**Features:**
- FEFO picking (first-expiry-first-out)
- Expiry alerts dashboard (expiring in 30/60/90 days)
- Auto-reorder suggestions based on consumption velocity
- Pharmacy POS with barcode scan
- Vendor management + pending POs
- Narcotic / Schedule H1 drug register (legal compliance)
- Stock transfer between branches

### 2.10 Staff management (simplified HR — NOT the full HRMS, just what a clinic needs)
Migration: `users` already covers profile
Migration: `user_attendance` — daily punch in/out (manual or biometric CSV import)
Migration: `user_leaves` — simple leave request/approve
Migration: `user_commissions` — auto-calculate doctor/staff commission per treatment

Commission rules stored in `commission_rules`:
- scope (user_id / role / all), treatment_catalog_id (nullable, otherwise all), commission_type (percent/fixed), commission_value, effective_from, effective_to

**Commission engine:**
- Every invoice_item triggers commission calculation based on applicable rule
- Monthly commission report per doctor/staff
- Referring doctor commission (external doctor who referred patient) — tracked separately

### 2.11 Reports & analytics
- Daily: Collection by branch, by doctor, by payment method
- Monthly: Revenue trend, top treatments, top doctors, patient acquisition source
- GST: GSTR-1, GSTR-3B export (CSV + JSON for offline utility)
- Patient analytics: new vs returning, age/gender distribution, geo heatmap
- Inventory: stock valuation, expiring, dead stock, turnover ratio
- Commission: user-wise, referral-wise
- Dashboard KPI tiles + charts (Chart.js)

### 2.12 Common admin pages
- Settings (branches, GST details, letterheads, PDF templates, working hours)
- User management (staff CRUD, role assignment)
- Treatment catalog CRUD
- Vendor CRUD
- Activity log viewer (Spatie Activitylog UI)
- System backup download (patient data export for compliance)

### 2.13 Acceptance criteria for Phase 2
- [ ] Create patient, schedule appointment, bill, take payment — full happy path works
- [ ] Prescription with drug autocomplete, allergy warning, PDF print works
- [ ] Pharmacy POS can sell OTC without patient linkage
- [ ] Multi-branch tenant: user restricted to their branch sees only their branch data
- [ ] GSTR-1 export matches manual calculations
- [ ] Role-based access: receptionist cannot see prescription details, doctor cannot issue refunds, etc.
- [ ] Audit log captures every patient record access

### 2.14 Commit
`feat(phase-2): tenant core platform — patients, appointments, billing, prescriptions, pharmacy`

---

## PHASE 3 — Communication Layer (WhatsApp, SMS, Email) — THE MOAT

### 3.1 Abstraction
Create `app/Integrations/Messaging/MessagingGateway.php` interface:
```php
interface MessagingGateway {
    public function sendWhatsApp(string $to, string $templateKey, array $vars, ?string $mediaUrl = null): MessageResult;
    public function sendSms(string $to, string $templateKey, array $vars): MessageResult;
    public function sendEmail(string $to, string $subject, string $templateKey, array $vars, array $attachments = []): MessageResult;
}
```

Implementations:
- `GupshupWhatsAppGateway`
- `InteraktWhatsAppGateway`
- `MSG91SmsGateway`
- `SESEmailGateway`

Gateway selection is tenant-configurable (stored in tenant settings).

### 3.2 Templates
Migration: `message_templates`
- scope (global/tenant), key (e.g. `appointment_reminder_24h`), channel (whatsapp/sms/email)
- subject (for email), body (with `{{variables}}`), language_code, media_url (optional)
- approved_provider_template_id (WhatsApp requires pre-approved templates)

Seed global templates for:
- `appointment_confirmation` — "Hi {{patient_name}}, your appointment with Dr. {{doctor_name}} is confirmed for {{date_time}} at {{branch_name}}. Reply RESCHEDULE to change."
- `appointment_reminder_24h`
- `appointment_reminder_2h`
- `appointment_cancelled`
- `bill_generated` — sends PDF invoice link
- `payment_received` — receipt
- `prescription_ready` — PDF link
- `lab_report_ready` — PDF link
- `post_visit_feedback` — star rating link
- `birthday_wish`
- `follow_up_reminder`
- `outstanding_payment_reminder`
- `otp_verification`
- `new_patient_welcome`
- `health_tip_weekly` (opt-in)
- Multi-language: provide all templates in 8 Indian languages

### 3.3 Delivery engine
Migration: `message_logs`
- tenant_id, scope (patient/staff/marketing), channel, provider, template_key
- to_name, to_number, to_email
- variables JSON, rendered_body, rendered_subject
- sent_at, delivered_at, read_at, failed_at, error_message
- provider_message_id, cost_paise
- related_type (Morph: Appointment/Invoice/Prescription/Patient), related_id

**Features:**
- Rate limiting (avoid spam flags from Meta)
- Opt-out honoring (patient can opt out globally or per-channel)
- Webhook from providers for delivery/read status
- Analytics: sent vs delivered vs read, channel cost, ROI of reminders (show-up rate improvement)

### 3.4 Schedulers
Via Laravel Scheduler:
- Hourly: Check appointments 24h/2h away, fire reminders
- Daily: Outstanding payment reminders (>30 days), birthday wishes, follow-up due reminders
- Weekly: Health tips campaign (opt-in only)
- Monthly: Patient re-engagement (hasn't visited in 6+ months)

### 3.5 Two-way inbox (receptionist chat)
- Patient replies to WhatsApp → inbound webhook → stored in `conversations` + `conversation_messages`
- Receptionist sees inbox in admin portal, can reply using pre-approved templates or within 24h service window (free-form text)
- Internal notes on conversations
- Auto-route to branch by patient's registered branch

### 3.6 Acceptance criteria for Phase 3
- [ ] WhatsApp appointment reminder sent successfully to sandbox number
- [ ] Delivery/read webhooks update message_logs
- [ ] Patient can reply "RESCHEDULE" and conversation appears in receptionist inbox
- [ ] Opt-out from patient's WhatsApp ("STOP") updates their preference
- [ ] Per-tenant gateway config switches provider correctly

### 3.7 Commit
`feat(phase-3): whatsapp, sms, email communication layer with inbox`

---

## PHASE 4 — Specialty Modules (Toggleable)

Each specialty module is enabled per-tenant based on `tenants.practice_type` and custom toggles in `tenant_feature_flags`. **One codebase, all modules, config-driven visibility.**

### 4.1 🦷 Dental Module

#### Tables
- `dental_tooth_chart_entries` — patient_id, tooth_number (FDI: 11-48), surface (M/O/D/B/L), condition (healthy/cavity/restored/root_canal/crown/extracted/implant/missing/pontic), recorded_at, recorded_by, notes, treatment_catalog_id (if treated)
- `dental_treatment_plans` — extends patient_treatment_plans with `teeth_involved` JSON
- `dental_lab_jobs` — for crowns/bridges: patient_id, lab_name, sent_date, expected_date, received_date, tooth_numbers, shade, impression_type, total_cost, status
- `dental_xrays` — IOPA / OPG / CBCT uploads with annotations

#### Features
- Interactive tooth chart (SVG-based, clickable, color-coded)
- Treatment planning on tooth chart
- Crown/bridge lab tracking
- X-ray viewer with annotation tools (draw circles, arrows, text notes)
- Dental-specific treatment catalog (RCT, Scaling, Filling, Extraction, Implant, Crown, Veneer, Orthodontic bracket, Aligner, etc.)
- Orthodontic progress photos (monthly comparison)
- Periodontal chart (pocket depth per surface)

### 4.2 🧪 Pathology Lab Module

#### Tables
- `lab_test_catalog` — code (e.g. CBC), name, category, specimen_type (blood/urine/stool/swab), container, price, tat_hours (turn around time), is_outsourced, outsource_lab_id
- `lab_test_parameters` — test_id, parameter_name, unit, reference_range_male, reference_range_female, reference_range_pediatric, reference_notes, decimal_places, is_calculated, formula (for derived values)
- `lab_panels` — bundles of tests (e.g. "Health Check Basic" = CBC + LFT + Lipid)
- `lab_orders` — patient_id, doctor_id (internal or external), ordered_at, expected_collection_at, collection_type (in_house/home/pickup), priority (routine/urgent/stat)
- `lab_order_items` — order_id, test_id, status (ordered/collected/processing/reported/delivered)
- `lab_samples` — sample_id (barcode), order_item_id, collected_at, collected_by, received_at, received_by, status
- `lab_results` — order_item_id, parameter_id, value, flag (low/normal/high/critical), method, entered_by, entered_at, verified_by, verified_at
- `lab_reports` — order_id, generated_at, report_pdf_path, doctor_signature_id, delivery_method (email/whatsapp/print/portal), delivered_at
- `home_collection_visits` — order_id, phlebotomist_id, address, slot, route_sequence, status
- `outsource_labs` — external labs for tests not done in-house
- `referring_doctors` — external doctors (non-staff) who refer, with commission
- `b2b_accounts` — corporate clients (companies buying annual health checks for employees)

#### Features
- Test catalog with 1500+ pre-loaded tests (NABL-aligned reference ranges)
- Report template designer (per test) — WYSIWYG
- Sample barcode generation + printing
- Barcode scan for sample receipt and processing
- Auto-flag abnormal values (highlight in red, show reference range)
- Doctor's digital signature on reports
- Home collection module: phlebotomist schedule, route optimization, payment on collection
- Outsourced test tracking (send to partner lab, receive result, pass-through)
- Referring doctor commission (percentage of lab revenue)
- B2B corporate module: employee list, test package, invoice per month
- Franchise / collection-center management (satellite centers that feed main lab)
- Report delivery via WhatsApp (password-protected PDF)

### 4.3 🏥 Hospital / Nursing Home Module (IPD)

#### Tables
- `wards` — branch_id, name, type (general/private/deluxe/ICU/NICU/PICU/HDU)
- `beds` — ward_id, bed_number, gender_allocation (male/female/any), is_occupied, current_admission_id
- `admissions` — admission_number, patient_id, admission_date, admitting_doctor_id, ward_id, bed_id, admission_type (planned/emergency/day_care), diagnosis_on_admission, estimated_stay_days
- `admissions` also: provisional_diagnosis, discharge_date, discharge_type (discharged/LAMA/DAMA/referred/expired), discharge_summary_id, final_bill_id, status
- `nursing_notes` — admission_id, note_at, note_by, body, vitals_snapshot JSON
- `medication_administration_records` — admission_id, drug_id, dose, route, scheduled_at, administered_at, administered_by, skipped_reason
- `doctor_rounds` — admission_id, round_at, doctor_id, findings, plan
- `operation_theatres` — branch_id, name, status
- `ot_schedule` — admission_id, ot_id, surgeon_id, anaesthetist_id, procedure, scheduled_start, scheduled_end, pre_op_checklist_completed, status
- `surgical_case_sheets` — admission_id, ot_schedule_id, pre_op, intra_op, post_op, findings, complications
- `discharge_summaries` — admission_id, final_diagnosis, treatment_given, condition_at_discharge, discharge_medications, advice, follow_up
- `ipd_bills` — admission-level bill rolling up all daily charges
- `daily_charges` — admission_id, charge_date, charge_type (room/doctor_visit/nursing/medication/consumable/procedure/investigation), amount, notes

#### Features
- Real-time bed occupancy dashboard (color-coded ward map)
- Admission → discharge workflow
- Doctor rounds logging (mobile-friendly for ward visits)
- Nursing notes with shift handover
- Medication Administration Record (MAR) — due / given / missed
- OT scheduling with surgeon calendar
- Pre-op checklist (fasting confirmed, consent signed, investigations done)
- Surgical case sheet with complications log
- Discharge summary auto-generation from admission data (fillable template)
- Running IPD bill (updates live as charges accrue)
- Insurance pre-authorization workflow for admitted patients
- Blood bank integration (request, issue, track components)
- Diet ordering (kitchen module)

### 4.4 👁️ Ophthalmology / ENT / Dermatology / Cardiology

#### Ophthalmology tables
- `refraction_cards` — visit_id, distance_right_sphere/cylinder/axis, near, add, PD, IPD, keratometry, fundus findings, IOP right/left, dilation notes
- `ophthalmic_procedures` — LASIK, cataract, glaucoma surgery tracking
- `optical_orders` — spectacle orders with frame + lens specs, amount, vendor

#### ENT tables
- `audiometry_results` — air/bone conduction values per frequency (250-8000 Hz) per ear
- `tympanogram_results`
- `ent_procedures` — tonsillectomy, FESS, grommet, etc.

#### Dermatology tables
- `dermatology_photos` — condition, affected area, photo paths (before/during/after), treatment_plan_id
- `skin_exam_findings` — structured body map

#### Cardiology tables
- `ecg_readings` — heart rate, PR, QRS, QT, axis, rhythm, interpretation, pdf_path
- `echo_reports` — ejection fraction, chamber dimensions, valve findings, conclusion, pdf_path
- `treadmill_test_reports` — stages, METs achieved, BP response, result
- `cardiac_history` — patient cardiac-specific data

### 4.5 🌿 Ayurveda / Homeopathy / Physiotherapy / Wellness

#### Ayurveda tables
- `prakriti_assessments` — patient_id, vata/pitta/kapha scores, dosha combination, interpretation
- `panchakarma_packages` — package_id, patient_id, total_sessions, completed_sessions, treatments_included JSON
- `ayurveda_medicines` — classical formulations + patent medicines
- `ayurveda_consultations` — nidana, hetu, samprapti, chikitsa notes

#### Homeopathy tables
- `homeopathy_remedies` — material medica database
- `case_history_homeopathy` — mind/body symptoms structured
- `repertorisation_results` — rubric scores for suggested remedies

#### Physiotherapy tables
- `physio_assessments` — pain scale (VAS), ROM (range of motion), muscle strength (MRC grade), gait analysis, functional assessment scales (Oswestry, etc.)
- `physio_treatment_protocols` — exercise library (with video URLs), recommended sets/reps
- `physio_sessions` — session_number_in_package, patient_id, therapist_id, exercises_done, modalities_used (US/TENS/IFT/hot_pack), progress_notes
- `home_exercise_plans` — shared with patient via PDF/WhatsApp with video links

### 4.6 Module routing by practice_type

#### 4.6.1 Module × Practice-Type Matrix (authoritative reference)

This matrix drives default module visibility when a new tenant is created. Tenant owner can override any toggle from Settings → Modules. Implementation: `tenant_feature_flags` table with `practice_type_defaults` JSON in `system_settings`.

**Legend:** ✓ = enabled by default · — = hidden by default · ⚙️ = enabled but optional

| Module | Dental | Pathology Lab | General OPD | Hospital (IPD) | Eye | ENT | Dermatology | Cardiology | Ayurveda | Homeopathy | Physio / Wellness | Multi-Specialty |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **CORE (always available)** |
| Patient master | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Appointment calendar | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Billing + GST | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Treatment catalog | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Inventory / Pharmacy | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ⚙️ | ✓ |
| HR + Payroll | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Insurance / TPA | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ⚙️ | ✓ |
| Communication (WhatsApp/SMS/Email) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| ABHA / ABDM | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Reports & Analytics | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Activity / Audit log | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Multi-branch | ⚙️ | ⚙️ | ⚙️ | ✓ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ✓ |
| **CLINICAL (per-specialty)** |
| Prescription builder | ✓ | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ⚙️ | ✓ |
| Drug interaction checker | ✓ | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | ✓ |
| Vitals / BMI tracker | ⚙️ | ⚙️ | ✓ | ✓ | ⚙️ | ⚙️ | ⚙️ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Tele-consultation | ⚙️ | — | ✓ | ✓ | ⚙️ | ⚙️ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Digital signature (DSC) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ⚙️ | ✓ |
| **DENTAL-SPECIFIC** |
| Tooth chart (FDI) | ✓ | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Periodontal charting | ✓ | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Dental lab job tracking | ✓ | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| X-ray annotation (IOPA/OPG/CBCT) | ✓ | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Orthodontic progress photos | ✓ | — | — | — | — | — | — | — | — | — | — | ⚙️ |
| **LAB / DIAGNOSTIC** |
| Test catalog (1500+ tests) | — | ✓ | ⚙️ | ✓ | — | — | — | ⚙️ | — | — | — | ✓ |
| Sample barcode tracking | — | ✓ | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Report template designer | — | ✓ | — | ✓ | — | — | — | ⚙️ | — | — | — | ⚙️ |
| Home collection + phlebotomist route | — | ✓ | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Outsourced test / partner-lab | — | ✓ | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| B2B corporate accounts | — | ✓ | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Franchise / collection-center | — | ✓ | — | — | — | — | — | — | — | — | — | ⚙️ |
| **HOSPITAL / IPD** |
| Ward + bed management | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Admission → discharge workflow | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Nursing notes + MAR | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Doctor rounds log | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| OT scheduling | — | — | — | ✓ | ⚙️ | ⚙️ | — | ⚙️ | — | — | — | ⚙️ |
| Pre-op checklist | — | — | — | ✓ | ⚙️ | ⚙️ | — | ⚙️ | — | — | — | ⚙️ |
| Surgical case sheet | — | — | — | ✓ | ⚙️ | ⚙️ | — | ⚙️ | — | — | — | ⚙️ |
| Discharge summary generator | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Running IPD bill (daily charges) | — | — | — | ✓ | — | — | — | — | — | — | — | ⚙️ |
| Blood bank | — | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| Diet / kitchen module | — | — | — | ⚙️ | — | — | — | — | — | — | — | ⚙️ |
| **EYE** |
| Refraction card | — | — | — | ⚙️ | ✓ | — | — | — | — | — | — | ⚙️ |
| Optical orders (spectacles) | — | — | — | ⚙️ | ✓ | — | — | — | — | — | — | ⚙️ |
| Fundus / IOP tracking | — | — | — | ⚙️ | ✓ | — | — | — | — | — | — | ⚙️ |
| **ENT** |
| Audiometry + tympanogram | — | — | — | ⚙️ | — | ✓ | — | — | — | — | — | ⚙️ |
| ENT procedure tracking | — | — | — | ⚙️ | — | ✓ | — | — | — | — | — | ⚙️ |
| **DERMATOLOGY** |
| Body-map + condition photos | — | — | — | ⚙️ | — | — | ✓ | — | — | — | — | ⚙️ |
| Before/after photo comparison | — | — | — | ⚙️ | — | — | ✓ | — | — | — | — | ⚙️ |
| **CARDIOLOGY** |
| ECG / Echo / TMT reports | — | — | — | ⚙️ | — | — | — | ✓ | — | — | — | ⚙️ |
| Cardiac history tracker | — | — | — | ⚙️ | — | — | — | ✓ | — | — | — | ⚙️ |
| **AYURVEDA** |
| Prakriti / Dosha assessment | — | — | — | — | — | — | — | — | ✓ | — | — | ⚙️ |
| Panchakarma package tracking | — | — | — | — | — | — | — | — | ✓ | — | — | ⚙️ |
| Ayurveda classical medicine DB | — | — | — | — | — | — | — | — | ✓ | — | — | ⚙️ |
| **HOMEOPATHY** |
| Case history (mind/body structured) | — | — | — | — | — | — | — | — | — | ✓ | — | ⚙️ |
| Materia medica + repertorisation | — | — | — | — | — | — | — | — | — | ✓ | — | ⚙️ |
| **PHYSIOTHERAPY / WELLNESS** |
| Assessment scales (VAS, ROM, MRC) | — | — | — | ⚙️ | — | — | — | — | ⚙️ | — | ✓ | ⚙️ |
| Exercise library with videos | — | — | — | ⚙️ | — | — | — | — | ⚙️ | — | ✓ | ⚙️ |
| Home exercise plan generator | — | — | — | ⚙️ | — | — | — | — | ⚙️ | — | ✓ | ⚙️ |
| Session-based package tracking | — | — | — | ⚙️ | — | — | — | — | ✓ | — | ✓ | ⚙️ |
| **PATIENT PORTAL** |
| Self-service login (mobile OTP) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Family member linking | ⚙️ | ⚙️ | ✓ | ✓ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ✓ |
| Online appointment booking widget | ✓ | ✓ | ✓ | ⚙️ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Online bill payment | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Personal health tracker (BP/sugar/weight) | ⚙️ | — | ✓ | ✓ | ⚙️ | ⚙️ | ⚙️ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Loyalty / membership points | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ | ⚙️ |

#### 4.6.2 Default module set per practice type (machine-readable)

Store this as `system_settings.key = 'practice_type_defaults'`:

```php
// config/practice_types.php (committed) — or DB row for superadmin to tweak
return [
    'dental' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'pharmacy', 'hr', 'insurance_tpa',
                      'communication', 'abdm', 'reports', 'audit_log', 'dental_tooth_chart', 'dental_lab_jobs',
                      'dental_xray', 'patient_portal', 'online_booking'],
        'optional' => ['multi_branch', 'tele_consult', 'orthodontic_photos'],
    ],
    'pathology_lab' => [
        'enabled' => ['patient', 'appointment', 'billing', 'pharmacy', 'hr', 'insurance_tpa', 'communication',
                      'abdm', 'reports', 'audit_log', 'lab_test_catalog', 'lab_sample_barcode',
                      'lab_report_templates', 'home_collection', 'outsourced_tests', 'b2b_accounts',
                      'patient_portal', 'online_booking'],
        'optional' => ['multi_branch', 'franchise_network'],
    ],
    'opd' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'vitals',
                      'pharmacy', 'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'tele_consult', 'digital_signature', 'patient_portal', 'online_booking',
                      'family_linking', 'personal_health_tracker'],
        'optional' => ['multi_branch', 'basic_lab_tests'],
    ],
    'hospital' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'vitals',
                      'pharmacy', 'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'multi_branch', 'tele_consult', 'digital_signature', 'lab_test_catalog',
                      'lab_sample_barcode', 'lab_report_templates', 'outsourced_tests',
                      'ward_bed_mgmt', 'admission_discharge', 'nursing_notes_mar', 'doctor_rounds',
                      'ot_scheduling', 'pre_op_checklist', 'surgical_case_sheet', 'discharge_summary',
                      'ipd_billing', 'patient_portal', 'online_booking', 'family_linking',
                      'personal_health_tracker'],
        'optional' => ['blood_bank', 'diet_kitchen', 'dental_tooth_chart', 'refraction_card',
                       'audiometry', 'ecg_echo_tmt', 'physio_assessment', 'physio_exercise_library'],
    ],
    'eye' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'pharmacy',
                      'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'digital_signature', 'refraction_card', 'optical_orders', 'fundus_iop',
                      'patient_portal', 'online_booking'],
        'optional' => ['multi_branch', 'tele_consult', 'ot_scheduling', 'vitals'],
    ],
    'ent' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'pharmacy',
                      'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'digital_signature', 'audiometry', 'ent_procedures',
                      'patient_portal', 'online_booking'],
        'optional' => ['multi_branch', 'tele_consult', 'ot_scheduling', 'vitals'],
    ],
    'dermatology' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'pharmacy',
                      'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'tele_consult', 'digital_signature', 'derm_body_map', 'derm_before_after_photos',
                      'patient_portal', 'online_booking'],
        'optional' => ['multi_branch', 'vitals'],
    ],
    'cardiology' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'drug_interaction', 'vitals',
                      'pharmacy', 'hr', 'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'tele_consult', 'digital_signature', 'ecg_echo_tmt', 'cardiac_history',
                      'patient_portal', 'online_booking', 'personal_health_tracker'],
        'optional' => ['multi_branch', 'basic_lab_tests', 'ot_scheduling'],
    ],
    'ayurveda' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'vitals', 'pharmacy', 'hr',
                      'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'tele_consult', 'digital_signature', 'prakriti_assessment', 'panchakarma_packages',
                      'ayurveda_medicine_db', 'physio_session_packages',
                      'patient_portal', 'online_booking', 'personal_health_tracker'],
        'optional' => ['multi_branch', 'physio_assessment', 'physio_exercise_library'],
    ],
    'homeopathy' => [
        'enabled' => ['patient', 'appointment', 'billing', 'prescription', 'vitals', 'pharmacy', 'hr',
                      'insurance_tpa', 'communication', 'abdm', 'reports', 'audit_log',
                      'tele_consult', 'digital_signature', 'homeopathy_case_history', 'homeopathy_repertorisation',
                      'patient_portal', 'online_booking', 'personal_health_tracker'],
        'optional' => ['multi_branch'],
    ],
    'physio' => [
        'enabled' => ['patient', 'appointment', 'billing', 'vitals', 'hr', 'communication', 'abdm',
                      'reports', 'audit_log', 'tele_consult', 'physio_assessment', 'physio_exercise_library',
                      'physio_home_plans', 'physio_session_packages',
                      'patient_portal', 'online_booking', 'personal_health_tracker'],
        'optional' => ['multi_branch', 'pharmacy', 'insurance_tpa', 'prescription', 'digital_signature'],
    ],
    'multi_specialty' => [
        // All modules enabled — tenant toggles off what they don't need
        'enabled' => '*',
        'optional' => [],
    ],
];
```

#### 4.6.3 Toggle UI (Settings → Modules)

- Owner sees a grid of all available modules, checkbox per module
- Disabled modules stay in DB (data preserved) but are hidden from navigation
- Re-enabling restores full access instantly
- Some modules have prerequisites (e.g. `ipd_billing` requires `ward_bed_mgmt`) — UI enforces dependencies
- Enterprise-only modules show a lock icon with upgrade prompt for lower tiers

#### 4.6.4 Developer contract

Every controller, route, menu item, and Blade view that belongs to a toggleable module must gate visibility through:

```php
// In routes
Route::middleware(['auth', 'module:dental_tooth_chart'])->group(function () { ... });

// In Blade
@module('dental_tooth_chart')
    <a href="...">Tooth Chart</a>
@endmodule

// In controller
abort_unless(tenant()->hasModule('dental_tooth_chart'), 404);
```

Implement a `ModuleMiddleware` + `@module` Blade directive + `Tenant::hasModule()` method that reads from the cached `tenant_feature_flags` for the current tenant.

Tenant owner can toggle any module on/off in Settings → Modules.

### 4.7 Acceptance criteria for Phase 4
- [ ] Dental tenant sees tooth chart; OPD tenant doesn't
- [ ] Lab tenant can create order, collect sample, enter results, generate PDF report
- [ ] Hospital tenant can admit, track daily charges, discharge with summary
- [ ] Ayurveda tenant can record prakriti assessment and start panchakarma package
- [ ] Toggling a module hides its UI immediately
- [ ] Reports per specialty work independently

### 4.8 Commit
`feat(phase-4): specialty modules — dental, lab, ipd, eye, ent, ayurveda, physio`

---

## PHASE 5 — ABDM / ABHA Integration (Regulatory Moat)

### 5.1 What to integrate
ABDM (Ayushman Bharat Digital Mission) provides:
- **ABHA ID creation** — 14-digit health ID for every Indian citizen
- **Health record linking** — ability to fetch/push patient health records to the national Health Data Exchange
- **Digital prescription** — NMC-compliant e-prescriptions with digital signature
- **Consent management** — patient-controlled record sharing

### 5.2 APIs to integrate
1. **ABHA Creation APIs** — via Aadhaar OTP, mobile OTP, or driving license
2. **Health Facility Registry (HFR)** — register clinic with ABDM
3. **Healthcare Professional Registry (HPR)** — register doctors
4. **Health Information Provider (HIP)** — your clinic as data producer
5. **Health Information User (HIU)** — your clinic as data consumer (view past records from other hospitals with patient consent)
6. **Consent Manager Integration**

### 5.3 Implementation
- Wrap ABDM APIs in `app/Integrations/Abdm/AbdmClient.php`
- Registration flow: tenant onboards → collects clinic HPR/HFR → verifies → stores credentials encrypted
- Patient registration flow: collects Aadhaar/mobile OTP → creates ABHA → links to existing `patients.abha_id`
- Consent flow: doctor requests patient records → patient approves via ABDM app → your system fetches records via HIU flow
- Data push: after each visit, push consultation summary to ABDM as FHIR R4 bundle (with patient consent)
- Digital signature: integrate e-Mudhra or Capricorn for doctor's DSC, apply to prescriptions

### 5.4 Tables
- `abdm_tenant_config` — HFR id, HPR id (per doctor), API credentials encrypted
- `abha_creation_logs`
- `consent_requests` — tenant, patient, requesting doctor, purpose, status, granted_at, expires_at
- `abdm_record_pushes` — what got pushed, status, ABDM ack id

### 5.5 Acceptance criteria
- [ ] Patient without ABHA can create one via Aadhaar OTP from portal
- [ ] Doctor can request consent and fetch past records (sandbox env)
- [ ] Prescription push to ABDM returns success in sandbox
- [ ] DSC-signed prescription PDF validates in signature viewer

### 5.6 Commit
`feat(phase-5): ABDM / ABHA integration with consent management`

---

## PHASE 6 — Patient Self-Service Portal

### 6.1 Scope
Patients access via `{tenant-slug}.clinicos.in/patient/login`. Login: mobile + OTP (no password).

### 6.2 Features
- Profile: view/edit personal info, insurance, emergency contact
- Family members: add dependents, view their visits
- Appointments: view upcoming, reschedule, cancel, book new (if public booking enabled)
- Medical records: past visits, prescriptions, lab reports, x-rays — all downloadable
- Bills: outstanding dues, past invoices, pay online
- Prescriptions: view + reorder via pharmacy (delivery option)
- Lab reports: view + download
- Tele-consult: join video call for upcoming tele-appointment
- Health tracking: log own vitals (BP, sugar, weight) — doctor sees on next visit
- Health tips: personalised based on conditions (opt-in)
- Loyalty / membership: points for visits, redeemable at pharmacy
- Family health: upcoming vaccinations, preventive check-up reminders
- ABHA: link to ABDM, grant/revoke consent
- Feedback: rate past visits

### 6.3 Implementation
- Reuse `resources/views/components/layout/employee.blade.php` (copied from source project) as `resources/views/components/layout/patient.blade.php`
- Guard: `auth:patient`
- `PatientAuthController` — OTP login, logout
- Controllers for each feature listed above

### 6.4 Public booking widget
- Embeddable iframe: `{tenant-slug}.clinicos.in/book?embed=1`
- Clinic can paste into their website or Google Business page
- Shows doctors, available slots, captures patient info, creates appointment

### 6.5 Acceptance criteria
- [ ] Patient logs in via OTP, sees upcoming appointment
- [ ] Can reschedule appointment, new slot reflected in admin calendar
- [ ] Can download prescription PDF
- [ ] Can pay outstanding bill via Razorpay, payment reflected in admin
- [ ] Can grant ABDM consent, doctor can fetch records

### 6.6 Commit
`feat(phase-6): patient self-service portal`

---

## PHASE 7 — Tele-consultation Module (Cross-specialty Add-on)

### 7.1 Integration
Use Daily.co (recommended — best pricing for India) or Jitsi (self-host).

### 7.2 Workflow
1. Patient books appointment, selects "Tele-consult" visit type
2. System generates unique video room + secure join links for patient and doctor
3. Pre-consult: patient uploads relevant reports, pays consultation fee
4. At scheduled time: both parties join via link (browser, no app install)
5. During: doctor sees patient's past records, can annotate, dictate prescription
6. Post-consult: e-prescription signed digitally, sent via WhatsApp
7. Follow-up: auto-reminder after N days

### 7.3 Tables
- `tele_consultations` — appointment_id, video_provider, room_id, patient_link, doctor_link, started_at, ended_at, duration_sec, recording_url (opt-in), consent_recorded
- `tele_prepaid_consultations` — payment_id, appointment_id (patient pays before link is released)

### 7.4 Acceptance criteria
- [ ] Book tele-consult, pay online, join call, finish, e-prescription delivered
- [ ] No-show / not-joined scenarios handled (refund flow)
- [ ] Recording stored securely in S3 (if opted in)

### 7.5 Commit
`feat(phase-7): tele-consultation with video, prepaid, e-prescription`

---

## PHASE 8 — Advanced Analytics & Business Intelligence

### 8.1 Dashboards
**Owner/Superadmin dashboards:**
- Revenue: daily/weekly/monthly/yearly, by branch, by doctor, by treatment category
- Patient: acquisition source (Google Ads / referral / walk-in / WhatsApp), repeat vs new, churn signals (no-visit in 180 days)
- Doctor: revenue, patient count, average bill, commission earned
- Treatment: volume, margin, ROI (including referring doctor cost)
- Inventory: stock value, expiry exposure, turnover
- Marketing: WhatsApp template effectiveness (open rate, show-up rate lift)

**Clinical dashboards:**
- Appointments scheduled vs completed
- No-show rate per doctor / per branch
- Waiting time analysis
- Prescription patterns (most prescribed drugs, adherence to clinic formulary)
- Lab turnaround time (order → report)
- Insurance claim velocity (submission → settlement days)

### 8.2 Exports
- Tally-compatible CSV for accountant
- GSTR-1 / GSTR-3B JSON for GST portal
- Patient demographic CSV for external marketing
- Health checkup data for corporate clients

### 8.3 Benchmarking (premium feature)
- Compare clinic's metrics anonymously against peers in same city/specialty
- "Your new patient growth is 8% vs city average of 12%"
- Drives enterprise plan upsell

### 8.4 Tables
- Materialized daily aggregates (via scheduled jobs)
- `daily_branch_metrics`
- `daily_doctor_metrics`
- `monthly_tenant_metrics`

### 8.5 Acceptance criteria
- [ ] Dashboards load in <2s for tenants with 100K patients and 1M records
- [ ] GSTR-1 JSON imports cleanly into GST offline utility
- [ ] Benchmark panel shows peer comparison (anonymised)

### 8.6 Commit
`feat(phase-8): advanced analytics, BI dashboards, benchmark`

---

## PHASE 9 — Marketplace & Network Features (Platform Moat)

### 9.1 Medicine fulfillment network
- Patient receives prescription → "Order medicines" button
- Nearest pharmacy in network (could be another tenant) accepts order
- Delivery tracking
- Transaction fee: 5-8% to platform

### 9.2 Lab referral network
- Small clinic without in-house lab creates test order → routes to partner lab tenant
- Lab receives, processes, uploads report → clinic sees it in patient record
- Commission flows: clinic gets referral %, lab gets test %, platform gets transaction fee

### 9.3 Doctor-to-doctor referral
- GP refers patient to specialist in network → appointment auto-booked at specialist
- Referring doctor commission auto-calculated on specialist's bill

### 9.4 Insurance network
- Patients with supported TPA can go cashless at any network tenant
- Pre-auth routing + claim settlement workflows

### 9.5 Tables
- `network_pharmacy_orders` — source_tenant, pharmacy_tenant, patient_id, items, status, delivery, platform_fee
- `network_lab_orders` — source_tenant, lab_tenant, order, status, platform_fee
- `network_doctor_referrals` — from_tenant, to_tenant, from_doctor, to_doctor, patient_id, commission
- `network_insurance_claims` — centralised across tenants

### 9.6 Acceptance criteria
- [ ] Clinic A creates order → Pharmacy B tenant receives it, fulfills, platform invoices fee
- [ ] Referral from GP tenant to specialist tenant — both see same patient record (with consent)
- [ ] Platform revenue from network transactions appears on central dashboard

### 9.7 Commit
`feat(phase-9): marketplace — pharmacy, lab, referral, insurance networks`

---

## PHASE 10 — Localization, Accessibility, Polish

### 10.1 Regional language UI
- Translate all UI strings (use Laravel's `__()` + `resources/lang/`) to: English, Hindi, Marathi, Gujarati, Tamil, Kannada, Telugu, Bengali
- Use native scripts (Devanagari, Tamil, etc.)
- Per-user language preference in profile
- Per-patient language preference for communications
- Translate WhatsApp templates (pre-approved from Meta) to all 8 languages

### 10.2 Mobile responsiveness
- Every page works on 360px wide screens
- Key flows (appointment booking, billing, medication administration) optimised for tablet use
- Optional: Capacitor-based mobile app wrapper for offline-friendly field use (phlebotomists, home visits)

### 10.3 Accessibility
- ARIA labels on all interactive elements
- Keyboard navigation full support
- Screen reader compatibility (VoiceOver, TalkBack)
- Color contrast WCAG AA compliance
- Focus indicators on all form fields

### 10.4 Performance
- Aggressive DB indexing review (analyze slow query log)
- Redis caching for: tenant config, user permissions, treatment catalog, drugs master
- CDN (BunnyCDN or CloudFront) for static assets, patient photos, report PDFs
- Image compression pipeline (Intervention Image + WebP conversion)
- Lazy loading for images and dashboard charts
- Database read replicas for reports (after 1000 tenants)

### 10.5 Onboarding polish
- Welcome tour (using Intro.js or Shepherd) on first login per role
- Video tutorials embedded in settings page
- Sample data toggle (create mock patients/appointments for trial users to play with)
- 1-click delete of sample data before going live

### 10.6 Acceptance criteria
- [ ] UI switches languages live, all strings translated
- [ ] Mobile usability test on real phone (Jio handset, tablet)
- [ ] Lighthouse score >85 for performance/accessibility
- [ ] Onboarding tour completes without errors

### 10.7 Commit
`feat(phase-10): i18n, mobile polish, accessibility, onboarding`

---

## PHASE 11 — Security, Compliance, Legal

### 11.1 Data protection
- Encrypt sensitive fields at rest: Aadhaar, PAN, bank, medical notes, diagnoses, prescription items
- Use Laravel's `encrypted` cast on relevant columns
- Transparent to application logic; prevents dump-file leaks
- Encryption keys rotated annually via `php artisan key:rotate` (custom command)

### 11.2 Audit logging
- Every access to patient record logged: who, when, from where (IP), what was viewed
- Every export of patient data logged and requires manager approval
- Tamper-proof audit log (write-only, no deletion allowed even by superadmin)

### 11.3 DPDP Act 2023 compliance (India's data protection law)
- Explicit patient consent on registration
- Purpose-bound processing (don't use data for purposes not consented)
- Data Principal rights: access, correction, deletion, portability
- 72-hour breach notification workflow
- Data Fiduciary registration if applicable
- Children (<18) require parent/guardian consent
- Grievance officer designation per tenant

### 11.4 Security hardening
- 2FA for all staff users (TOTP via Google Authenticator, or SMS OTP fallback)
- Password policy: 12+ chars, complexity, rotation every 90 days for admin roles
- Account lockout after 5 failed logins (15-min timeout)
- Session timeout (30 min idle for clinical roles, 4 hours for receptionist)
- IP whitelist for sensitive roles (enterprise plan only)
- Rate limiting on auth endpoints
- CSRF protection on all state-changing routes (Laravel default)
- SQL injection protection (Eloquent only, no raw queries with user input)
- XSS protection (Blade escape by default)
- SSRF protection for webhook / remote image fetches
- Security headers: CSP, HSTS, X-Frame-Options, Referrer-Policy

### 11.5 Backup & DR
- Tenant DB: daily incremental + weekly full + monthly archival
- Central DB: hourly + daily
- S3 bucket replication across regions (Mumbai → Hyderabad)
- Quarterly restore drill
- 30-day point-in-time recovery for central DB

### 11.6 Legal documents
- Terms of Service
- Privacy Policy (DPDP-compliant)
- Data Processing Agreement (for enterprise clients)
- HIPAA-inspired safeguards (even without legal obligation, investor-friendly)
- Shop & Establishment / MSME registration as a data fiduciary
- Medical records retention: 10 years for adults, 3 years beyond minor's 18th birthday

### 11.7 Acceptance criteria
- [ ] 2FA enforced for all Doctor / Owner roles
- [ ] Sensitive columns are encrypted at rest (verify via raw DB query)
- [ ] Audit log captures patient record access
- [ ] Failed backup triggers alert
- [ ] Quarterly restore drill runs successfully

### 11.8 Commit
`feat(phase-11): security hardening, DPDP compliance, audit log, backups`

---

## PHASE 12 — Deployment & DevOps

### 12.1 Environment
- **Staging:** `staging.clinicos.in` — mirrors production, for internal QA and client demos
- **Production:** `clinicos.in` (central) + `*.clinicos.in` (wildcard DNS for tenant subdomains)
- **Infra:** AWS Mumbai (ap-south-1) — ECS Fargate for app, RDS MySQL Multi-AZ, ElastiCache Redis, S3, CloudFront
- **CI/CD:** GitHub Actions → auto-deploy to staging on PR merge, manual trigger to production

### 12.2 Docker
Write a production-grade `Dockerfile` with PHP-FPM + Nginx multi-stage. Compose file for local dev.

### 12.3 Monitoring
- **Sentry** — errors + performance
- **Papertrail / CloudWatch Logs** — centralised logs
- **UptimeRobot** — public status page
- **Cronitor** — scheduled job monitoring
- **New Relic / Datadog** — APM (once revenue justifies)

### 12.4 Scaling plan
- Day 1: 1 app server, 1 MySQL primary, Redis
- At 1000 tenants: Add read replica, add queue worker server
- At 10,000 tenants: Separate web and API nodes, CDN mandatory, queue autoscaling
- At 50,000 tenants: DB sharding by tenant_id range, dedicated reporting cluster

### 12.5 Runbooks
Document in `/docs/runbooks/`:
- Tenant signup failure recovery
- Payment webhook retry
- Database migration rollback
- Tenant data export (GDPR/DPDP requests)
- Incident response (outage, breach)
- On-call rotation

### 12.6 Acceptance criteria
- [ ] Push to main → auto-deploy to staging, tests pass, smoke check passes
- [ ] Can manually promote to production with one click
- [ ] New tenant signup in production completes in <30 seconds
- [ ] All runbooks tested at least once

### 12.7 Commit
`chore(phase-12): deployment, monitoring, runbooks`

---

## PHASE 13 — Documentation, Sales Enablement, Launch

### 13.1 User documentation
- `docs/user/getting-started.md` — first day of use
- `docs/user/{module}.md` — one per module (dental, lab, etc.)
- Video tutorials (screen recordings, hosted on YouTube unlisted)
- In-app context help (tooltips on every complex field)

### 13.2 API documentation
- For enterprise clients wanting integration (HIS, lab machines)
- OpenAPI 3.0 spec generated from route annotations
- Hosted at `api.clinicos.in/docs`

### 13.3 Sales enablement
- Pitch deck (15 slides, PDF)
- One-pager per specialty (dental, lab, OPD, etc.)
- ROI calculator (shows monthly savings vs competitors)
- Comparison table (vs Practo, eClinical, Medeil, Halemind)
- Reference customers / testimonials (collect from Phase-1 pilots)

### 13.4 Pricing page + self-serve signup
- Clear 3-tier pricing with toggle (monthly/annual)
- Feature comparison matrix
- FAQ (security, data ownership, migration, support)
- "Request demo" form for enterprise

### 13.5 Launch checklist
- [ ] 10 paying customers (₹3-10K/month each) from Phase-1 pilots
- [ ] 50+ feature tests passing in CI
- [ ] Security audit by external firm
- [ ] Legal review of ToS, Privacy Policy, DPA
- [ ] Support team hired (2 reps) or SaaS support tooling set up (Intercom / Crisp)
- [ ] Status page live
- [ ] Incident response plan documented
- [ ] Insurance (E&O, cyber) purchased
- [ ] MSME / startup registration complete

### 13.6 Commit
`docs(phase-13): user/API docs, sales materials, launch prep`

---

# DETAILED FEATURE CHECKLIST (master reference)

## Core Platform (every tenant gets)
- [ ] Multi-tenant architecture with DB-per-tenant
- [ ] Subdomain-based tenant identification + custom domain support
- [ ] Central superadmin portal
- [ ] Tenant signup wizard with OTP
- [ ] Plan management + subscription billing via Razorpay
- [ ] Multi-branch support per tenant
- [ ] Role-based access (Owner, Doctor, Receptionist, Nurse, Lab Tech, Pharmacist, Accountant)
- [ ] Fine-grained permissions via Spatie
- [ ] Patient master (50+ fields including UHID, ABHA, medical history)
- [ ] Patient family linking
- [ ] Patient documents upload
- [ ] Patient insurance policies tracking
- [ ] Appointment calendar (day/week/month, resource-aware, drag-drop)
- [ ] Online appointment booking widget
- [ ] Recurring appointments
- [ ] Waiting list + token system
- [ ] Appointment confirmations + reminders (WhatsApp/SMS/email)
- [ ] Treatment catalog with GST rates, multi-language names
- [ ] Treatment packages (multi-session bundles)
- [ ] Multi-visit treatment plans
- [ ] Billing / Invoice generation with GST
- [ ] Multiple payment methods (cash/UPI/card/netbanking/cheque)
- [ ] Partial payments, advances, refunds
- [ ] TPA insurance claim workflow (pre-auth, submission, settlement)
- [ ] GST-compliant invoice PDF
- [ ] Drug master database (50K+ Indian drugs)
- [ ] Prescription builder with allergy warnings and DDI checks
- [ ] Prescription templates per doctor
- [ ] Digital prescription signing (DSC)
- [ ] Pharmacy inventory with batch + expiry tracking
- [ ] FEFO picking
- [ ] Purchase orders + goods receipt
- [ ] Pharmacy POS (walk-in sales)
- [ ] Stock transfers between branches
- [ ] Staff attendance (biometric CSV import)
- [ ] Staff leave management
- [ ] Staff commission calculation
- [ ] Referring doctor commission
- [ ] Reports: collection, revenue, doctor performance, inventory, GST
- [ ] Activity log viewer
- [ ] Data export (patient data, GST returns, Tally)

## Communication
- [ ] WhatsApp Business API (Gupshup / Interakt / WATI switchable)
- [ ] MSG91 SMS integration
- [ ] Amazon SES email
- [ ] Message template management with approvals
- [ ] Multi-language templates
- [ ] 15+ pre-built templates (reminders, bills, reports, feedback, etc.)
- [ ] Scheduled campaigns
- [ ] Delivery + read receipts via webhooks
- [ ] Two-way conversation inbox
- [ ] Opt-out handling
- [ ] Birthday wishes automation
- [ ] Outstanding payment reminders
- [ ] Post-visit feedback automation

## Specialty — Dental
- [ ] Interactive tooth chart (FDI numbering)
- [ ] Per-tooth condition tracking
- [ ] Dental treatment plans
- [ ] Dental lab job tracking (crowns, bridges)
- [ ] X-ray upload + annotation (IOPA, OPG, CBCT)
- [ ] Orthodontic progress photos
- [ ] Periodontal charting
- [ ] Dental-specific treatment catalog

## Specialty — Pathology Lab
- [ ] 1500+ test catalog with reference ranges
- [ ] Test parameters with normal ranges by age/gender
- [ ] Lab panels (test bundles)
- [ ] Lab orders (in-house / home / pickup)
- [ ] Sample barcode generation + scanning
- [ ] Sample tracking (collected → received → processed → reported)
- [ ] Result entry with auto-flagging of abnormal values
- [ ] Report template designer
- [ ] Doctor's digital signature on reports
- [ ] Home collection scheduling + route planning
- [ ] Phlebotomist management
- [ ] Outsourced test tracking
- [ ] Referring doctor commission
- [ ] B2B corporate accounts
- [ ] Franchise / collection-center management

## Specialty — Hospital / IPD
- [ ] Ward + bed management with visual layout
- [ ] Bed occupancy dashboard
- [ ] Admission → Discharge workflow
- [ ] Daily charges accumulation
- [ ] Running IPD bill
- [ ] Doctor rounds logging
- [ ] Nursing notes with shift handover
- [ ] Medication Administration Records (MAR)
- [ ] OT scheduling with surgeon calendar
- [ ] Pre-op checklist
- [ ] Surgical case sheet
- [ ] Discharge summary generator
- [ ] Insurance pre-auth workflow
- [ ] Blood bank integration (optional)
- [ ] Diet ordering / kitchen module (optional)

## Specialty — Eye / ENT / Derm / Cardio
- [ ] Refraction card + optical orders
- [ ] Audiometry + tympanogram
- [ ] Dermatology body map + photos
- [ ] ECG / Echo / TMT report storage + viewer

## Specialty — Ayurveda / Homeopathy / Physio / Wellness
- [ ] Prakriti assessment
- [ ] Panchakarma package tracking
- [ ] Ayurveda classical + patent medicine database
- [ ] Homeopathy repertorisation
- [ ] Physio assessment scales (VAS, ROM, MRC)
- [ ] Exercise library with videos
- [ ] Home exercise plan generator
- [ ] Session-based package tracking

## ABDM / ABHA
- [ ] HFR (Health Facility Registry) integration
- [ ] HPR (Healthcare Professional Registry) integration
- [ ] ABHA ID creation flow (Aadhaar OTP + mobile OTP)
- [ ] Consent request + manager integration
- [ ] Health record fetch (HIU flow)
- [ ] Health record push (HIP flow) — FHIR R4 bundles
- [ ] Digital signature on prescriptions (eMudhra / Capricorn DSC)

## Patient Portal
- [ ] Mobile OTP login (no password)
- [ ] Profile + family member management
- [ ] Appointment view / reschedule / cancel / book
- [ ] Past visit records access
- [ ] Prescription PDF download
- [ ] Lab report PDF download
- [ ] Bill payment via Razorpay
- [ ] Medicine reordering from pharmacy
- [ ] Tele-consult video call joining
- [ ] Personal health tracking (BP, sugar, weight log)
- [ ] Health tips feed (opt-in)
- [ ] ABDM consent management from patient
- [ ] Loyalty/membership points
- [ ] Feedback submission

## Tele-consultation
- [ ] Video call integration (Daily.co / Jitsi)
- [ ] Prepaid consultation
- [ ] Pre-consult document upload
- [ ] Live consultation with past records view
- [ ] E-prescription with DSC
- [ ] Optional recording (patient consent)
- [ ] No-show / late-join handling with refunds

## Analytics & BI
- [ ] Revenue dashboards (daily/weekly/monthly/yearly, branch, doctor, category)
- [ ] Patient acquisition & churn analytics
- [ ] Doctor performance scorecards
- [ ] Treatment ROI analysis
- [ ] Inventory valuation + turnover
- [ ] Marketing ROI (WhatsApp campaign effectiveness)
- [ ] Appointment funnel (scheduled → arrived → completed)
- [ ] No-show rate per practitioner / branch
- [ ] Lab TAT analysis
- [ ] Insurance claim velocity
- [ ] GST return exports (GSTR-1, GSTR-3B)
- [ ] Tally export
- [ ] Peer benchmarking (anonymised)

## Marketplace / Network
- [ ] Pharmacy fulfillment network (clinic → pharmacy tenant)
- [ ] Lab referral network (clinic → lab tenant)
- [ ] Doctor-to-doctor referral
- [ ] Insurance cashless network
- [ ] Platform transaction fee collection

## Localization
- [ ] 8 Indian languages UI (English, Hindi, Marathi, Gujarati, Tamil, Kannada, Telugu, Bengali)
- [ ] Multi-language WhatsApp templates (approved with Meta)
- [ ] Per-patient communication language preference
- [ ] Regional number formatting (₹1,00,000 not $100,000)
- [ ] Regional date formatting (DD-MM-YYYY default)

## Security & Compliance
- [ ] Field-level encryption for sensitive data
- [ ] Full audit log (patient access, exports, admin actions)
- [ ] 2FA for all staff
- [ ] Role-based access with granular permissions
- [ ] DPDP Act 2023 compliance workflow
- [ ] Patient consent management
- [ ] Data retention policies (10 years adult, 3 years past minor's 18th)
- [ ] Breach notification workflow (72h)
- [ ] Grievance officer per tenant
- [ ] Encrypted backups with cross-region replication
- [ ] Quarterly restore drills
- [ ] Security headers (CSP, HSTS, etc.)
- [ ] Rate limiting on auth endpoints
- [ ] IP whitelist (enterprise only)

## Deployment
- [ ] Docker + Docker Compose
- [ ] GitHub Actions CI/CD
- [ ] AWS Mumbai infrastructure
- [ ] Auto-scaling app tier
- [ ] RDS Multi-AZ
- [ ] S3 + CloudFront
- [ ] Sentry + Papertrail + UptimeRobot + Cronitor
- [ ] Staging environment mirroring production
- [ ] Zero-downtime deployments (Laravel Envoyer / Deployer)
- [ ] Database migration safety (can roll back every phase)

---

# TESTING STRATEGY

## Unit tests (Pest)
- Every service class
- Every domain rule (GST calculation, commission calculation, appointment conflict, drug interaction, reference range flagging)
- Every policy (can this user do this?)

## Feature tests
- Full user flow per module (create patient → book appointment → bill → payment)
- Multi-tenant isolation (tenant A cannot read tenant B's data even with SQL injection attempts)
- Permission enforcement (receptionist cannot access prescription)
- WhatsApp delivery pipeline (mock gateway)
- Billing + payment + refund + GST

## Browser tests (Dusk, optional)
- Critical paths: signup, first appointment, first bill, first prescription

## Load tests (k6 or JMeter)
- 1000 concurrent appointment bookings per tenant
- 100,000 patient records query performance
- Queue drain rate under 10,000 queued messages

## Security tests
- OWASP ZAP automated scan every release
- Manual penetration test annually
- SQL injection tests in CI
- XSS tests in CI

## Minimum coverage
- 70%+ for services
- 60%+ overall
- 100% for billing/payment/refund math

---

# FINAL NOTES FOR CLAUDE CODE

## Execution rules
1. **Build strictly phase by phase.** Complete all acceptance criteria of phase N before starting phase N+1.
2. **Commit after every phase** with the specified commit message.
3. **Never skip the "Acceptance criteria" section** — run the checks listed.
4. **Ask for user review after each phase.**
5. **Write tests alongside code, not after.**
6. **Prefer small migrations** over big ones (easier to roll back).
7. **Always populate seed data** for new tables so manual testing is possible.
8. **Every list view must have pagination, search, filter** from day one.
9. **Every create/edit must use FormRequest classes** for validation.
10. **Every controller method must have authorisation check** (abort_unless or policy).
11. **Use Blade components aggressively** — avoid duplicated markup.
12. **Alpine.js for interactivity** — no jQuery, no SPA framework.
13. **All money in paise** (integer), convert to rupees only at display layer.
14. **All dates in UTC** in DB, render in tenant's timezone.
15. **All text fields in utf8mb4** to support all Indian scripts + emoji.
16. **Log every external API call** (WhatsApp, SMS, Razorpay, ABDM) to a debug table.
17. **Queue all slow operations** — never block HTTP request thread.
18. **Cache tenant config + user permissions** — hit these on every request.
19. **After completing each phase, update this file's checklist with [x]** so future chat sessions know the state.

## Don't do these
- Don't hardcode tenant-specific logic
- Don't mix central and tenant models in same controller
- Don't skip validation because "it's internal"
- Don't return user input in error messages (XSS risk)
- Don't use `sleep()` in code — use queued jobs
- Don't store passwords / API keys in code — env + encrypted config
- Don't forget to handle `tenancy::runForTenant()` when queueing jobs
- Don't send emails/SMS synchronously in controllers

## Daily session protocol
At the start of every new chat session:
1. Read this file end-to-end
2. Check `git log --oneline -20` to see where the last session ended
3. Look at the checklist above — find the first `[ ]` unchecked item in order
4. Verify the state matches by running a smoke test of the previously-completed phase
5. Resume work on the next incomplete phase
6. Mark items `[x]` in this file as they're completed
7. Commit the checklist update with every phase's main commit

---

# BUSINESS OPERATING PRINCIPLES

## Pricing philosophy
- Never discount more than 15% without approval
- Annual plans get 2 months free (equivalent to 16.6% discount)
- First 100 customers get 40% lifetime discount as "founding members" — promotes referrals
- Enterprise is always custom-priced based on bed count / branch count / patient volume

## Customer success
- All paid customers get mandatory onboarding call (30 min) in week 1
- Weekly usage report sent to owner (engagement metrics)
- Health score: green / yellow / red based on usage decline
- Red = retention team calls within 48h

## Roadmap cadence
- Monthly releases
- Quarterly major feature additions
- Annual pricing review

## Target metrics by Year 5
- 60,000 paying tenants
- ₹40 Cr MRR
- 95% gross retention
- 110% net revenue retention (existing customers spend more)
- CAC payback < 6 months
- NPS > 50

---

# APPENDIX A — Go-to-Market Strategy (GTM Phases)

These are **sales/business phases**, distinct from the build phases above. GTM starts once Phase 2 (Tenant Core) is live and a dental specialty module from Phase 4 is ready.

## GTM Phase 1 — Months 1-3: Dental dominance in one city
- Pick your home city (Pune / Nagpur / Surat / Jaipur — wherever you live)
- **Sell only to dental clinics** — they talk to each other, referral rate is highest
- Win **50 dentists** with heavy-discount founding-member pricing (₹40-₹50K setup + ₹3K/month vs normal ₹75K + ₹5K)
- In exchange for pricing: get testimonial video + referral commitment to 3 friends
- Your pitch: *"We're building this for dentists first, but it works for any specialty. You'll be our founding customer and your feedback shapes the product."*
- **Goal by Month 3:** 50 paying dentists in 1 city, 3 case-study videos, NPS tracking live

## GTM Phase 2 — Months 4-9: Expand specialties within same city
- Existing dentists refer their GP/lab/physio friends
- Pitch: *"Dr. X at [clinic name] uses us. Same system, with a [specialty] module."*
- **Add specialty modules as clinics demand them** — build what customers pull, don't push
- Target **200 customers by Month 9** across dental + lab + OPD + physio
- Start a monthly webinar ("Clinic Growth in 2026") to build a pipeline
- **Goal by Month 9:** 200 paying clinics, ₹10-15 L MRR, 2 full-time sales reps hired

## GTM Phase 3 — Months 10-18: Hospital / chain customers
- Higher-value (₹30K-₹2L/month ACV), longer sales cycle (2-4 months)
- Need: dedicated customer success manager, onboarding specialist, SLA document
- Target: 10-50 bed hospitals and 3-5 branch chains
- **Goal by Month 18:** 500 customers (400 solo + 80 multi-doc + 20 hospital) = ₹50-60 L MRR = ₹6-7 Cr ARR

## GTM Phase 4 — Years 2-3: Geographic expansion
- Tier-2/3 cities first (Indore, Coimbatore, Kochi, Bhubaneswar, Lucknow, Jaipur, Vadodara, Visakhapatnam)
- **Avoid metros** (Mumbai, Bangalore, Delhi) until Year 3 — competition is stacked
- Hire **local sales partners on commission** (20% of first year's contract)
- Language-localised sales material (Hindi, Marathi, Tamil, Gujarati)
- **Goal by Year 3:** 3,000 customers = ₹3 Cr MRR = ₹36 Cr ARR — raise Series A here

## GTM Phase 5 — Year 3+: Platform plays (network effects)
- **Medicine marketplace** — pharmacy tenants fulfill orders from clinic tenants → 5-8% transaction fee
- **Lab network** — clinics without in-house lab send tests to lab tenants → 10% referral fee
- **Doctor-to-doctor referral** — GP refers to specialist on platform → both are customers, you take 3%
- **Insurance integration** — TPAs (Bajaj Allianz, Star, HDFC Ergo) process claims through your pipes → they pay per claim processed (₹50-₹200 each)
- These are **pure-margin layers** on top of existing SaaS revenue
- **Goal by Year 5:** 60,000 customers, ₹40 Cr MRR from SaaS + ₹5-8 Cr MRR from network fees

---

# APPENDIX B — Why You'll Win (Competitive Positioning)

## Who competes in Indian SMB healthcare software?

| Competitor | Price | Weakness you exploit |
|---|---|---|
| **Practo Ray** | ₹1,500-₹5,000/m | Expensive for solo clinics, weak inventory, poor regional language UX, no WhatsApp-native |
| **Medisys** | ₹5,000+/m | 10-year-old UI, no mobile-first workflows, no WhatsApp |
| **Cliniify** | ₹1,500-₹3,000/m | Dental-only, can't upsell to other specialties |
| **Suvarna HIS** | ₹20,000+/m | Enterprise-focused, overkill + overprice for small clinics |
| **Halemind** | ₹3,000-₹10,000/m | UK-style UX, not India-localised, no Indian TPA workflows |
| **Insta HMS** | ₹8,000+/m | Enterprise-tier pricing, slow sales |
| **eClinical Works** | Foreign, $400+/m | Foreign, no India GST/TPA/ABDM, USD pricing |
| **Cloudbeds/Dr. Chrono** | Foreign | No localisation, no WhatsApp, compliance gap |

## Your 6 non-negotiable differentiators

1. **Regional-language UI in 8 Indian scripts** (Hindi, Marathi, Gujarati, Tamil, Kannada, Telugu, Bengali + English) — instantly disqualifies every foreign competitor and 80% of Indian ones
2. **WhatsApp-first patient communication** — no patient app to install, meets users where they already are
3. **Tier-2/3-friendly pricing** — ₹3K/month starter tier (competitors start at ₹5K-₹8K)
4. **ABDM-native** (ABHA creation, consent flow, health-record exchange) — the new government digital-health regulation is a moat; most competitors are 2+ years behind
5. **GST + Indian TPA workflows baked in** — not an afterthought, not a module, built into every invoice
6. **Multi-specialty configurable** — one platform, toggle modules. A chain with dental + pharmacy + diagnostic labs uses one subscription instead of three different tools

## One-line pitch per segment

- **Solo dentist:** *"One app replaces your appointment book, invoice pad, prescription pad, and X-ray folder — ₹3K/month."*
- **Diagnostic lab:** *"1500 tests pre-loaded, barcode sample tracking, report auto-delivered to patients on WhatsApp. ₹8K/month vs ₹30K/month for CrelioHealth."*
- **Multi-specialty clinic:** *"Your GP, dentist, and physio all use the same patient database. One subscription, three specialties."*
- **50-bed hospital:** *"Replace 3 systems (billing, HIS, pharmacy) with one. ₹40K/month vs ₹1.5L/month for Suvarna."*
- **Ayurveda chain:** *"India's only clinic software that understands Prakriti assessment and Panchakarma packages."*

---

# APPENDIX C — Market Readiness Checklist (before you sell)

**⚠️ Critical — do not start paid sales until ALL items below are ✓**

## Product readiness
- [ ] Phases 0-6 completed (Setup, Central, Tenant Core, Communication, 1+ specialty module, ABDM, Patient Portal)
- [ ] Full happy-path test: signup → onboarding → create patient → appointment → bill → payment → prescription → WhatsApp delivery
- [ ] Multi-tenancy isolation verified (tenant A cannot see tenant B's data under any circumstance)
- [ ] Payment flow tested with real money on Razorpay (not just test mode)
- [ ] WhatsApp templates approved by Meta (this takes 3-7 days, plan ahead)
- [ ] ABHA creation works end-to-end in sandbox
- [ ] PDF generation works (prescription, invoice, lab report, payslip)
- [ ] All 8 regional languages verified (or at least Hindi + Marathi + Gujarati + Tamil for Phase-1 launch)
- [ ] Mobile-responsive tested on real phones (Jio handset, low-end Android)
- [ ] Page load time <2s on 3G network
- [ ] Error tracking live (Sentry)
- [ ] Uptime monitoring live (UptimeRobot)

## Security & compliance
- [ ] Sensitive fields encrypted at rest (Aadhaar, PAN, bank, medical notes)
- [ ] 2FA enforced for staff roles
- [ ] Audit log captures every patient record access
- [ ] Daily automated backups running + verified
- [ ] Quarterly restore drill scheduled (and documented)
- [ ] Security headers configured (CSP, HSTS, X-Frame-Options)
- [ ] Rate limiting on /login and /signup
- [ ] DPDP Act compliance reviewed by a lawyer
- [ ] Terms of Service + Privacy Policy + Data Processing Agreement published
- [ ] Grievance officer designated (you can be this initially)
- [ ] Cyber insurance purchased (~₹15K-₹25K/year for ₹1 Cr coverage)

## Sales readiness
- [ ] Landing page live with clear pricing
- [ ] Signup flow works self-serve (no manual intervention needed)
- [ ] Demo data / sample tenant available for prospects to try
- [ ] 1-page PDF flyer (specialty-specific)
- [ ] 3-minute demo video per specialty
- [ ] 15-slide pitch deck (PDF)
- [ ] ROI calculator (shows monthly savings vs competitors)
- [ ] Customer-facing help docs (at least `getting-started.md` + `faq.md`)
- [ ] Support email + WhatsApp number live and monitored
- [ ] CRM to track leads (HubSpot free tier or your own ERP's Leads module)

## Legal & business
- [ ] Business registered (Private Limited preferred; LLP acceptable)
- [ ] Current account opened (HDFC/ICICI business account)
- [ ] GST registration + filed first return
- [ ] MSME/Udyam registration (for tax benefits)
- [ ] Razorpay merchant account active (not test mode)
- [ ] Domain + SSL + email (Google Workspace or Zoho Mail)
- [ ] Invoice template with your GSTIN
- [ ] Founder equity agreement signed (if co-founders)

## Operational
- [ ] At least 3 paying pilot customers (even at 70% discount) with signed feedback
- [ ] Onboarding playbook: first-day setup, first-week check-in, first-month review
- [ ] Support response SLA committed (4 hours for starter, 2 hours for growth, 1 hour for enterprise)
- [ ] Refund policy documented (7-day money-back for starter, pro-rated for annual)
- [ ] Data export policy (patient can request their data; you deliver CSV+JSON within 30 days)
- [ ] Incident response plan (who-calls-whom when production goes down)

## Numbers you need to know cold
- [ ] Your CAC (cost to acquire one customer) — track every rupee spent on ads, sales, content
- [ ] Your LTV estimate (monthly ARPU × expected months to churn)
- [ ] Your gross margin per tenant (revenue – (infra cost + WhatsApp/SMS cost + support cost))
- [ ] Runway in months (how long can you survive without new revenue)

---

# APPENDIX D — Straight Answers to the Question "Am I Ready to Sell?"

## If Phases 0-6 are done (minimum viable product):
**Yes, you can sell to first 10-20 pilot customers at 50-70% discount.**
- Use their feedback to harden the product
- They know they're early adopters; they'll tolerate minor bugs
- Don't charge full price; don't promise enterprise features you don't have yet
- Focus on one specialty (dental or lab) and one city

## If Phases 0-8 are done (production-grade):
**Yes, you can sell at full price (₹3-10K/month) confidently.**
- Analytics dashboards + reports are critical for owner-doctors to see ROI
- Without them, customers ask "how much did I actually earn this month?" and you have no answer
- This phase is when you raise prices and stop discounting

## If Phases 0-11 are done (security + compliance):
**Yes, you can sell to small hospitals (10-50 beds) and chains (3-5 branches) at ₹30K-₹2L/month.**
- Enterprise customers will ask for SOC2 / ISO 27001 — you can truthfully say "in progress, Q4 target"
- DPDP Act compliance is non-negotiable for any multi-location hospital
- Customer success team must exist (not just you answering DMs)

## If Phases 0-13 are done (full launch):
**Yes, you can scale to 1000+ customers with a sales team.**
- Hire 2 sales reps + 1 customer success manager
- Run paid ads (Google, Facebook, LinkedIn) with proper CAC tracking
- Attend trade shows (India Medi Expo, Dental Conference, Arab Health India)
- Start the network/marketplace features (Phase 9)

## ⚠️ When NOT to sell
Do not sell if ANY of the below is true:
- Multi-tenant isolation is not 100% airtight (one tenant seeing another's data = game over)
- Backups are not automated + tested
- No way to export a customer's data if they want to leave
- You're the only person who knows how to fix a production bug (single point of failure)
- Your own unit economics are negative (you're losing money per customer)

---

# APPENDIX E — Founder's Survival Guide (honest stuff nobody tells you)

## The first 6 months are a grind
- You'll spend more time on sales than coding
- Doctors will cancel meetings 30 minutes before
- Half your trial signups will ghost you
- **This is normal.** Budget 10 meetings to close 1 customer in the first 3 months.

## Price anchoring is real
- If your first 5 customers pay ₹1K/month, you will struggle to sell ₹5K/month later
- Better to have 5 customers at ₹5K with big discount sunset clauses than 5 at ₹1K permanently
- Always put "standard price ₹5K/month, your price ₹3K/month for 12 months" in writing

## Indian doctors expect personal relationships
- Cold email conversion: <1%
- WhatsApp to a number from a referral: 30%+
- The hack: get 1 doctor to love you, get 3 names from them, mention that doctor when you reach out
- Every doctor's best friend is their CA or another doctor — those are your referral goldmines

## Product feedback is a trap
- Every customer will ask for "just this one more feature"
- 80% of those features are things only that one customer wants
- Build rule: 3 paying customers must ask for a feature before you build it
- Say no to the rest, politely

## Support cost will shock you
- Expect 5-8 support touches per customer per month in year 1
- Each touch = 20-40 minutes
- At 100 customers, that's 500+ hours/month = you need 2 full-time support reps
- Plan for this in unit economics from day 1

## Don't bootstrap past 200 customers
- Between 200-500 customers, you hit the "valley of death" — too many to DIY, too few to fund a real team
- Raise seed/pre-Series A at 150-200 customers (₹15-20 L MRR) — it's the sweet spot
- Use the money to hire sales + customer success, not more engineers

## The competition you don't see
- Your real competitor isn't Practo or Cliniify
- It's **Excel + WhatsApp + a notebook on the receptionist's desk**
- That's what 90% of Indian clinics use today
- Your job is to make switching from Excel *effortless* — which means free data import, free training, free onboarding for first month

---

# APPENDIX F — Day-One Founder Checklist

**Before writing any code (Week 0):**
1. Pick a brand name — must have .com + .in available + Google says it's not trademarked
2. Register the company (MCA Pvt Ltd, ~₹15K, takes 10 days)
3. Open current account (HDFC/ICICI/Axis, ~2 weeks)
4. Register for GST (~1 week)
5. Book 10 doctor meetings. Don't sell. Just ask: *"What are your top 3 daily operational problems?"*
6. Write the answers down. The 3 most-repeated problems are your MVP.

**Week 1-4 of building:**
7. Complete Phase 0 (multi-tenancy setup) — non-negotiable before anything else
8. Complete Phase 2 (patient + appointment + billing) with 1 fake test tenant
9. Invite the first 3 friendliest doctors from your 10 meetings to try the test tenant
10. Fix the 20 things they complain about

**Month 2-3:**
11. Add WhatsApp integration (Phase 3) — this is your differentiator, build it early
12. Onboard first 5 paying customers at 50% discount
13. Get a 2-minute testimonial video from each

**Month 4+:**
14. Build specialty module whichever 3+ customers have asked for
15. Reach 20 paying customers = product-market fit signal
16. Start GTM Phase 2 (expand specialties in same city)

---

**END OF PLAN. Build well. Don't cut corners on security or multi-tenancy isolation — those are the two things that can kill the business.**
