<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class ReportTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->superAdmin = $this->createSuperAdmin();
    }

    #[Test]
    public function test_sales_report_accessible(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.reports.sales'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_inventory_report_accessible(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.reports.inventory'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_report_filters_work(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.reports.sales', [
                'date_from' => now()->subMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
    }
}
