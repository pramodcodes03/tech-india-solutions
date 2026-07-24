# ERP / HRMS — Testing & Acceptance Guide

**For:** Tech India Solutions
**Purpose:** A simple, click-by-click guide so your team can test every new feature from the proposal and sign it off.

---

## How to use this guide

- Each feature has **Where to go**, **What to do**, and **What you should see**.
- Tick the **☐** box once you've confirmed it works.
- There are **two logins**: the **Admin Portal** (for HR / Admin / Accounts staff) and the **Employee Portal** (for staff self-service). Some tests need both.
- Every number (probation days, TAT hours, PF %, escalation days, etc.) is a **setting you control** — so if a default doesn't match your policy, change it in Settings; you don't need us.

### Before you start

| Item | Detail |
|------|--------|
| Admin Portal | `https://<your-domain>/admin/login` |
| Employee Portal | `https://<your-domain>/employee/login` |
| Admin test login | Use your Admin / HR Manager account |
| Employee test login | Use any employee's account (or create one in **HR → Employees → Add Employee**) |

> **Tip:** Keep two browser windows open — one logged in as Admin, one as an Employee — so you can test the full request → approval flow.

---

## 1. Recruitment & Hiring Management

| # | Test | ☐ |
|---|------|---|
| 1.1 | **Add a candidate with a source.** Admin → sidebar **Recruitment → Add Candidate**. Fill name, pick **Source** = *Referral*, choose the **Referred By** employee, save. → Candidate appears in **All Candidates**. | ☐ |
| 1.2 | **Filter by source.** Recruitment → **All Candidates** → use the **Source** filter (Walk-in / Referral / Campus / …). → Only matching candidates show. | ☐ |
| 1.3 | **Pipeline board.** Recruitment → **Pipeline Board**. → Candidates appear in columns (Applied → Screened → Interview → Offer → Hired / Rejected). **Drag** a card to another column → it moves and the status updates. | ☐ |
| 1.4 | **Configurable stages.** Recruitment → **Hiring Stages**. Add a new stage, rename one, drag to reorder. → The board reflects your changes. | ☐ |
| 1.5 | **Campus batch.** Recruitment → **Campus Batches** → create a batch (name, institution, drive date, coordinator). Then add a candidate with Source = *Campus* and pick the batch. | ☐ |
| 1.6 | **Offer letter PDF.** Open a candidate → **Offer Letter PDF** (or fill the Offer box and **Generate Offer Letter**). → A PDF opens with the candidate's details filled in. | ☐ |
| 1.7 | **Referral visibility (employee side).** Log in as the employee who referred someone → **My Referrals**. → They see the candidate and its current status. | ☐ |
| 1.8 | **Reports + export.** Recruitment → **Reports**. → See the stage funnel and source-wise conversion. Click **Export Excel** and **Export PDF**. | ☐ |
| 1.9 | **Bulk upload.** Recruitment → **Bulk Import** → **Download CSV template**, fill a few rows, upload. → Candidates are created. | ☐ |

---

## 2. Payroll & Salary Management

| # | Test | ☐ |
|---|------|---|
| 2.1 | **Department / category template.** HR → Payroll → **Salary Templates** → create a template (set Basic, HRA, etc.), choose level = *Department*. | ☐ |
| 2.2 | **Bulk-assign a template.** On that template click **Apply** → select several employees → **Apply Template**. → Each selected employee gets a salary structure from the template. | ☐ |
| 2.3 | **Monthly override (incentive).** HR → Payroll → **Adjustments** → add an *Incentive* of e.g. 5000 for an employee for this month. Then generate that month's payroll → the incentive shows on the payslip without changing their salary version. | ☐ |
| 2.4 | **Automatic arrears.** Give an employee a backdated salary revision, then HR → Payroll → **Adjustments → Compute & Book Arrears** (pick the employee). → Arrears for the elapsed months are calculated and booked. | ☐ |
| 2.5 | **Income-tax (TDS) slabs + Form 16.** HR → Payroll → **Statutory Register → Settings** → review/edit the TDS slabs. Then **Form 16** → pick an employee → see the annual tax summary. | ☐ |
| 2.6 | **Labour Welfare Fund (LWF).** Statutory **Settings** → set LWF employee/employer amount and frequency → save. | ☐ |
| 2.7 | **Compliance registers + challans.** HR → Payroll → **Statutory Register** (pick month/year) → see PF / ESI / PT / LWF per employee, with **EPS auto-split from PF**. Download **PF Challan**, **ESI Challan**, **PT** and **LWF** registers. | ☐ |
| 2.8 | **Bank-transfer file.** On the same screen click **Bank Transfer File** → an Excel with account number, IFSC and net pay per employee downloads. | ☐ |
| 2.9 | **Excess-break → half-day.** HR → mark a day's attendance with **break minutes** above the configured limit (Statutory Settings → *Excess-break → ½-day LOP*). → That day becomes a half-day and reduces pay in payroll. | ☐ |

