<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Resolves recipient role keys (e.g. "customer.email", "admin.role:Sales")
 * into a list of [email, name] pairs.
 *
 * Returns Collection<array{email,name}>. Null/missing emails are filtered out.
 */
class RecipientResolver
{
    /**
     * @param  string|null  $permission  Module permission this event belongs to
     *                                   (e.g. 'leads.view'). When set, admins who
     *                                   don't hold it are never notified — the
     *                                   all-admins fallback is filtered down to
     *                                   people who actually work that module.
     */
    public function resolve(array $roleKeys, ?Model $entity, Business $business, array $context = [], ?string $permission = null): Collection
    {
        $recipients = collect();

        foreach ($roleKeys as $role) {
            $resolved = $this->resolveOne($role, $entity, $business, $context, $permission);
            $recipients = $recipients->merge($resolved);
        }

        // Fallback for internal-staff-only events: if none of the requested
        // recipients are external (customer/vendor/employee), and we ended
        // up with nobody, fall back to the business's admins + super admins.
        // This catches the common case where the event wants HR Manager /
        // reporting_manager but the business hasn't assigned anyone to that
        // role yet — without the fallback the notification silently fails
        // with "No recipients resolved".
        //
        // When the event declares a permission, the fallback is restricted to
        // admins holding it. Otherwise an unassigned lead's status change would
        // email every admin in the business — including people with no access
        // to the Leads module at all.
        //
        // External-recipient events (invoice to customer, payslip to
        // employee, etc.) intentionally do NOT fall back to admins —
        // we don't want to email the whole staff because one customer
        // record is missing an email.
        if ($recipients->isEmpty() && ! $this->hasExternalRecipient($roleKeys)) {
            $recipients = $this->allAdmins($business, $permission)->merge($this->superAdmins());
        }

        // Super-admin oversight: any event that targets internal admin staff
        // (a role, the creator, or all-admins) is ALSO sent to super admins,
        // so they receive every important notification across all businesses.
        // Purely external events (only customer / vendor / employee) are left
        // alone — super admins aren't CC'd on a customer's invoice email.
        if ($this->targetsInternalAdmin($roleKeys)) {
            $recipients = $recipients->merge($this->superAdmins());
        }

        // De-dupe by email (case-insensitive).
        return $recipients
            ->filter(fn ($r) => ! empty($r['email']))
            ->unique(fn ($r) => strtolower($r['email']))
            ->values();
    }

    /**
     * True if any requested recipient targets internal admin staff — an
     * admin.role:*, admin.creator, admin.all, or admin.super key.
     */
    protected function targetsInternalAdmin(array $roleKeys): bool
    {
        return collect($roleKeys)->contains(fn ($r) => str_starts_with($r, 'admin.'));
    }

    /**
     * True if any of the requested recipient role keys targets an external
     * party (the customer, vendor, or employee on the entity) rather than
     * internal staff. Used by the fallback to decide whether spamming all
     * admins is the right safety net.
     */
    protected function hasExternalRecipient(array $roleKeys): bool
    {
        $externalPrefixes = ['customer.', 'vendor.', 'employee.', 'asset_assignment.employee'];

        return collect($roleKeys)->contains(fn ($r) => collect($externalPrefixes)
            ->contains(fn ($p) => str_starts_with($r, $p)));
    }

    protected function resolveOne(string $role, ?Model $entity, Business $business, array $context, ?string $permission = null): Collection
    {
        // Role with parameter: admin.role:HR Manager
        $param = null;
        if (str_contains($role, ':')) {
            [$role, $param] = explode(':', $role, 2);
        }

        return match ($role) {
            'customer.email' => $this->fromRelation($entity, 'customer', ['name', 'company']),
            'vendor.email' => $this->fromRelation($entity, 'vendor', ['name', 'company']),
            'employee.email' => $this->fromEmployee($entity, prefer: 'work'),
            'employee.personal' => $this->fromEmployee($entity, prefer: 'personal'),
            'admin.creator' => $this->fromRelation($entity, 'creator'),
            'admin.all' => $this->allAdmins($business, $permission),
            'admin.role' => $this->adminsByRole($business, (string) $param, $permission),
            'admin.permission' => $this->adminsByPermission($business, (string) $param),
            'admin.super' => $this->superAdmins(),
            'reporting_manager' => $this->reportingManager($entity),
            // Reporting manager, or — when the employee has none — the business
            // admins (+ super admins) so leave / comp-off is never left unrouted.
            'reporting_manager_or_admin' => $this->reportingManagerOrAdmin($entity, $business),
            'lead.assignee' => $this->fromRelation($entity, 'assignedTo'),
            'lead.assignee_manager' => $this->reportingManagerOf(
                $entity?->assignedTo ?? null
            ),
            'ticket.assignee' => $this->fromRelation($entity, 'assignedTo'),
            'asset_assignment.employee' => $this->fromRelation($entity, 'employee'),
            'salary_structure.submitter' => $this->fromRelation($entity, 'submitter'),
            'bank_edit.requester' => $this->fromRelation($entity, 'requester'),
            // A literal email taken from a Settings row, e.g.
            // setting.email:feedback_notification_email — lets admins configure a
            // fixed mailbox (no user account needed) to receive certain events.
            'setting.email' => $this->fromSettingEmail((string) $param),
            // The Owner configured on the matched escalation-matrix level for
            // this ticket's current level — so an escalated ticket auto-mails
            // exactly the person set to own that level.
            'escalation.owner' => $this->escalationOwner($entity),
            default => collect(), // Unknown role → silently ignore
        };
    }

