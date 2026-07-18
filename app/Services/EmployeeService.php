<?php

namespace App\Services;

use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeService
{
    public function generateCode(): string
    {
        $prefix = app(\App\Support\Tenancy\CurrentBusiness::class)->get()?->employee_code_prefix ?? 'EMP';
        $last = Employee::withTrashed()
            ->where('employee_code', 'like', $prefix.'%')
            ->orderByDesc('employee_code')
            ->first();

        $next = $last ? (int) substr($last->employee_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            // Honour an admin-provided code; fall back to auto-generated.
            if (empty($data['employee_code'])) {
                $data['employee_code'] = $this->generateCode();
            }

            // Reuse identifiers freed up by a previously-deleted employee.
            // Active-record uniqueness is enforced by the form request; here we
            // purge any SOFT-DELETED employee that still holds one of the
            // DB-level unique keys (email / employee_code / legacy_employee_id /
            // card_no) so the fresh insert can't hit a duplicate-key error. The
            // old record and all its data are permanently removed first.
            $this->purgeTrashedConflicts($data);
            $data['created_by'] = Auth::guard('admin')->id();

            // Default password is the employee code (employee must change on first login)
            if (empty($data['password'])) {
                $data['password'] = $data['employee_code'];
            }

            // Handle profile photo upload
            if (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
                $data['profile_photo'] = $data['profile_photo']->store('profile-photos', 'public');
            } else {
                unset($data['profile_photo']);
            }

            $employee = Employee::create($data);

            // Provision leave balances for the current year
            $this->allocateAnnualLeaves($employee, (int) date('Y'));

            return $employee;
        });
    }

    /**
     * Permanently remove any soft-deleted employee(s) whose unique identifier
     * would collide with the incoming data, so a fresh employee can reuse those
     * values. Only trashed rows are touched — an active employee holding one of
     * these values is already blocked by the form request's uniqueness rules.
     *
     * The unique keys mirror the DB constraints: email, employee_code,
     * legacy_employee_id (all global) and card_no (per business). We strip the
     * tenant scope so a deleted row in the same business is found even before an
     * active-business context is fully resolved.
     */
    private function purgeTrashedConflicts(array $data): void
    {
        $query = Employee::withoutGlobalScopes()->onlyTrashed();

        $matched = false;
        $query->where(function ($q) use ($data, &$matched) {
            foreach (['email', 'employee_code', 'legacy_employee_id', 'card_no'] as $col) {
                if (! empty($data[$col])) {
                    $q->orWhere($col, $data[$col]);
                    $matched = true;
                }
            }
        });

        // No identifier to match on → nothing to purge (avoids a bare WHERE that
        // would match every trashed row).
        if (! $matched) {
            return;
        }

        foreach ($query->get() as $trashed) {
            $this->hardDelete($trashed);
        }
    }

    public function update(Employee $employee, array $data): Employee
    {
        $data['updated_by'] = Auth::guard('admin')->id();

        // Only update password if explicitly provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Handle profile photo upload
        if (isset($data['profile_photo']) && $data['profile_photo'] instanceof \Illuminate\Http\UploadedFile) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $data['profile_photo'] = $data['profile_photo']->store('profile-photos', 'public');
        } else {
            unset($data['profile_photo']);
        }

        $employee->update($data);

        return $employee->refresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->update(['deleted_by' => Auth::guard('admin')->id(), 'status' => 'inactive']);
        $employee->delete();
    }

    /**
     * Toggle status between active and inactive without deleting.
     */
    public function toggleStatus(Employee $employee): Employee
    {
        $next = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->update([
            'status' => $next,
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return $employee->refresh();
    }

    /**
     * Permanently delete the employee and all related records.
     *
     * Cascades handle most child rows (attendance, payslips, leave_*,
     * salary_structures, warnings, penalties, appraisals, documents,
     * bank_detail_edit_requests, department_feedback). Two FKs need manual
     * handling:
     *   - asset_assignments.employee_id is restrictOnDelete → delete first
     *   - assets.current_custodian_id is nullOnDelete (auto-handled)
     *   - asset_maintenance_logs.performed_by_employee_id is nullOnDelete (auto)
     *   - employees.reporting_manager_id is nullOnDelete (auto)
     */
    public function hardDelete(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            AssetAssignment::where('employee_id', $employee->id)->delete();

            $photo = $employee->profile_photo;

            $employee->forceDelete();

            if ($photo && Storage::disk('public')->exists($photo)) {
                Storage::disk('public')->delete($photo);
            }
        });
    }

    public function resetPassword(Employee $employee, ?string $newPassword = null): string
    {
        $password = $newPassword ?: $employee->employee_code;
        $employee->update(['password' => Hash::make($password)]);

        return $password;
    }

    /**
     * Allocate annual leave quotas to an employee for a given year.
     * Prorates based on joining date if joined mid-year.
     *
     * IMPORTANT — this is the "upfront annual quota" path, and it deliberately
     * does NOT grant leave for types that accrue monthly. A leave type with
     * accrual_enabled is delivered 0.5/month by LeaveAccrualService; granting
     * its full annual_quota here as well double-counted the entitlement (an
     * employee ended up with up to 2× the policy). Only pure annual-quota types
     * (accrual switched off) are seeded here.
     *
     * It is also non-destructive and gate-aware:
     *  - never lowers an existing allocation, so accrued days are never wiped
     *    if this runs again (Bulk Allocate is safe to press twice);
     *  - honours the same working-days eligibility gate as accrual and the
     *    apply screen, so EL can't be handed to someone who hasn't qualified.
     *
     * Safe to call from the nightly scheduler and from the admin UI.
     */
    public function allocateAnnualLeaves(Employee $employee, int $year): void
    {
        // Business-explicit (+ scope-free) so this works both in the admin UI
        // and from the nightly scheduler, which has no active business context.
        $types = LeaveType::withoutGlobalScopes()
            ->where('business_id', $employee->business_id)
            ->where('status', 'active')
            ->get();

        $joinedThisYear = $employee->joining_date && $employee->joining_date->year === $year;
        $monthsWorked = $joinedThisYear
            ? max(1, 12 - $employee->joining_date->month + 1)
            : 12;

        // Employees still on probation do not accrue PAID leave — it's allocated
        // once they're confirmed (manually via EmployeeController::update, or
        // automatically by the nightly probation-completion job). Unpaid (LWP)
        // types are unaffected.
        $onProbation = $employee->isOnProbation();
        $eligibility = app(LeaveEligibilityService::class);

        foreach ($types as $type) {
            // Monthly-accrual types are owned by LeaveAccrualService. Seed the
            // balance row so it exists, but never pre-fill it here.
            $accrues = (bool) $type->accrual_enabled;

            $allocated = 0.0;
            if (! $accrues && ! ($type->is_paid && $onProbation) && $type->annual_quota > 0) {
                // Working-days gate (CL & SL vs EL buckets, employee →
                // department → business). Not yet qualified ⇒ no upfront grant.
                if ($eligibility->isEligible($employee, $type)) {
                    $allocated = round(($type->annual_quota * $monthsWorked) / 12, 1);
                }
            }

            $balance = LeaveBalance::withoutGlobalScopes()->firstOrNew([
                'business_id' => $employee->business_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ]);

            // Never reduce what's already there — accrual may have credited days
            // since, and re-running this must not claw them back.
            $balance->allocated = max((float) $balance->allocated, $allocated);
            $balance->save();
        }
    }

    /**
     * Nightly job: confirm employees whose probation period has completed.
     * Probation end = the employee's own probation_end_date, else joining_date
     * + the global default (HR Settings → Probation Period Days). On completion
     * the employee is flipped to 'active', stamped with a confirmation_date, and
     * their leave quotas are allocated for ALL active leave types.
     *
     * @return int number of employees confirmed
     */
    public function confirmCompletedProbations(?\Carbon\Carbon $asOf = null): int
    {
        $asOf = $asOf ?? now();
        $defaultDays = \App\Support\HrSettings::int('probation_period_days', 90);
        $confirmed = 0;

        Employee::withoutGlobalScopes()
            ->where('status', 'probation')
            ->whereNull('confirmation_date')
            ->whereNotNull('joining_date')
            ->get()
            ->each(function (Employee $emp) use ($asOf, $defaultDays, &$confirmed) {
                // The employee's own probation end date wins; else DOJ + default.
                $probEnd = $emp->probation_end_date
                    ?: $emp->joining_date->copy()->addDays($defaultDays);

                if ($asOf->lt($probEnd)) {
                    return; // probation not complete yet
                }

                $emp->update([
                    'status' => 'active',
                    'confirmation_date' => $probEnd->toDateString(),
                ]);

                // Allocate leaves now that they're confirmed (all leave types).
                $this->allocateAnnualLeaves($emp->fresh(), (int) $asOf->year);
                $confirmed++;
            });

        return $confirmed;
    }
}
