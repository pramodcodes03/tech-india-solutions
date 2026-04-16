<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class SettingTest extends TestCase
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
    public function test_admin_can_view_settings(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.settings.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'company_name' => 'Leather Technics',
                    'company_email' => 'info@leathertechnics.com',
                    'company_phone' => '044-12345678',
                    'company_gst' => '33AABCT1234F1ZR',
                    'currency_symbol' => 'INR',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'company_name',
            'value' => 'Leather Technics',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'company_email',
            'value' => 'info@leathertechnics.com',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'currency_symbol',
            'value' => 'INR',
        ]);
    }
}