    /**
     * Resolve a recipient from a Settings row holding an email address. Returns
     * empty when the setting is unset/blank so the event still falls through to
     * its other recipients (e.g. HR Manager) instead of failing.
     */
    protected function fromSettingEmail(string $settingKey): Collection
    {
        if ($settingKey === '') {
            return collect();
        }

        $email = \App\Models\Setting::where('key', $settingKey)->value('value');
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return collect();
        }

        return collect([[
            'email' => $email,
            'name'  => 'Company',
        ]]);
    }

    /**
     * Resolve the Owner of the escalation-matrix level that matches this
     * ticket's current level (business + department + level). Runs from the
     * scheduler (no tenant context), so global scopes are stripped and the
     * business is filtered explicitly. Empty when no level/owner is configured.
     */
    protected function escalationOwner(?Model $entity): Collection
    {
        if (! $entity || empty($entity->escalation_level) || empty($entity->department)) {
            return collect();
        }

        $level = \App\Models\TicketEscalationLevel::withoutGlobalScopes()
            ->where('business_id', $entity->business_id)
            ->where('department', $entity->department)
            ->where('level', $entity->escalation_level)
            ->first();

        if (! $level || ! $level->owner_admin_id) {
            return collect();
        }

        $admin = \App\Models\Admin::withoutGlobalScopes()->find($level->owner_admin_id);

        return ($admin && $admin->email)
            ? collect([['email' => $admin->email, 'name' => $admin->name]])
            : collect();
    }

    protected function fromRelation(?Model $entity, string $relation, array $nameFields = ['name']): Collection
    {
        if (! $entity || ! method_exists($entity, $relation)) {
            return collect();
        }
        $related = $entity->{$relation};
        if (! $related || empty($related->email)) {
            return collect();
        }

        $name = null;
        foreach ($nameFields as $f) {
            if (! empty($related->{$f})) {
                $name = $related->{$f};
                break;
            }
        }

        return collect([['email' => $related->email, 'name' => $name]]);
    }

    protected function fromEmployee(?Model $entity, string $prefer = 'work'): Collection
    {
        if (! $entity) {
            return collect();
        }

        // If the entity IS an employee, use it directly; else look for ->employee.
        $employee = $entity->getTable() === 'employees'
            ? $entity
            : ($entity->employee ?? null);

        if (! $employee) {
            return collect();
        }

        $email = $prefer === 'personal'
            ? ($employee->personal_email ?? $employee->email)
            : ($employee->email ?? $employee->personal_email);

        if (! $email) {
            return collect();
        }

        $fullName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));

        return collect([['email' => $email, 'name' => $fullName ?: null]]);
    }

    /**
     * Active admins of the business. When $permission is given, only those who
     * actually hold it (directly or via a role) are returned — so a module's
     * notifications never reach staff without access to that module.
     */
    protected function allAdmins(Business $business, ?string $permission = null): Collection
    {
        return Admin::where('business_id', $business->id)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->when($permission, fn ($q) => $q->permission($permission))
            ->get(['name', 'email'])
            ->map(fn ($a) => ['email' => $a->email, 'name' => $a->name]);
    }

    /**
     * Admins holding a named role. A $permission (when the event declares one)
     * further narrows it, so a role that no longer has module access stops
     * receiving that module's mail.
     */
    protected function adminsByRole(Business $business, string $roleName, ?string $permission = null): Collection
    {
        if (! $roleName) {
            return collect();
        }

        return Admin::where('business_id', $business->id)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->when($permission, fn ($q) => $q->permission($permission))
            ->get(['name', 'email'])
            ->map(fn ($a) => ['email' => $a->email, 'name' => $a->name]);
    }

    /**
     * Every active admin of the business holding a specific permission — the
     * explicit form of the same idea, usable directly from the catalog as
     * `admin.permission:leads.view`.
     */
    protected function adminsByPermission(Business $business, string $permission): Collection
    {
        if (! $permission) {
            return collect();
        }

        return $this->allAdmins($business, $permission);
    }

    protected function superAdmins(): Collection
    {
        return Admin::whereNull('business_id')
            ->where('status', 'active')
            ->whereNotNull('email')
            ->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->get(['name', 'email'])
            ->map(fn ($a) => ['email' => $a->email, 'name' => $a->name]);
    }

    /**
     * The employee's reporting manager, or the business admins + super admins
     * when the employee has no manager — so requests from manager-less employees
     * are routed to the main business admin for approval.
     */
    protected function reportingManagerOrAdmin(?Model $entity, Business $business): Collection
    {
        $manager = $this->reportingManager($entity);
        if ($manager->isNotEmpty()) {
            return $manager;
        }

        // Start from a base collection so merging the (Eloquent) role collections
        // of recipient arrays doesn't invoke Eloquent's key-based merge.
        return collect()
            ->merge($this->adminsByRole($business, 'Business Admin'))
            ->merge($this->adminsByRole($business, 'Admin'))
            ->merge($this->superAdmins());
    }

    protected function reportingManager(?Model $entity): Collection
    {
        if (! $entity) {
            return collect();
        }

        $employee = $entity->getTable() === 'employees'
            ? $entity
            : ($entity->employee ?? null);

        return $this->reportingManagerOf($employee);
    }

    protected function reportingManagerOf($employee): Collection
    {
        if (! $employee || ! $employee->reporting_manager_id) {
            return collect();
        }
        $manager = $employee->reportingManager;
        if (! $manager || ! $manager->email) {
            return collect();
        }
        $name = trim(($manager->first_name ?? '').' '.($manager->last_name ?? ''));

        return collect([['email' => $manager->email, 'name' => $name ?: null]]);
    }
}
