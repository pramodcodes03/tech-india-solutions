# User Management

## Purpose

The User Management module allows administrators to create, edit, and manage admin panel users. Each user is assigned a role that determines their access to modules and actions within the ERP.

## Screens

- **User List** — Paginated table of all admin users with name, email, role, status, and actions
- **Create User** — Form to add a new admin user with role assignment
- **Edit User** — Modify user details and role
- **Toggle Status** — Activate or deactivate a user without deleting

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Name | text | Required, max 255 |
| Email | email | Required, unique |
| Password | password | Required on create, min 8 chars |
| Phone | text | Optional |
| Role | select | Required, must be a valid role |
| Status | toggle | active / inactive |

## Business Rules

### Last Admin Protection

The system prevents deletion or deactivation of the last remaining Super Admin. If only one Super Admin exists, the toggle-status and delete actions are blocked with an appropriate error message.

### Self-Deactivation Prevention

A logged-in admin cannot deactivate or delete their own account. This prevents accidental lockout.

### Password Handling

- Passwords are hashed using `bcrypt` before storage
- On edit, the password field is optional; leaving it blank retains the current password
- A separate "Change Password" endpoint allows any logged-in user to change their own password

## Permissions

| Permission | Description |
|-----------|-------------|
| `users.view` | View user list and details |
| `users.create` | Create new users |
| `users.edit` | Edit existing users |
| `users.delete` | Delete users |

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/admin-users` | List users |
| GET | `/admin/admin-users/create` | Create form |
| POST | `/admin/admin-users` | Store user |
| GET | `/admin/admin-users/{id}/edit` | Edit form |
| PUT | `/admin/admin-users/{id}` | Update user |
| DELETE | `/admin/admin-users/{id}` | Delete user |
| PATCH | `/admin/admin-users/{id}/toggle-status` | Toggle active/inactive |
| POST | `/admin/change-password` | Change own password |
