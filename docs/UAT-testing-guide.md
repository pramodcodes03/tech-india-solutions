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
| 2.10 | **Bulk payslip delete.** HR → Payroll → Payslips → tick several rows → **Delete Selected**. → The prompt states the exact number; confirming removes only those. Ticking the header checkbox selects the page, and a link offers to select every payslip matching the current month / department / search filter. | ☐ |
| 2.11 | **Employee-wise payslip delete.** Press **Delete** on a single row (or open the payslip → **Delete Payslip**). → Only that employee's payslip for the month is removed; the rest of the run is untouched. | ☐ |
| 2.12 | **Paid payslips are protected.** Include a payslip marked **Paid** in a bulk delete. → It is kept and the message says it needs an admin override. An Admin can tick **Also delete payslips already marked Paid** to remove it; HR Manager is not offered that option at all. | ☐ |
| 2.13 | **A cleared month re-runs cleanly.** Before generating, add a pending **penalty** and an **incentive** for an employee. Generate, then delete that payslip. → The penalty returns to *Pending* and the incentive is no longer *applied*. Generate the month again → both are picked up once, and the net pay matches the first run. | ☐ |
| 2.14 | **Deletions are audited.** After a bulk delete, check the activity log. → One entry records who deleted, how many, the period and the payslip codes. | ☐ |

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
| 4.8 | **Leave Balance Gate — over-balance blocked.** Leave Settings → *Leave Balance Gate* → tick **Block leave requests that exceed the available balance** (on by default). Employee Portal → apply for more days of a paid type than the balance shows. → The form disables **Submit** and states what is available vs requested; forcing the post is refused with the same message and **no request row is created**. | ☐ |
| 4.9 | **Leave Balance Gate — within balance still works.** Same employee, apply for days **equal to or under** the balance. → Submits normally and the days are held as *pending*. | ☐ |
| 4.10 | **LWP exception ON (default).** Leave Settings → tick **Allow Leave Without Pay as an exception**. Employee with a zero balance → apply under an **unpaid / LWP** leave type. → It submits. The refusal message on paid types points them here. | ☐ |
| 4.11 | **LWP exception OFF (absolute block).** Untick the exception. Same zero-balance employee → apply for LWP. → Refused with *"…not permitted as an exception… Please contact HR."* An employee who still has paid balance left is **not** blocked from LWP. | ☐ |
| 4.12 | **Gate OFF restores the old behaviour.** Untick **Block leave requests…** → apply over balance. → It submits; only the days the balance can fund are held as pending and HR splits paid/unpaid at approval. | ☐ |
| 4.14 | **Combined Leave — two halves make a day.** Give an employee **0.5 Casual** and **0.5 Sick** and nothing else. Employee Portal → Apply for Leave → neither type alone will submit for a full day. Tick **Combine two or more leave types** → 0.5 Casual + 0.5 Sick for one day. → The total reconciles to 1.0, it submits, and the request is flagged **COMBINED**. | ☐ |
| 4.15 | **Combined Leave — correct deduction.** Approve that request. → Casual *used* = 0.5 and Sick *used* = 0.5. Neither type is charged the full day, and the split is shown on the request and on the leave card. | ☐ |
| 4.16 | **Combined Leave — the split must add up.** Pick two days but enter 0.5 + 0.5. → The strip stays amber and Submit is disabled until the numbers match the days applied for. The same leave type cannot be used twice. | ☐ |
| 4.17 | **Combined Leave — every type is checked.** Combine a type the employee has balance in with one they have none of. → The request is refused, naming the empty type. | ☐ |
| 4.18 | **Combined Leave — cancel returns both halves.** Cancel an approved combined request. → Both leave types get their days back, not just the primary one. | ☐ |
| 4.13 | **Toggles persist per business.** Set the two switches, reload Leave Settings → they hold. Switch to another business → that business keeps its own values. | ☐ |

---

## 4a. Super Admin Masking

Actions taken by the **Super Admin** are shown to everyone else as **System Admin**. The stored record still points at
the real account, so a database audit is unaffected — only the display changes. A Super Admin sees real names, otherwise
they could not audit at all. Test this with two browsers: one signed in as Super Admin, one as a plain Admin.

| # | Test | ☐ |
|---|------|---|
| 4a.1 | **Activity log is masked.** As Super Admin, do something logged (edit a customer, create a lead). As a plain Admin open the Dashboard → **Recent Activity**. → The entry reads **System Admin**; the real name appears nowhere. | ☐ |
| 4a.2 | **A Super Admin sees the truth.** Open the same Dashboard as the Super Admin. → The same entry shows the real name. | ☐ |
| 4a.3 | **Ordinary admins are not masked.** An action by a plain Admin shows their real name to everyone. | ☐ |
| 4a.4 | **Approval and issuer trails.** As Super Admin, approve a leave request and issue a warning. As the employee, open both. → Each reads **System Admin**. | ☐ |
| 4a.5 | **The role is hidden.** As a plain Admin: Management → **Roles & Permissions**, and Users → Create User. → *Super Admin* is not listed, not selectable, and no user list shows a Super Admin account. | ☐ |
| 4a.6 | **The record underneath is intact.** With the developer, read the activity-log row in the database. → `causer_id` still points at the real Super Admin account. | ☐ |

