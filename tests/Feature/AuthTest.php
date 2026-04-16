<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class AuthTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    #[Test]
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->post(route('admin.signin'), [
            'email' => 'superadmin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    #[Test]
    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        $this->createSuperAdmin();

        $response = $this->post(route('admin.signin'), [
            'email' => 'superadmin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest('admin');
    }

    #[Test]
    public function test_admin_can_logout(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    #[Test]
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }
}
