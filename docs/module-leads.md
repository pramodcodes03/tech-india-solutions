# Lead Management

## Purpose

The Lead Management module tracks prospective customers from initial contact through conversion. It provides both a traditional list view and a visual kanban board for pipeline management.

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Code | text | Auto-generated |
| Name | text | Required |
| Company | text | Optional |
| Phone | text | Optional |
| Email | email | Optional |
| Source | select | Website, Referral, Cold Call, Exhibition, Other |
| Status | select | new, contacted, qualified, proposal, negotiation, won, lost |
| Assigned To | select | Admin user |
| Expected Value | decimal | Estimated deal value |
| Next Follow-up | datetime | Scheduled follow-up date |
| Notes | textarea | Internal notes |

## Leads Board

The kanban board displays leads organized by status columns: New, Contacted, Qualified, Proposal, Negotiation, Won, Lost. Leads can be dragged between columns to update their status via AJAX.

Access the Leads Board at **Leads > Kanban Board** or via the route `/admin/leads/kanban`.

## Lead-to-Customer Conversion

When a lead reaches the "won" stage (or at any point), it can be converted to a customer:

1. Click the **Convert to Customer** button on the lead detail page
2. The system creates a new Customer record pre-populated with the lead's name, company, phone, and email
3. The lead status is updated to "won" and marked as converted
4. A link to the newly created customer is displayed

This is a one-way operation. The converted customer can then be used for quotations and sales orders.

## Lead Activities

Each lead maintains a timeline of activities tracked in the `lead_activities` table. Activities include status changes, follow-up notes, and conversion events. This provides a full history of interactions with the prospect.

## Permissions

| Permission | Description |
|-----------|-------------|
| `leads.view` | View lead list, kanban, and details |
| `leads.create` | Create new leads |
| `leads.edit` | Edit existing leads |
| `leads.delete` | Delete leads (soft delete) |
| `leads.convert` | Convert a lead to a customer |

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/leads` | List leads |
| GET | `/admin/leads/kanban` | Kanban board |
| POST | `/admin/leads/{lead}/convert` | Convert to customer |
| PATCH | `/admin/leads/{lead}/status` | Update status (AJAX) |