---

## 4b. Performance Management (KRA / KPI)

The full appraisal cycle: masters → goal assignment → self-assessment → manager review → HR moderation → score, band and
reward. Everything is scoped to a **performance cycle**, and assessments are only accepted while that cycle is **Open**.

| # | Test | ☐ |
|---|------|---|
| 4b.1 | **Create a cycle.** HR → Performance (KRA/KPI) → **Performance Cycles → New Cycle** → name, frequency, period and the three review deadlines. → Saves as **Draft**. Open it before anything else will accept an assessment. | ☐ |
| 4b.2 | **KRA master.** Performance → **KRA Master → New KRA** → name, department/designation scope, default weightage, reviewer, frequency. → Appears in the list with its scope shown. | ☐ |
| 4b.3 | **KPI master.** Open the KRA → **Add KPI** → unit, target, weightage and score formula. → The worked example on the form shows how the chosen formula scores. KPIs under one KRA should total 100%. | ☐ |
| 4b.4 | **Assign goals — direct.** Performance → **Goal Assignment → Assign** → *Direct* → pick employees and KRAs. → Goals appear with the KPIs copied from the master. | ☐ |
| 4b.5 | **Assign goals — bulk / cascade.** Repeat with *Bulk* (a whole department) and *Cascade*. → Cascade gives each person only the KRAs matching their own department and designation. | ☐ |
| 4b.6 | **Copy forward.** Create a second cycle, then assign with *Copy Forward* from the first. → Goals and weightages come across; **scores do not**. | ☐ |
| 4b.7 | **The 100% weightage rule.** Performance → **Weightages**. → Anyone not totalling 100% is flagged. Edit the boxes and the total updates live; **Split evenly** lands exactly on 100. | ☐ |
| 4b.8 | **Weightage Excel round-trip.** Export the sheet, change the Weightage column, import it back. → Values update; rows that no longer match an employee + KRA are reported rather than guessed. | ☐ |
| 4b.9 | **Employee self-assessment.** Employee Portal → **My Goals → Start Self-Assessment** → rate each KRA, write the achievements, **Save Draft**. → Stays private and the goal does not move on. Then **Submit**. → It locks and moves to the manager. | ☐ |
| 4b.10 | **Evidence upload.** Attach a PDF/PNG/JPG (max 5 MB) against a KRA. → It lists under that goal and is visible to the manager and HR. After submitting, it can no longer be removed. | ☐ |
| 4b.11 | **Manager review.** Sign in as the reviewer → **Team Performance** → open the employee → enter achieved values per KPI, rate each KRA, write feedback, submit. → KPI scores compute from target vs achieved and the goal moves to HR. | ☐ |
| 4b.12 | **Send back.** As the manager, **Send Back to Employee**. → Their self-assessment reopens; the manager's own notes survive. As HR, send back to the manager → the employee's self-assessment survives. | ☐ |
| 4b.13 | **HR moderation.** Performance → **Review Desk** → open the employee → set a moderated rating with a reason. → A reason is required. The live score changes to reflect the moderated rating. | ☐ |
| 4b.14 | **Verify against the record.** On the same screen, check the attendance / penalty / warning panel. → Counts cover the cycle period only. | ☐ |
| 4b.15 | **Finalise.** Press **Finalise Review**. → A weighted score is computed, mapped to a band, and a reward is suggested. The goals close and the cycle's progress strip moves. | ☐ |
| 4b.16 | **The weighted formula.** With KRAs at 40/20/20/20 rated 5/4/3/2, the final score is **76.00**. → Confirms `(100×40 + 80×20 + 60×20 + 40×20) ÷ 100`. | ☐ |
| 4b.17 | **Bands.** Performance → **Bands & Bell Curve** → edit a range or a reward. → A score on a boundary (95) lands in the **better** band. | ☐ |
| 4b.18 | **Bell curve.** Turn it on, then **Apply** to a finalised cycle. → Scores are ranked and fitted to the configured shares; the earned band is **kept** and the curve band shown beside it. **Clear Curve** removes only the curve. | ☐ |
| 4b.19 | **Rewards.** Performance → **Rewards** → Accept, Override or Reject a recommendation. → Accepting or overriding writes an appraisal record (visible under HR → Appraisals); rejecting leaves none. Re-finalising does **not** undo your decision. | ☐ |
| 4b.20 | **Reports.** Performance → **Reports** → open each of the thirteen. → Each renders, filters by cycle and department, and exports to both Excel and PDF. | ☐ |
| 4b.21 | **Locking freezes everything.** Set the cycle to **Locked**, then try to submit an assessment, moderate or finalise. → Each is refused with a message naming the status. | ☐ |
| 4b.22 | **Roll forward.** On a cycle, press **Roll Forward**. → The next period is created with the same frequency and the deadlines offset the same way. | ☐ |
| 4b.23 | **Permissions.** Give a role only `performance_reviews.view`. → They can open the Review Desk but see no Finalise, Send Back or HR review controls. A role with none of the `performance_*` permissions gets no sidebar group and a 403 on the URLs. | ☐ |

