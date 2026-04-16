<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class AdminUserTest extends TestCase
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
    public function test_super_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_super_admin_can_create_admin_user(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.admin-users.store'), [
                'name' => 'New Admin',
                'email' => 'newadmin@test.com',
                'phone' => '9876500001',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Sales',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.admin-users.index'));
        $this->assertDatabaseHas('admins', [
            'email' => 'newadmin@test.com',
            'name' => 'New Admin',
        ]);
    }

    #[Test]
    public function test_super_admin_can_update_admin_user(): void
    {
        $admin = $this->createAdminWithRole('Sales', 'sales-update@test.com');

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->put(route('admin.admin-users.update', $admin->id), [
                'name' => 'Updated Name',
                'email' => 'sales-update@test.com',
                'phone' => '9876500002',
                'role' => 'Sales',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.admin-users.index'));
        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function test_super_admin_can_delete_admin_user(): void
    {
        $admin = $this->createAdminWithRole('Sales', 'sales-delete@test.com');

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->delete(route('admin.admin-users.destroy', $admin->id));

        $response->assertRedirect(route('admin.admin-users.index'));
        $this->assertSoftDeleted('admins', ['id' => $admin->id]);
    }

    #[Test]
    public function test_cannot_delete_last_super_admin(): void
    {
        // superAdmin is the only Super Admin
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->delete(route('admin.admin-users.destroy', $this->superAdmin->id));

        // Controller checks self-deletion first (403), then last super admin
        $response->assertRedirect();
        $this->assertDatabaseHas('admins', [
            'id' => $this->superAdmin->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function test_viewer_cannot_create_admin_user(): void
    {
        $viewer = $this->createAdminWithRole('Viewer', 'viewer@test.com');

        $response = $this->actingAs($viewer, 'admin')
            ->post(route('admin.admin-users.store'), [
                'name' => 'Should Fail',
                'email' => 'shouldfail@test.com',
                'phone' => '9876500003',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Sales',
                'status' => 'active',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('admins', [
            'email' => 'shouldfail@test.com',
        ]);
    }

    #[Test]
    public function test_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.admin-users.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'role' => '',
            ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }
}
