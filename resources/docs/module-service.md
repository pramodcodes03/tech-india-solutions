# Service Module

## Purpose

The Service module manages after-sales service tickets. It tracks customer issues from initial report through resolution, with technician assignment and a threaded comment system for internal communication.

## Ticket Lifecycle

```
open → in_progress → resolved → closed
```

| Status | Description |
|--------|-------------|
| open | Ticket created, awaiting assignment or action |
| in_progress | Technician assigned and working on the issue |
| resolved | Issue resolved, pending customer confirmation |
| closed | Ticket closed after resolution verified |

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Ticket Number | text | Auto-generated |
| Customer | select | Required |
| Product | select | Optional, the product with the issue |
| Issue Description | textarea | Required, detailed problem description |
| Priority | select | low, medium, high, critical |
| Status | select | open, in_progress, resolved, closed |
| Assigned To | select | Admin user (technician) |
| Opened At | datetime | When the ticket was opened |
| Closed At | datetime | When the ticket was closed |
| Resolution Notes | textarea | How the issue was resolved |

## Priority Levels

| Priority | Description | Expected Response |
|----------|-------------|-------------------|
| Low | Minor issue, no business impact | Within 48 hours |
| Medium | Moderate issue, workaround exists | Within 24 hours |
| High | Significant issue, affects operations | Within 8 hours |
| Critical | Business-stopping issue | Immediate |

## Technician Assignment

Tickets can be assigned to any admin user. The assigned technician sees their tickets in the dashboard and receives responsibility for resolution. Assignment can be changed at any time by users with edit permission.

## Comments

Service tickets support threaded comments for internal communication:

- Any authorized user can add comments to a ticket
- Comments include the author name and timestamp
- Comments are displayed in chronological order on the ticket detail page
- Use comments to track troubleshooting steps, customer communication, and resolution progress

## Permissions

| Permission | Description |
|-----------|-------------|
| `service_tickets.view` | View ticket list and details |
| `service_tickets.create` | Create new tickets |
| `service_tickets.edit` | Edit tickets, change status, assign |
| `service_tickets.delete` | Delete tickets |

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/service-tickets` | List tickets |
| POST | `/admin/service-tickets` | Store ticket |
| GET | `/admin/service-tickets/{id}` | View details |
| PUT | `/admin/service-tickets/{id}` | Update ticket |
| DELETE | `/admin/service-tickets/{id}` | Delete ticket |
| POST | `/admin/service-tickets/{id}/comments` | Add comment |
