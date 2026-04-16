# Testing

## Overview

ALTechnics ERP uses PHPUnit 12 for testing. Tests are organized into Feature and Unit test directories following Laravel conventions.

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test File

```bash
php artisan test --filter=CustomerTest
```

### Run with Coverage

```bash
php artisan test --coverage
```

Requires Xdebug or PCOV to be installed.

## Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── AdminLoginTest.php
│   ├── Customer/
│   │   └── CustomerCrudTest.php
│   ├── Lead/
│   │   └── LeadManagementTest.php
│   └── ...
├── Unit/
│   ├── Models/
│   │   └── ProductStockTest.php
│   └── Services/
│       └── InvoiceServiceTest.php
└── TestCase.php
```

## Test Database

Tests use SQLite in-memory by default for speed. This is configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Writing a New Feature Test

```php
<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_can_view_list(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.module.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.module.index');
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $viewer = Admin::factory()->create();
        $viewer->assignRole('Viewer');

        $response = $this->actingAs($viewer, 'admin')
            ->post(route('admin.module.store'), [/* data */]);

        $response->assertForbidden();
    }
}
```

## Key Testing Patterns

- **RefreshDatabase** — Use this trait to reset the DB between tests
- **Seed permissions first** — Always seed `RolePermissionSeeder` in setUp
- **actingAs with guard** — Always specify the `admin` guard: `actingAs($user, 'admin')`
- **Test both positive and negative** — Test that authorized users can access and unauthorized users are blocked

## Adding Tests for New Modules

1. Create a test class in the appropriate `tests/Feature/` subdirectory
2. Extend `Tests\TestCase`
3. Use `RefreshDatabase` trait
4. Seed roles in `setUp()`
5. Test CRUD operations, status transitions, and permission checks
6. Run the full suite to ensure no regressions