---

## 4c. Documents & PDF Pack (42 documents)

Every document is produced on the shared **Letterhead Foundation** — company header, footer with page numbering,
authorised signature, company seal, amount in words in Indian format, and optional DRAFT / PAID / CANCELLED
watermarks. All 42 live behind one screen: **Documents → Document Pack**.

| # | Test | ☐ |
|---|------|---|
| 4c.1 | **Set up the letterhead.** Documents → **Letterhead** → enter the signatory name and designation, a footer line, and upload a signature and a seal (PNG, under 1 MB). → The preview on the right updates. A PDF or an oversized file is refused with a clear message. | ☐ |
| 4c.2 | **The letterhead appears on every document.** Generate any document. → The header carries the legal name, address, phone, GSTIN and PAN; the footer carries your line and **Page N of M**; the signature and seal print above the signatory block. | ☐ |
| 4c.3 | **Turning the letterhead off.** Letterhead → untick **Print the letterhead on documents** → generate again. → Header, footer and signature block are gone, so nothing overlaps pre-printed stationery. The document body is unchanged. | ☐ |
| 4c.4 | **Amount in words is Indian.** Generate a Salary Certificate, Payment Receipt or Reimbursement Voucher. → Amounts read in **lakh and crore**, e.g. ₹12,34,567.89 as *"Rupees Twelve Lakh Thirty Four Thousand Five Hundred Sixty Seven and Eighty Nine Paise Only"* — never million or billion. | ☐ |
| 4c.5 | **Watermarks.** On any document choose Watermark = **DRAFT**, then CANCELLED. → It prints diagonally across every page. The Payment Receipt defaults to **PAID**. | ☐ |
| 4c.6 | **Payroll & Statutory pack (5).** Generate Form 16, PF/ESI/PT Register, Bank Transfer Advice, Salary Register and the Bulk Payslip download for a month with payroll. → Each opens with real figures; the bulk download puts one payslip per page. | ☐ |
| 4c.7 | **Bank advice flags missing details.** Ensure one employee has no bank account or IFSC, then generate the Bank Transfer Advice. → They are listed in the file **and** named in a red panel as having incomplete bank details — not silently dropped. | ☐ |
| 4c.8 | **Attendance & Leave pack (6).** Generate the Muster Roll, Daily Attendance Report, Leave Card, and the Leave, Regularization and Comp-off approval slips. → The muster roll grids the whole month with a legend; a combined leave request shows its per-type break-up on the sanction slip. | ☐ |
| 4c.9 | **Reports & Modules pack (14).** Generate all fourteen — the five HR reports, budget vs actual, helpdesk, CRM pipeline, Report Builder export, reimbursement voucher, requisition note, service job card, asset gate pass and the depreciation schedule. → Each renders; the job card and gate pass leave blank fields for on-site completion. | ☐ |
| 4c.10 | **HR Letters pack (9).** Generate the Appointment, Confirmation, Experience, Relieving, Salary Certificate, Warning and Show-cause letters, the ID card and the Full & Final settlement. → Each addresses the employee by name with their real dates and designation. | ☐ |
| 4c.11 | **Salary Certificate annexure.** → The CTC break-up table is attached below the letter, and the annual CTC is repeated in words. | ☐ |
| 4c.12 | **Employee ID card.** → Front and back print on one A4 sheet at card size, with the photo, blood group, emergency contact and the return-if-found block. | ☐ |
| 4c.13 | **Sales & Finance pack (8).** Generate the Sales Order, Payment Receipt, GRN, Delivery Challan, Customer Statement, Vendor Statement, Stock Ledger and Credit/Debit Note. → Each renders with the right party details and totals. | ☐ |
| 4c.14 | **Customer statement runs a balance.** → Invoices and payments are interleaved by date with a running balance, and the closing balance is repeated in words. | ☐ |
| 4c.15 | **Preview vs download.** Use **Preview in Browser** and then **Download PDF**. → Preview opens in a tab; download saves a file named for the document and the record or period. | ☐ |
| 4c.16 | **Pack permissions.** Sign in as **Accounts**. → They can open the hub and generate the Sales & Finance pack, but HR Letters are not listed and their URL returns 403. Sign in as **HR Manager** → the reverse, and the Letterhead screen returns 403 for them. | ☐ |
| 4c.17 | **Cross-business isolation.** With a Super Admin, note a record id from another business and try to generate a document for it. → Refused — the record is not found in the current business. | ☐ |

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
| Leave Balance Gate + LWP exception | Leave Settings (per business) |
| Performance bands, rewards, bell curve | Performance → Bands & Bell Curve |
| KRA / KPI weightages and score formulas | Performance → KRA & KPI Master, Weightages |
| Letterhead, signature, seal, footer line | Documents → Letterhead |
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