---

## 3. Attendance Management (self-service correction)

| # | Test | ☐ |
|---|------|---|
| 3.1 | **Raise a correction (employee).** Employee Portal → **Attendance Corrections → Request Correction** → pick date, type (missed punch), expected in/out, reason → submit. | ☐ |
| 3.2 | **HR review.** Admin → Attendance → **Corrections** → open the request → **Approve & Apply** (or Reject with a reason). → On approve, the employee's attendance for that day is corrected. | ☐ |
| 3.3 | **48-hour resolution target.** The list shows a **due time** and turns red / shows **Escalated** if a request stays open beyond the configured window (HR Settings → *Attendance correction window*, default 48h). | ☐ |

---

## 4. Leave Management

| # | Test | ☐ |
|---|------|---|
| 4.1 | **Accrual settings.** HR → Leaves → **Leave Types** → edit a type → enable **Accrual**, set rate (e.g. 0.5/month), frequency, "only after probation", and (for EL) min working days. | ☐ |
| 4.2 | **Monthly accrual runs automatically.** It credits on the 1st of each period; to verify immediately, HR → Leaves → **Leave Settings → Run Accrual**. → Eligible employees' balances increase. | ☐ |
| 4.3 | **Probation gate.** A brand-new employee (within probation) does **not** accrue until probation ends (HR Settings → *Probation period*). | ☐ |
| 4.4 | **Backdated leave blocked.** Employee Portal → apply for leave with a past date older than the window (Leave Settings, default 72h). → It is **rejected automatically** with a message. | ☐ |
| 4.5 | **Bulk grant.** HR → Leaves → **Leave Balances** → choose a department (or all) → **Bulk Allocate**. → Balances are granted to those employees. | ☐ |
| 4.6 | **Year-end lapse / carry-forward.** Runs automatically on 31 Dec; to verify, Leave Settings → **Run Year-end Lapse**. → Unused short-term leave lapses, eligible earned leave carries forward up to the cap. | ☐ |
| 4.7 | **Leave policy document.** Leave Settings → enter the policy text → save. Employee Portal → Leaves → **Leave Policy** → the employee sees it. | ☐ |

---

## 5. Expense Management & Reimbursement

| # | Test | ☐ |
|---|------|---|
| 5.1 | **Submit a claim (employee).** Employee Portal → **Reimbursements → New Claim** → title, amount, purpose, category, **upload a bill** → submit. | ☐ |
| 5.2 | **Status tracking + history.** The employee's list shows the claim status; opening it shows a **timeline** of every step. | ☐ |
| 5.3 | **Admin review.** Admin → Expenses → **Reimbursements** → open the claim → move it through *Under Review → Approved → Disbursed* (or Reject). | ☐ |
| 5.4 | **Budgets.** Admin → Expenses → **Budgets** → add a budget per category & period. → It shows **Total / Utilised / Remaining** live, with a usage bar. | ☐ |
| 5.5 | **Requisition + approval chain.** Admin → Expenses → **Requisitions → New Requisition** (category Furniture / IT Equipment / …). → It routes through the configured approval levels; approve each level → it becomes Approved. | ☐ |
| 5.6 | **Disbursement + reports.** On an approved requisition → **Mark Disbursed** (add payment ref). Then **Reports** → see by status, category and requester. | ☐ |

---

## 6. Employee Document Management

| # | Test | ☐ |
|---|------|---|
| 6.1 | **Employee uploads.** Employee Portal → **My Documents** → upload a document (e.g. PAN). → Status shows **Pending**. | ☐ |
| 6.2 | **Admin is notified.** Admin gets a bell / email notification that a document was uploaded. | ☐ |
| 6.3 | **Verify / reject.** Admin → HR → Employees → open the employee → **Documents** → **Verify** (or **Reject** with remarks). → The employee sees the updated status. | ☐ |
| 6.4 | **Audit log.** On each document, expand **Audit log** → who verified, when, and remarks. | ☐ |
| 6.5 | **Bulk ZIP download.** On the employee's Documents page → **Download All (ZIP)** → all documents download as one zip. | ☐ |

---

## 7. Internal Helpdesk (Ticket Management)

| # | Test | ☐ |
|---|------|---|
| 7.1 | **Raise a ticket (employee).** Employee Portal → **Helpdesk → Raise Ticket** → pick department (HR / IT / Admin / Accounts), subject, description, priority → submit. | ☐ |
| 7.2 | **Assign + workflow.** Admin → HR → **Helpdesk** → open the ticket → **Assign** to a person → change status (Assigned → In Review → Resolved → Closed). | ☐ |
| 7.3 | **Comments.** Both sides can add comments; admins can add an **internal note** the employee doesn't see. | ☐ |
| 7.4 | **Escalation matrix.** Helpdesk → **Configure** → add escalation levels (after N days → owner). Tickets older than the threshold auto-escalate and flag as breached. | ☐ |
| 7.5 | **Notifications.** Email + bell notifications fire on create / assign / status change / escalation / closure. | ☐ |
| 7.6 | **TAT tracking.** The Helpdesk list shows TAT due time and **breached** tickets per department. | ☐ |

