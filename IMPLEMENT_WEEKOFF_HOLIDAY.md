# Week-Off, Holiday & Comp-Off Implementation Spec
> Read this file fully before starting. All changes are described here.
> Already-done files are committed — just implement what's marked TODO.

---

## WHAT WAS DONE (already in code, do NOT redo)

### 1. Migration — `2026_05_08_100001_create_business_week_offs_table.php`
- Creates `business_week_offs` table: `business_id`, `day_of_week` (0=Sun..6=Sat), `is_off`
- Adds to `holidays` table: `is_yearly` (bool), `is_dynamic` (bool — **IGNORE, leave in DB but don't use in UI**), `employee_id` (nullable FK — **IGNORE**)

### 2. Model — `app/Models/BusinessWeekOff.php`
- `BelongsToBusiness` trait, `offDays()` static method returns array of off day numbers
- `saveConfig(array $days)` saves the whole week config

### 3. Model — `app/Models/Holiday.php` (modified)
- Added `is_yearly`, `is_dynamic`, `employee_id` fillable + casts
- Added `forYear(int $year)` static method — expands yearly holidays into any given year

### 4. Controller — `app/Http/Controllers/Admin/Hr/WeekOffController.php`
- `index()` — shows week-off config page
- `save()` — saves day toggles via POST

### 5. Controller — `app/Http/Controllers/Admin/Hr/HolidayController.php` (modified)
- `index()` — uses `Holiday::forYear($year)` instead of direct query
- `store()`/`update()` — handles `is_yearly` field
- Passes `$employees` to create/edit views

### 6. Service — `app/Services/AttendanceService.php` (modified)
- `monthlySummary()` now:
  - Reads week-off days from `BusinessWeekOff::offDays()` (not hardcoded Sunday)
  - Reads yearly holidays via `Holiday::forYear($year)`
  - Counts `fixed_week_offs`, `dynamic_week_offs` (comp-offs), `holidays` separately
  - Formula: `paidDays = present + paidLeave + fixedWeekOff + dynamicWeekOff + holidays`
  - Returns new keys: `fixed_week_offs`, `dynamic_week_offs`, `paid_leave_days`, `unpaid_leave_days`
- Old `unpaidLeaveDaysInMonth()` replaced by `splitLeaveDaysInMonth()` returning [paid, unpaid]
- **NOTE**: `dynamic_week_offs` currently reads from holidays table with `is_dynamic=true` — change this to read from `comp_off_requests` table (see TODO #3 below)

### 7. Service — `app/Services/PayrollService.php` (modified)
- `generate()` now uses `totalCalendarDays` (days in month) as denominator for proration
- Ratio = `paidDays / totalCalendarDays` (capped at 1.0)

### 8. Views — already created:
- `resources/views/admin/hr/week-off/index.blade.php` — interactive day-toggle cards UI
- `resources/views/admin/hr/holidays/index.blade.php` — upgraded with stats + yearly/dynamic badges
- `resources/views/admin/hr/holidays/_form.blade.php` — form with yearly toggle

---

## TODO — What Still Needs to Be Implemented

---

### TODO #1 — Run Migration
```bash
php artisan migrate
```

---

### TODO #2 — Add Routes (`routes/web.php`)

Inside the `Route::prefix('hr')->name('hr.')->group(...)` block, add:

```php
// Week-Off Configuration
Route::get('week-off', [HrWeekOffController::class, 'index'])->name('week-off.index');
Route::post('week-off', [HrWeekOffController::class, 'save'])->name('week-off.save');

// Comp-Off (Dynamic Week-Off)
Route::get('comp-off', [HrCompOffController::class, 'index'])->name('comp-off.index');
Route::post('comp-off/{compOff}/approve', [HrCompOffController::class, 'approve'])->name('comp-off.approve');
Route::post('comp-off/{compOff}/reject', [HrCompOffController::class, 'reject'])->name('comp-off.reject');
```

Also add in the employee routes block:
```php
Route::get('comp-off', [\App\Http\Controllers\Employee\CompOffController::class, 'index'])->name('comp-off.index');
Route::post('comp-off', [\App\Http\Controllers\Employee\CompOffController::class, 'store'])->name('comp-off.store');
Route::delete('comp-off/{compOff}', [\App\Http\Controllers\Employee\CompOffController::class, 'cancel'])->name('comp-off.cancel');
```

Add use statements at top of web.php:
```php
use App\Http\Controllers\Admin\Hr\WeekOffController as HrWeekOffController;
use App\Http\Controllers\Admin\Hr\CompOffController as HrCompOffController;
```

---

### TODO #3 — New Migration for Comp-Off

Create file: `database/migrations/2026_05_08_100002_create_comp_off_requests_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comp_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // The week-off day the employee actually worked
            $table->date('worked_on');
            // The working day the employee wants off in exchange
            $table->date('comp_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->string('admin_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('comp_date');
        });
    }

    public function down(): void {
        Schema::dropIfExists('comp_off_requests');
    }
};
```

---

### TODO #4 — New Model `app/Models/CompOffRequest.php`

```php
<?php
namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOffRequest extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'employee_id', 'worked_on', 'comp_date',
        'reason', 'status', 'approved_by', 'actioned_at', 'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
            'worked_on'    => 'date',
            'comp_date'    => 'date',
            'actioned_at'  => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo { return $this->belongsTo(Admin::class, 'approved_by'); }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
}
```

---

### TODO #5 — Admin Controller `app/Http/Controllers/Admin/Hr/CompOffController.php`

```php
<?php
namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\CompOffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        $compOffs = CompOffRequest::with('employee')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();

        return view('admin.hr.comp-off.index', compact('compOffs'));
    }

    public function approve(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status'       => 'approved',
            'approved_by'  => Auth::guard('admin')->id(),
            'actioned_at'  => now(),
            'admin_remarks'=> $request->input('admin_remarks'),
        ]);

        return back()->with('success', 'Comp-off approved. The day will be counted as paid.');
    }

    public function reject(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status'       => 'rejected',
            'approved_by'  => Auth::guard('admin')->id(),
            'actioned_at'  => now(),
            'admin_remarks'=> $request->input('admin_remarks'),
        ]);

        return back()->with('success', 'Comp-off rejected.');
    }
}
```

---

### TODO #6 — Employee Controller `app/Http/Controllers/Employee/CompOffController.php`

```php
<?php
namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CompOffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function index()
    {
        $employee  = Auth::guard('employee')->user();
        $compOffs  = CompOffRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')->paginate(20);

        return view('employee.comp-off.index', compact('compOffs'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $data = $request->validate([
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            'comp_date' => ['required', 'date', 'after:worked_on'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['status']      = 'pending';

        CompOffRequest::create($data);

        return back()->with('success', 'Comp-off request submitted. Pending approval.');
    }

    public function cancel(CompOffRequest $compOff)
    {
        abort_unless($compOff->employee_id === Auth::guard('employee')->id(), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update(['status' => 'cancelled']);

        return back()->with('success', 'Comp-off request cancelled.');
    }
}
```

---

### TODO #7 — Update `AttendanceService::monthlySummary()` dynamic_week_offs line

Currently the code reads `dynamic_week_offs` from holidays table. Replace that section to read from `comp_off_requests` instead.

Find this block in `app/Services/AttendanceService.php`:
```php
// Dynamic week-offs for this employee (worked on a week-off day and swapped it)
$dynamicHolidayDates = $allHolidays
    ->where('is_dynamic', true)
    ->filter(fn ($h) => $h->employee_id === null || $h->employee_id === $employeeId)
    ->map(fn ($h) => $h->date->toDateString())
    ->flip();
```

Replace with:
```php
// Comp-off (dynamic week-off): approved comp-off dates are paid days
$dynamicHolidayDates = \App\Models\CompOffRequest::where('employee_id', $employeeId)
    ->where('status', 'approved')
    ->whereBetween('comp_date', [$start->toDateString(), $end->toDateString()])
    ->pluck('comp_date')
    ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
    ->flip();
```

---

### TODO #8 — Admin View `resources/views/admin/hr/comp-off/index.blade.php`

Create this file. It should:
- Show a table of all comp-off requests with columns: Employee, Worked On (week-off day), Comp Date (day off taken), Reason, Status badge, Action buttons
- Status badges: pending=warning, approved=success, rejected=danger
- For pending rows: show Approve / Reject buttons (POST forms with optional admin_remarks input)
- Filter bar at top: filter by status dropdown
- Use existing panel/table-striped/btn classes (same as other HR views)

Example row structure:
```
| Rahul Sharma (EMP001) | Sunday 04 May 2026 | Monday 05 May 2026 | Worked for project deadline | [Pending] | [Approve] [Reject] |
```

---

### TODO #9 — Employee View `resources/views/employee/comp-off/index.blade.php`

Create this file. It should:
- Show a form at top to submit new comp-off request (worked_on date, comp_date, reason)
- Below: table of their own comp-off history (status, dates, admin remarks)
- Use employee layout: `<x-layout.employee title="Comp-Off Requests">`

---

### TODO #10 — Holiday create/edit views

`resources/views/admin/hr/holidays/create.blade.php`:
```blade
<x-layout.admin title="Add Holiday">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holidays', 'url' => route('admin.hr.holidays.index')], ['label' => 'Add']]" />
    <h1 class="text-2xl font-extrabold mb-5">Add Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.store') }}">
        @csrf
        @include('admin.hr.holidays._form', ['employees' => $employees])
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Save Holiday</button>
            <a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
```

`resources/views/admin/hr/holidays/edit.blade.php`:
```blade
<x-layout.admin title="Edit Holiday">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holidays', 'url' => route('admin.hr.holidays.index')], ['label' => 'Edit']]" />
    <h1 class="text-2xl font-extrabold mb-5">Edit Holiday</h1>
    <form method="POST" action="{{ route('admin.hr.holidays.update', $holiday) }}">
        @csrf @method('PUT')
        @include('admin.hr.holidays._form', ['holiday' => $holiday, 'employees' => $employees])
        <div class="flex gap-3 mt-5">
            <button class="btn btn-primary">Update Holiday</button>
            <a href="{{ route('admin.hr.holidays.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
```

---

### TODO #11 — Sidebar Nav (`resources/views/components/admin/sidebar.blade.php`)

Find the line with `Holiday Calendar` and add Week-Off + Comp-Off links nearby:

```blade
@can('holidays.view')
    <li><a href="{{ route('admin.hr.holidays.index') }}">Holiday Calendar</a></li>
    <li><a href="{{ route('admin.hr.week-off.index') }}">Week-Off Setup</a></li>
    <li><a href="{{ route('admin.hr.comp-off.index') }}">Comp-Off Requests</a></li>
@endcan
```

Also add in employee sidebar (find the leaves section):
```blade
<li><a href="{{ route('employee.comp-off.index') }}">Comp-Off</a></li>
```

---

### TODO #12 — Salary Formula Summary (for reference/testing)

The final `paidDays` formula in `AttendanceService::monthlySummary()`:

```
paidDays = present + late + paidLeave + (halfDay × 0.5) + fixedWeekOffs + dynamicWeekOffs + holidayCount

Where:
  present        = days marked present (not on weekoff/holiday)
  late           = days marked late (still paid)
  paidLeave      = paid portion of approved leave requests
  halfDay × 0.5  = half days
  fixedWeekOffs  = configured week-off days (e.g. Sunday) in the month
  dynamicWeekOffs= approved comp-off comp_dates falling in this month
  holidayCount   = public holidays in the month

unpaidDays = absent + unpaidLeave  → these cause LOP deduction
```

Salary proration = `paidDays / daysInMonth` (capped at 1.0)

---

### QUICK CHECKLIST (run in order)

- [ ] `php artisan migrate`
- [ ] Create `comp_off_requests` migration (TODO #3)
- [ ] Create `CompOffRequest` model (TODO #4)
- [ ] Create admin `CompOffController` (TODO #5)
- [ ] Create employee `CompOffController` (TODO #6)
- [ ] Fix `AttendanceService` dynamic_week_offs source (TODO #7)
- [ ] Add routes (TODO #2)
- [ ] Create admin comp-off view (TODO #8)
- [ ] Create employee comp-off view (TODO #9)
- [ ] Create holiday create/edit views (TODO #10)
- [ ] Update sidebar nav (TODO #11)
- [ ] Test: create a comp-off request → approve → generate payslip → verify paid_days includes comp_date
