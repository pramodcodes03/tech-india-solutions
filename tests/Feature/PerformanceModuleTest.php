<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hr\Performance\ReportController;
use App\Models\Admin;
use App\Models\Business;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\EmployeeKra;
use App\Models\Kpi;
use App\Models\Kra;
use App\Models\PerformanceBand;
use App\Models\PerformanceCycle;
use App\Models\PerformanceDocument;
use App\Models\PerformanceHistory;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Models\PerformanceSelfReview;
use App\Models\Warning;
use App\Services\Performance\PerformanceAssignmentService;
use App\Services\Performance\PerformanceScoringService;
use App\Services\Performance\PerformanceWorkflowService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Module A — Performance Management (KRA / KPI).
 *
 * The scoring chain is the part worth proving: KPI → KRA → weighted overall
 * score → band → recommended reward, with HR moderation and the bell curve on
 * top. The workflow tests cover the Employee → Manager → HR escalation and the
 * send-back that has to reopen exactly one stage.
 */
class PerformanceModuleTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected Employee $employee;

    protected Employee $manager;

    protected Department $department;

    protected PerformanceCycle $cycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->business = Business::create([
            'name' => 'Perf Co', 'slug' => 'perf-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'HR Admin', 'email' => 'hr@perf.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Sales', 'code' => 'SLS',
        ]);

        $this->manager = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-MGR',
            'email' => 'mgr@perf.test', 'first_name' => 'Meera', 'last_name' => 'Rao',
            'department_id' => $this->department->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2024-01-01',
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@perf.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $this->department->id,
            'reporting_manager_id' => $this->manager->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);

        $this->cycle = $this->makeCycle();
        $this->seedBands();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function makeCycle(string $status = 'open', string $name = 'Q3 2026'): PerformanceCycle
    {
        return PerformanceCycle::create([
            'business_id' => $this->business->id,
            'name' => $name,
            'frequency' => 'quarterly',
            'period_start' => '2026-07-01',
            'period_end' => '2026-09-30',
            'status' => $status,
        ]);
    }

    /** The proposal's six bands, as the migration seeds them. */
    private function seedBands(): void
    {
        $bands = [
            ['Outstanding', 95, 100, 'promotion', 10],
            ['Excellent', 85, 94.99, 'increment', 20],
            ['Very Good', 75, 84.99, 'bonus', null],
            ['Good', 60, 74.99, 'none', 50],
            ['Needs Improvement', 40, 59.99, 'training', 15],
            ['Unsatisfactory', 0, 39.99, 'pip', 5],
        ];

        foreach ($bands as $i => [$name, $min, $max, $rec, $curve]) {
            PerformanceBand::create([
                'business_id' => $this->business->id,
                'name' => $name, 'min_score' => $min, 'max_score' => $max,
                'color' => '#4361ee', 'recommendation' => $rec,
                'bell_curve_percent' => $curve, 'sort_order' => $i + 1,
            ]);
        }
    }

    private function makeKra(string $code, string $name, float $weightage = 100): Kra
    {
        return Kra::create([
            'business_id' => $this->business->id,
            'code' => $code, 'name' => $name,
            'weightage' => $weightage, 'review_frequency' => 'quarterly',
            'status' => 'active',
        ]);
    }

    private function makeKpi(Kra $kra, string $code, float $target, float $weightage = 100, string $formula = 'higher_better'): Kpi
    {
        return Kpi::create([
            'business_id' => $this->business->id,
            'kra_id' => $kra->id, 'code' => $code, 'name' => $code.' metric',
            'measurement_unit' => 'number', 'target_value' => $target,
            'weightage' => $weightage, 'score_formula' => $formula, 'status' => 'active',
        ]);
    }

    private function assign(array $kraIds, array $weightages = []): array
    {
        return app(PerformanceAssignmentService::class)
            ->assign($this->cycle, [$this->employee], $kraIds, $weightages);
    }

    private function scoring(): PerformanceScoringService
    {
        return app(PerformanceScoringService::class);
    }

    private function workflow(): PerformanceWorkflowService
    {
        return app(PerformanceWorkflowService::class);
    }

    // ═══ KPI scoring ═════════════════════════════════════════════════════

    #[Test]
    public function higher_is_better_scores_achieved_over_target(): void
    {
        $this->assertSame(80.0, EmployeeKpi::computeScore(100, 80, 'higher_better'));
        $this->assertSame(100.0, EmployeeKpi::computeScore(100, 100, 'higher_better'));
    }

    #[Test]
    public function over_achievement_is_capped_at_one_hundred(): void
    {
        // Otherwise one runaway KPI could push a whole score past the top band.
        $this->assertSame(100.0, EmployeeKpi::computeScore(100, 250, 'higher_better'));
    }

    #[Test]
    public function lower_is_better_inverts_the_ratio(): void
    {
        $this->assertSame(80.0, EmployeeKpi::computeScore(100, 125, 'lower_better'));
        // Beating the target outright is full marks, not more than full marks.
        $this->assertSame(100.0, EmployeeKpi::computeScore(100, 50, 'lower_better'));
        $this->assertSame(100.0, EmployeeKpi::computeScore(100, 0, 'lower_better'));
    }

    #[Test]
    public function exact_match_is_all_or_nothing(): void
    {
        $this->assertSame(100.0, EmployeeKpi::computeScore(30, 30, 'exact_match'));
        $this->assertSame(0.0, EmployeeKpi::computeScore(30, 29, 'exact_match'));
    }

    #[Test]
    public function an_unmeasured_kpi_scores_null_rather_than_zero(): void
    {
        // A KPI nobody has filled in yet must not read as a zero the employee
        // did not earn.
        $this->assertNull(EmployeeKpi::computeScore(100, null, 'higher_better'));
    }

    #[Test]
    public function a_kpi_with_no_target_is_met_by_any_achievement(): void
    {
        $this->assertSame(100.0, EmployeeKpi::computeScore(0, 5, 'higher_better'));
        $this->assertSame(0.0, EmployeeKpi::computeScore(0, 0, 'higher_better'));
    }

    // ═══ KRA roll-up ═════════════════════════════════════════════════════

    #[Test]
    public function kpis_roll_up_into_their_kra_weighted_by_their_own_share(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales Growth');
        $this->makeKpi($kra, 'KPI-0001', 100, 70);   // heavy
        $this->makeKpi($kra, 'KPI-0002', 100, 30);   // light
        $this->assign([$kra->id]);

        $goal = EmployeeKra::first();
        $kpis = $goal->kpis()->orderBy('id')->get();
        $kpis[0]->update(['achieved_value' => 100]);   // 100
        $kpis[1]->update(['achieved_value' => 50]);    // 50
        foreach ($goal->fresh('kpis')->kpis as $k) {
            $k->recomputeScore();
            $k->save();
        }

        // (100 × 70 + 50 × 30) / 100 = 85
        $this->assertSame(85.0, $this->scoring()->rollUpKpis($goal->fresh('kpis')->kpis, $goal));
    }

    #[Test]
    public function an_unmeasured_kpi_is_left_out_of_the_average_not_counted_as_zero(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales Growth');
        $this->makeKpi($kra, 'KPI-0001', 100, 50);
        $this->makeKpi($kra, 'KPI-0002', 100, 50);
        $this->assign([$kra->id]);

        $goal = EmployeeKra::first();
        $kpis = $goal->kpis()->orderBy('id')->get();
        $kpis[0]->update(['achieved_value' => 80, 'score' => 80]);
        // The second is left blank on purpose.

        // 80, not 40 — half the cycle measured at 80% is 80%.
        $this->assertSame(80.0, $this->scoring()->rollUpKpis($goal->fresh('kpis')->kpis, $goal));
    }

    #[Test]
    public function a_kra_with_no_kpis_falls_back_to_the_managers_rating(): void
    {
        $kra = $this->makeKra('KRA-0002', 'Team Collaboration');
        $this->assign([$kra->id]);

        $goal = EmployeeKra::first();
        $goal->update(['manager_rating' => 4]);

        // 4 out of 5 → 80 out of 100.
        $this->assertSame(80.0, $this->scoring()->rollUpKpis($goal->fresh('kpis')->kpis, $goal));
    }

    // ═══ Weighted overall score ══════════════════════════════════════════

    #[Test]
    public function kras_roll_up_weighted_into_the_overall_score(): void
    {
        // The proposal's worked example: Sales 40% + Attendance 20% +
        // Behaviour 20% + Learning 20%.
        $sales = $this->makeKra('KRA-0001', 'Sales', 40);
        $attendance = $this->makeKra('KRA-0002', 'Attendance', 20);
        $behaviour = $this->makeKra('KRA-0003', 'Behaviour', 20);
        $learning = $this->makeKra('KRA-0004', 'Learning', 20);

        $this->assign([$sales->id, $attendance->id, $behaviour->id, $learning->id]);

        // Rated 5, 4, 3, 2 → 100, 80, 60, 40 out of 100.
        foreach ([[$sales, 5], [$attendance, 4], [$behaviour, 3], [$learning, 2]] as [$kra, $rating]) {
            EmployeeKra::where('kra_id', $kra->id)->update(['manager_rating' => $rating]);
        }

        $result = $this->scoring()->computeForEmployee($this->cycle, $this->employee->id);

        // (100×40 + 80×20 + 60×20 + 40×20) / 100 = 76
        $this->assertSame(76.0, $result['final']);
        $this->assertSame(100.0, $result['total_weight']);
    }

    #[Test]
    public function a_part_configured_employee_still_produces_a_sane_score(): void
    {
        // Weightages total 50, not 100. The score must divide by the weight
        // that actually exists rather than silently halving everyone.
        $kra = $this->makeKra('KRA-0001', 'Sales', 50);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 4]);

        $result = $this->scoring()->computeForEmployee($this->cycle, $this->employee->id);

        $this->assertSame(80.0, $result['final']);
        $this->assertSame(50.0, $result['total_weight']);
    }

    #[Test]
    public function hr_moderation_overrides_the_manager_rating(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 5]);

        $this->assertSame(100.0, $this->scoring()->computeForEmployee($this->cycle, $this->employee->id)['final']);

        $this->workflow()->saveHrReview($this->cycle, $this->employee->id, [
            'moderated_rating' => 3,
            'moderation_reason' => 'Out of line with the department',
        ], $this->admin);

        $this->assertSame(60.0, $this->scoring()->computeForEmployee($this->cycle, $this->employee->id)['final']);
    }

    // ═══ Bands and rewards ═══════════════════════════════════════════════

    #[Test]
    public function a_score_maps_to_the_right_band(): void
    {
        $this->assertSame('Outstanding', PerformanceBand::forScore(96)->name);
        $this->assertSame('Excellent', PerformanceBand::forScore(90)->name);
        $this->assertSame('Good', PerformanceBand::forScore(65)->name);
        $this->assertSame('Unsatisfactory', PerformanceBand::forScore(12)->name);
    }

    #[Test]
    public function a_score_on_a_boundary_lands_in_the_better_band(): void
    {
        // 95 is Outstanding, not Excellent.
        $this->assertSame('Outstanding', PerformanceBand::forScore(95)->name);
        $this->assertSame('Excellent', PerformanceBand::forScore(85)->name);
    }

    #[Test]
    public function finalising_computes_bands_and_suggests_a_reward(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 5]);

        $score = $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        $this->assertSame('100.00', (string) $score->final_score);
        $this->assertSame('Outstanding', $score->band_name);
        $this->assertSame('promotion', $score->recommendation);
        $this->assertSame('suggested', $score->recommendation_status);
        $this->assertSame('finalized', EmployeeKra::first()->status);
    }

    #[Test]
    public function a_promotion_recommendation_does_not_survive_a_weak_score(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 2]);   // → 40, Needs Improvement

        $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, [
            'overall_rating' => 2,
            'recommend_promotion' => true,
        ], [], null, $this->admin);

        $score = $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        // The band's own suggestion wins — the score contradicts the recommendation.
        $this->assertSame('Needs Improvement', $score->band_name);
        $this->assertSame('training', $score->recommendation);
    }

    #[Test]
    public function re_finalising_keeps_a_decision_hr_has_already_made(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 5]);

        $score = $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);
        $score->update(['recommendation_status' => 'overridden', 'recommendation_override' => 'bonus']);

        $again = $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        $this->assertSame('overridden', $again->recommendation_status);
        $this->assertSame('bonus', $again->recommendation_override);
        $this->assertSame('bonus', $again->effective_recommendation);
    }

    // ═══ Bell curve ══════════════════════════════════════════════════════

    #[Test]
    public function the_bell_curve_fits_scores_to_the_configured_shares(): void
    {
        HrSettings::setForBusiness('performance_bell_curve_enabled', $this->business->id, 1, 'hr');
        $this->assertTrue($this->scoring()->bellCurveEnabled($this->business->id));

        // Twenty employees, all finalised, evenly spread.
        for ($i = 1; $i <= 20; $i++) {
            $e = Employee::create([
                'business_id' => $this->business->id, 'employee_code' => 'EMP-C'.$i,
                'email' => "curve{$i}@perf.test", 'first_name' => 'Curve', 'last_name' => (string) $i,
                'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
            ]);
            PerformanceScore::create([
                'business_id' => $this->business->id,
                'performance_cycle_id' => $this->cycle->id,
                'employee_id' => $e->id,
                'final_score' => 100 - ($i * 2),
            ]);
        }

        $result = $this->scoring()->applyBellCurve($this->cycle);

        $this->assertSame(20, $result['ranked']);
        // 10 / 20 / 50 / 15 / 5 of twenty → 2 / 4 / 10 / 3 / 1.
        $this->assertSame(2, $result['distribution']['Outstanding']);
        $this->assertSame(4, $result['distribution']['Excellent']);
        $this->assertSame(10, $result['distribution']['Good']);
        $this->assertSame(1, $result['distribution']['Unsatisfactory']);

        $top = PerformanceScore::orderByDesc('final_score')->first();
        $this->assertSame('Outstanding', $top->bell_curve_band);
        $this->assertSame(1, $top->rank_in_business);
    }

    #[Test]
    public function the_curve_never_leaves_anybody_unbanded(): void
    {
        HrSettings::setForBusiness('performance_bell_curve_enabled', $this->business->id, 1, 'hr');

        // Seven does not divide cleanly into 10/20/50/15/5.
        for ($i = 1; $i <= 7; $i++) {
            $e = Employee::create([
                'business_id' => $this->business->id, 'employee_code' => 'EMP-O'.$i,
                'email' => "odd{$i}@perf.test", 'first_name' => 'Odd', 'last_name' => (string) $i,
                'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
            ]);
            PerformanceScore::create([
                'business_id' => $this->business->id,
                'performance_cycle_id' => $this->cycle->id,
                'employee_id' => $e->id,
                'final_score' => 90 - $i,
            ]);
        }

        $result = $this->scoring()->applyBellCurve($this->cycle);

        $this->assertSame(7, $result['ranked']);
        $this->assertSame(0, PerformanceScore::whereNull('bell_curve_band')->count());
    }

    #[Test]
    public function the_curve_is_stored_beside_the_earned_band_not_over_it(): void
    {
        HrSettings::setForBusiness('performance_bell_curve_enabled', $this->business->id, 1, 'hr');

        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 5]);
        $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        $this->scoring()->applyBellCurve($this->cycle);

        $score = PerformanceScore::first();
        $this->assertSame('Outstanding', $score->band_name, 'The earned band must survive the curve.');
        $this->assertNotNull($score->bell_curve_band);

        $this->scoring()->clearBellCurve($this->cycle);
        $this->assertNull($score->fresh()->bell_curve_band);
        $this->assertSame('Outstanding', $score->fresh()->band_name);
    }

    // ═══ Assignment ══════════════════════════════════════════════════════

    #[Test]
    public function assigning_copies_the_kpis_from_the_master(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 40);
        $this->makeKpi($kra, 'KPI-0001', 500000, 60);
        $this->makeKpi($kra, 'KPI-0002', 12, 40);

        $result = $this->assign([$kra->id]);

        $this->assertSame(1, $result['assigned']);
        $goal = EmployeeKra::first();
        $this->assertSame(40.0, (float) $goal->weightage);
        $this->assertCount(2, $goal->kpis);
        // Targets are copied, so editing the template later cannot rewrite a
        // cycle already under review.
        $this->assertSame('500000.00', (string) $goal->kpis->first()->target_value);
        // The manager defaults to the employee's own reporting manager.
        $this->assertSame($this->manager->id, $goal->manager_id);
    }

    #[Test]
    public function assigning_the_same_goal_twice_updates_rather_than_duplicating(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 40);
        $this->assign([$kra->id]);
        $result = $this->assign([$kra->id], [$kra->id => 60]);

        $this->assertSame(1, EmployeeKra::count());
        $this->assertSame(1, $result['updated']);
        $this->assertSame(60.0, (float) EmployeeKra::first()->weightage);
    }

    #[Test]
    public function a_goal_already_under_review_keeps_its_weightage(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 40);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['status' => 'manager_reviewed']);

        $this->assign([$kra->id], [$kra->id => 90]);

        // Moving the target after the manager has scored would change the
        // meaning of a review already done.
        $this->assertSame(40.0, (float) EmployeeKra::first()->weightage);
    }

    #[Test]
    public function copy_forward_brings_goals_and_weightages_into_the_next_cycle(): void
    {
        $sales = $this->makeKra('KRA-0001', 'Sales', 40);
        $behaviour = $this->makeKra('KRA-0002', 'Behaviour', 60);
        $this->assign([$sales->id, $behaviour->id]);
        EmployeeKra::where('kra_id', $sales->id)->update(['weightage' => 70, 'manager_rating' => 5]);
        EmployeeKra::where('kra_id', $behaviour->id)->update(['weightage' => 30]);

        $next = $this->makeCycle('draft', 'Q4 2026');
        $result = app(PerformanceAssignmentService::class)->copyForward($this->cycle, $next);

        $this->assertSame(2, $result['assigned']);
        $copied = EmployeeKra::where('performance_cycle_id', $next->id)->get();
        $this->assertSame(70.0, (float) $copied->firstWhere('kra_id', $sales->id)->weightage);
        // Scores do not travel — a new cycle starts clean.
        $this->assertNull($copied->firstWhere('kra_id', $sales->id)->manager_rating);
        $this->assertSame('assigned', $copied->first()->status);
    }

    #[Test]
    public function cascade_gives_each_person_only_the_kras_that_match_them(): void
    {
        $designation = Designation::create([
            'business_id' => $this->business->id, 'name' => 'Executive', 'code' => 'EXE',
            'department_id' => $this->department->id,
        ]);
        $this->employee->update(['designation_id' => $designation->id]);

        $everyone = $this->makeKra('KRA-0001', 'Attendance', 20);
        $salesOnly = $this->makeKra('KRA-0002', 'Sales', 50);
        $salesOnly->update(['department_id' => $this->department->id]);
        $otherDept = $this->makeKra('KRA-0003', 'Support', 30);
        $otherDept->update(['department_id' => Department::create([
            'business_id' => $this->business->id, 'name' => 'Support', 'code' => 'SUP',
        ])->id]);

        app(PerformanceAssignmentService::class)->cascade($this->cycle, $this->department->id);

        $mine = EmployeeKra::where('employee_id', $this->employee->id)->pluck('kra_id');
        $this->assertTrue($mine->contains($everyone->id), 'An unrestricted KRA applies to everyone.');
        $this->assertTrue($mine->contains($salesOnly->id));
        $this->assertFalse($mine->contains($otherDept->id), 'Another department\'s KRA must not be assigned.');
    }

    #[Test]
    public function the_weightage_validator_flags_anyone_who_does_not_total_one_hundred(): void
    {
        $a = $this->makeKra('KRA-0001', 'Sales', 40);
        $b = $this->makeKra('KRA-0002', 'Behaviour', 30);
        $this->assign([$a->id, $b->id]);

        $service = app(PerformanceAssignmentService::class);
        $this->assertSame(70.0, $service->weightageTotals($this->cycle)[$this->employee->id]);
        $this->assertCount(1, $service->employeesOutOfBalance($this->cycle));

        EmployeeKra::where('kra_id', $b->id)->update(['weightage' => 60]);
        $this->assertCount(0, $service->employeesOutOfBalance($this->cycle));
    }

    #[Test]
    public function a_reviewed_goal_cannot_be_unassigned(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        $goal = EmployeeKra::first();

        $this->assertTrue(app(PerformanceAssignmentService::class)->unassign($goal));

        $this->assign([$kra->id]);
        $goal = EmployeeKra::first();
        $goal->update(['status' => 'manager_reviewed']);

        $this->assertFalse(app(PerformanceAssignmentService::class)->unassign($goal));
        $this->assertSame(1, EmployeeKra::count());
    }

    // ═══ Workflow ════════════════════════════════════════════════════════

    #[Test]
    public function the_escalation_runs_employee_then_manager_then_hr(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);

        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Closed 12 deals']);
        $this->assertSame('self_submitted', EmployeeKra::first()->status);

        $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, [
            'overall_rating' => 4, 'feedback' => 'Strong quarter',
        ], [], $this->manager);
        $this->assertSame('manager_reviewed', EmployeeKra::first()->status);

        $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);
        $this->assertSame('finalized', EmployeeKra::first()->status);

        // Each stage is written to the trail.
        $this->assertEqualsCanonicalizing(
            ['submitted', 'reviewed', 'finalized'],
            PerformanceHistory::pluck('action')->all(),
        );
    }

    #[Test]
    public function sending_back_to_the_employee_keeps_the_manager_review(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);

        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Did the work']);
        $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, [
            'overall_rating' => 3, 'feedback' => 'Fine',
        ], [], $this->manager);

        $this->workflow()->sendBack($this->cycle, $this->employee->id, 'self', 'Add the numbers please', $this->admin);

        $this->assertSame('sent_back', PerformanceSelfReview::first()->status);
        $this->assertNull(PerformanceSelfReview::first()->submitted_at);
        $this->assertSame('sent_back', EmployeeKra::first()->status);
        // The manager's own review is untouched — only the employee's stage reopened.
        $this->assertSame('submitted', PerformanceManagerReview::first()->status);
    }

    #[Test]
    public function sending_back_to_the_manager_keeps_the_self_assessment(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);

        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Did the work']);
        $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, [
            'overall_rating' => 5, 'feedback' => 'Too generous',
        ], [], $this->manager);

        $this->workflow()->sendBack($this->cycle, $this->employee->id, 'manager', 'Rating is out of line', $this->admin);

        $this->assertSame('sent_back', PerformanceManagerReview::first()->status);
        $this->assertSame('submitted', PerformanceSelfReview::first()->status, 'The self-assessment must survive.');
        $this->assertSame('self_submitted', EmployeeKra::first()->status);
    }

    #[Test]
    public function a_locked_cycle_refuses_every_change(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        $this->cycle->update(['status' => 'locked']);

        foreach ([
            fn () => $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'x']),
            fn () => $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, ['overall_rating' => 4], [], $this->manager),
            fn () => $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin),
            fn () => $this->workflow()->sendBack($this->cycle, $this->employee->id, 'self', 'nope', $this->admin),
        ] as $action) {
            try {
                $action();
                $this->fail('Expected a locked cycle to refuse the change.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('Locked', $e->getMessage());
            }
        }
    }

    #[Test]
    public function the_pending_queue_shows_what_is_at_each_desk(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);

        $this->assertSame(1, $this->workflow()->pendingQueue($this->cycle, 'self')->count());
        $this->assertSame(0, $this->workflow()->pendingQueue($this->cycle, 'manager')->count());

        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Done']);

        $this->assertSame(0, $this->workflow()->pendingQueue($this->cycle, 'self')->count());
        $this->assertSame(1, $this->workflow()->pendingQueue($this->cycle, 'manager')->count());
        $this->assertSame(1, $this->workflow()->pendingQueue($this->cycle, 'manager', $this->manager->id)->count());
    }

    #[Test]
    public function the_discipline_snapshot_reads_the_cycle_period(): void
    {
        Warning::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'warning_code' => 'WRN-1', 'title' => 'Late arrival', 'level' => 1, 'reason' => 'Late',
            'issued_on' => '2026-08-15', 'status' => 'active',
        ]);
        // Outside the cycle — must not be counted.
        Warning::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'warning_code' => 'WRN-2', 'title' => 'Late arrival', 'level' => 1, 'reason' => 'Late',
            'issued_on' => '2026-01-15', 'status' => 'active',
        ]);

        $snapshot = $this->workflow()->disciplineSnapshot($this->cycle, $this->employee->id);

        $this->assertSame(1, $snapshot['warning_count']);
        $this->assertSame(0, $snapshot['penalty_count']);
    }

    // ═══ Cycles ══════════════════════════════════════════════════════════

    #[Test]
    public function a_cycle_suggests_the_next_period_when_rolled_forward(): void
    {
        $next = $this->cycle->nextPeriod();

        $this->assertSame('2026-10-01', $next['period_start']);
        $this->assertSame('2026-12-31', $next['period_end']);
        $this->assertSame('Q4 2026', $next['name']);
    }

    #[Test]
    public function cycle_names_are_suggested_per_frequency(): void
    {
        $march = Carbon::parse('2026-03-01');
        $this->assertSame('Mar 2026', PerformanceCycle::suggestName('monthly', $march));
        $this->assertSame('Q1 2026', PerformanceCycle::suggestName('quarterly', $march));
        $this->assertSame('H1 2026', PerformanceCycle::suggestName('half_yearly', $march));
        $this->assertSame('FY 2026', PerformanceCycle::suggestName('yearly', $march));
    }

    // ═══ Screens ═════════════════════════════════════════════════════════

    #[Test]
    public function every_admin_screen_renders(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->makeKpi($kra, 'KPI-0001', 100, 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 4]);
        $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        $routes = [
            ['admin.hr.performance.dashboard', []],
            ['admin.hr.performance.cycles.index', []],
            ['admin.hr.performance.cycles.create', []],
            ['admin.hr.performance.cycles.show', $this->cycle],
            ['admin.hr.performance.cycles.edit', $this->cycle],
            ['admin.hr.performance.kras.index', []],
            ['admin.hr.performance.kras.create', []],
            ['admin.hr.performance.kras.show', $kra],
            ['admin.hr.performance.kpis.index', []],
            ['admin.hr.performance.kpis.create', []],
            ['admin.hr.performance.goals.index', []],
            ['admin.hr.performance.goals.create', []],
            ['admin.hr.performance.goals.weightages', []],
            ['admin.hr.performance.reviews.index', []],
            ['admin.hr.performance.bands.index', []],
            ['admin.hr.performance.rewards.index', []],
            ['admin.hr.performance.reports.index', []],
        ];

        foreach ($routes as [$name, $params]) {
            $this->actingAs($this->admin, 'admin')->get(route($name, $params))
                ->assertStatus(200, "Route {$name} did not render.");
        }

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.performance.reviews.show', ['cycle' => $this->cycle->id, 'employee' => $this->employee->id]))
            ->assertStatus(200)
            ->assertSee('Asha Verma');
    }

    #[Test]
    public function all_thirteen_reports_render_and_export(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->makeKpi($kra, 'KPI-0001', 100, 100);
        $this->assign([$kra->id]);
        EmployeeKra::first()->update(['manager_rating' => 4]);
        $this->workflow()->submitManagerReview($this->cycle, $this->employee->id, [
            'overall_rating' => 4, 'recommend_promotion' => true,
        ], [], $this->manager);
        $this->workflow()->finalize($this->cycle, $this->employee->id, $this->admin);

        $reports = array_keys(ReportController::REPORTS);
        $this->assertCount(13, $reports);

        foreach ($reports as $report) {
            $this->actingAs($this->admin, 'admin')
                ->get(route('admin.hr.performance.reports.show', ['report' => $report, 'cycle' => $this->cycle->id]))
                ->assertStatus(200, "Report {$report} did not render.");

            foreach (['excel', 'pdf'] as $format) {
                $this->actingAs($this->admin, 'admin')
                    ->get(route('admin.hr.performance.reports.show', ['report' => $report, 'cycle' => $this->cycle->id, 'format' => $format]))
                    ->assertStatus(200, "Report {$report} failed to export as {$format}.");
            }
        }
    }

    #[Test]
    public function the_employee_portal_runs_the_self_assessment_end_to_end(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        $goal = EmployeeKra::first();

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.performance-goals.index'))
            ->assertStatus(200)
            ->assertSee('Sales');

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.performance-goals.self-assessment', $this->cycle))
            ->assertStatus(200);

        // Draft first — stays private, does not move the goal on.
        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.performance-goals.self-assessment.store', $this->cycle), [
                'action' => 'draft',
                'achievements' => 'Half written',
                'kra' => [$goal->id => ['rating' => 4, 'remarks' => 'Went well']],
            ])->assertRedirect();

        $this->assertSame('draft', PerformanceSelfReview::first()->status);
        $this->assertSame('assigned', EmployeeKra::first()->status);
        $this->assertSame('4.0', (string) EmployeeKra::first()->self_rating);

        // Then submit.
        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.performance-goals.self-assessment.store', $this->cycle), [
                'action' => 'submit',
                'achievements' => 'Closed 12 deals against a target of 10',
                'kra' => [$goal->id => ['rating' => 4]],
            ])->assertRedirect(route('employee.performance-goals.index', ['cycle' => $this->cycle->id]));

        $this->assertSame('submitted', PerformanceSelfReview::first()->status);
        $this->assertSame('self_submitted', EmployeeKra::first()->status);
    }

    #[Test]
    public function submitting_an_empty_self_assessment_is_refused(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.performance-goals.self-assessment.store', $this->cycle), [
                'action' => 'submit',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, PerformanceSelfReview::where('status', 'submitted')->count());
    }

    #[Test]
    public function evidence_can_be_attached_and_is_locked_once_submitted(): void
    {
        Storage::fake('public');

        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        $goal = EmployeeKra::first();

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.performance-goals.evidence.store', $goal), [
                'file' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])->assertRedirect();

        $document = PerformanceDocument::first();
        $this->assertNotNull($document);
        Storage::disk('public')->assertExists($document->file_path);

        // Once the assessment is with the manager, the evidence is part of what
        // they are reviewing and can no longer be pulled.
        $goal->update(['status' => 'self_submitted']);

        $this->actingAs($this->employee, 'employee')
            ->delete(route('employee.performance-goals.evidence.destroy', $document))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, PerformanceDocument::count());
    }

    #[Test]
    public function a_manager_reviews_only_their_own_team(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $this->assign([$kra->id]);
        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Done']);

        $this->actingAs($this->manager, 'employee')
            ->get(route('employee.team-performance.index'))
            ->assertStatus(200)
            ->assertSee('Asha Verma');

        $this->actingAs($this->manager, 'employee')
            ->get(route('employee.team-performance.show', ['cycle' => $this->cycle->id, 'employee' => $this->employee->id]))
            ->assertStatus(200);

        // Somebody else's manager cannot open it.
        $outsider = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-OUT',
            'email' => 'out@perf.test', 'first_name' => 'Other', 'last_name' => 'Manager',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);

        $this->actingAs($outsider, 'employee')
            ->get(route('employee.team-performance.show', ['cycle' => $this->cycle->id, 'employee' => $this->employee->id]))
            ->assertStatus(403);
    }

    #[Test]
    public function a_manager_submits_achieved_values_and_the_score_follows(): void
    {
        $kra = $this->makeKra('KRA-0001', 'Sales', 100);
        $kpi = $this->makeKpi($kra, 'KPI-0001', 100, 100);
        $this->assign([$kra->id]);
        $goal = EmployeeKra::first();
        $employeeKpi = $goal->kpis->first();

        $this->workflow()->submitSelfReview($this->cycle, $this->employee, ['achievements' => 'Done']);

        $this->actingAs($this->manager, 'employee')
            ->post(route('employee.team-performance.store', ['cycle' => $this->cycle->id, 'employee' => $this->employee->id]), [
                'overall_rating' => 4,
                'feedback' => 'Solid delivery this quarter',
                'kpi' => [$employeeKpi->id => 90],
                'kra' => [$goal->id => ['rating' => 4]],
            ])->assertRedirect();

        $this->assertSame('90.00', (string) $employeeKpi->fresh()->score);
        $this->assertSame('manager_reviewed', EmployeeKra::first()->status);

        // KPI at 90 and a manager rating of 4 (=80) average to 85.
        $this->assertSame(85.0, $this->scoring()->computeForEmployee($this->cycle, $this->employee->id)['final']);
    }

    #[Test]
    public function an_admin_without_performance_permissions_is_refused(): void
    {
        $outsider = Admin::create([
            'name' => 'Sales Person', 'email' => 'sales@perf.test',
            'password' => bcrypt('password'), 'phone' => '9990009999',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $outsider->assignRole('Sales');

        foreach ([
            'admin.hr.performance.dashboard',
            'admin.hr.performance.cycles.index',
            'admin.hr.performance.kras.index',
            'admin.hr.performance.goals.index',
            'admin.hr.performance.reviews.index',
            'admin.hr.performance.reports.index',
        ] as $route) {
            $this->actingAs($outsider, 'admin')->get(route($route))->assertStatus(403);
        }
    }

    #[Test]
    public function everything_is_scoped_to_the_active_business(): void
    {
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-perf-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);

        Kra::create([
            'business_id' => $other->id, 'code' => 'KRA-9999', 'name' => 'Theirs',
            'weightage' => 100, 'review_frequency' => 'quarterly', 'status' => 'active',
        ]);
        $this->makeKra('KRA-0001', 'Mine', 100);

        $this->assertSame(1, Kra::count());
        $this->assertSame('Mine', Kra::first()->name);
    }
}
