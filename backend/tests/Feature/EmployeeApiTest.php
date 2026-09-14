<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_employees_with_asset_counts(): void
    {
        $employee = Employee::factory()->create();
        Asset::factory()->count(2)->create(['employee_id' => $employee->id]);

        $response = $this->getJson('/api/employees');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                ['id', 'email', 'name', 'phone', 'position', 'assets_count'],
            ],
        ]);

        $this->assertSame(2, $response->json('data.0.assets_count'));
    }

    public function test_it_refuses_to_delete_an_employee_with_assets(): void
    {
        $employee = Employee::factory()->create();
        Asset::factory()->create(['employee_id' => $employee->id]);

        $this->deleteJson("/api/employees/{$employee->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_it_deletes_an_employee_without_assets(): void
    {
        $employee = Employee::factory()->create();

        $this->deleteJson("/api/employees/{$employee->id}")->assertNoContent();

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_it_shows_an_employee_with_assets(): void
    {
        $employee = Employee::factory()->create();
        Asset::factory()->create(['employee_id' => $employee->id]);

        $this->getJson("/api/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonCount(1, 'data.assets');
    }
}
