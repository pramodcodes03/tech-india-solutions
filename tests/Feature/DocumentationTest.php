<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_documentation_is_publicly_accessible(): void
    {
        $response = $this->get(route('documentation'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_documentation_introduction_page_loads(): void
    {
        $response = $this->get(route('documentation', ['page' => 'introduction']));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_documentation_404_for_invalid_page(): void
    {
        $response = $this->get(route('documentation', ['page' => 'non-existent-page-xyz']));

        $response->assertStatus(404);
    }
}