---

## 8. Asset & Inventory Management

| # | Test | ☐ |
|---|------|---|
| 8.1 | **Bulk operations screen.** Admin → Assets → **Bulk Operations** → tick several assets → pick an action (Assign / Change category / status / condition / location) → **Apply**. → All selected assets update. | ☐ |
| 8.2 | **Bulk location transfer.** Same screen → action **Transfer location** → all selected assets move and the transfer is logged. | ☐ |
| 8.3 | **Bulk delete.** Action **Delete** → confirm. → Assets are deleted and the action is recorded in the audit log. | ☐ |
| 8.4 | **Per-asset history.** Assets → Reports → **Employee Assets** (or open an asset's **History**) → see assignments, maintenance and the audit trail. | ☐ |
| 8.5 | **Employee asset report.** Assets → **Employee Assets** → pick an employee → see everything they currently hold. | ☐ |
| 8.6 | **Reports by status/condition/location.** Assets → **Reports** → group by any dimension → **Export Excel**. | ☐ |

---

## 9. Lead Management

| # | Test | ☐ |
|---|------|---|
| 9.1 | **Product-wise lead.** Admin → Leads → Add Lead → choose a **Product** and a **Lead Received Date**. | ☐ |
| 9.2 | **Stage remarks + time tracking.** Open a lead → **Update Stage** with a remark. → The **Stage History** shows the move, the remark, and time spent in the previous stage. The detail panel shows **Lead Age** and **Time in Current Stage**. | ☐ |
| 9.3 | **Product report.** Leads → **Product Report** → filter by product / date / status / employee → see win-rate and pipeline value → **Export Excel**. | ☐ |

---

## 10. Bulk Import Framework

| # | Test | ☐ |
|---|------|---|
| 10.1 | **Employee import with validation.** Admin → **Bulk Imports → Employees** → download the template, fill rows (include one bad row) → upload → **preview** shows valid count + the errors → **Confirm & Import**. | ☐ |
| 10.2 | **Error report.** On the **Bulk Imports** home, any import with failures has a **download error report** link. | ☐ |
| 10.3 | **Payroll adjustments import.** Bulk Imports → **Payroll Adjustments** → upload incentives / arrears / deductions per employee. | ☐ |
| 10.4 | **Leave balance import.** Bulk Imports → **Leave Balances** → upload balances for many employees at once. | ☐ |

---

## 11. Reporting & Analytics

| # | Test | ☐ |
|---|------|---|
| 11.1 | **HR reports hub.** Admin → Reports → **HR & Payroll Reports**. | ☐ |
| 11.2 | **Employee Master.** Filter by department/status → **Export Excel** (includes bank, ESI, UAN/EPS). | ☐ |
| 11.3 | **Payroll report.** Monthly summary + department-wise + component-wise → Export. | ☐ |
| 11.4 | **Leave / Attendance / Expense reports.** Each opens, filters, and exports. | ☐ |
| 11.5 | **Custom report builder.** Reports → **Custom Report Builder** → pick a module (e.g. Employees), tick columns (e.g. Code, Name, Bank, ESI, EPS) → **Preview** → **Save Template** → **Export Excel**. Saved templates can be re-run any time. | ☐ |
| 11.6 | **Dashboards.** HR Dashboard and Asset Dashboard show KPI cards and charts. | ☐ |

---

## Settings you control (no developer needed)

All of these are editable from the Admin Portal — change them and the system uses the new value immediately:

| Setting | Where |
|---------|-------|
| Probation period, attendance-correction window | HR / Leave Settings |
| Leave accrual rates, frequency, EL working-days, carry-forward cap | Leave Types / Leave Settings |
| Backdated leave window | Leave Settings |
| Helpdesk escalation levels & TAT | Helpdesk → Configure |
| PF wage cap, LWF amount/frequency, TDS slabs, PT, excess-break minutes | Payroll → Statutory → Settings |
| Requisition approval chain | (Settings) |

---

## Sign-off

| Module | Tested by | Date | Result (Pass / Issues) |
|--------|-----------|------|------------------------|
| 1. Recruitment | | | |
| 2. Payroll & Salary | | | |
| 3. Attendance correction | | | |
| 4. Leave | | | |
| 5. Expense & Reimbursement | | | |
| 6. Employee Documents | | | |
| 7. Internal Helpdesk | | | |
| 8. Asset & Inventory | | | |
| 9. Lead | | | |
| 10. Bulk Import | | | |
| 11. Reporting & Analytics | | | |

> Found something that doesn't match your policy? In most cases it's a **setting** — adjust it in the Admin Portal. If it's a genuine issue, note it in the table above and share this sheet with us.

---

*Tech India Solutions — ERP / HRMS Add-on Modules*
