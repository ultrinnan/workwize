<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_assets_with_their_employee(): void
    {
        Asset::factory()->count(3)->create();

        $response = $this->getJson('/api/assets');

        $response->assertOk()->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                ['id', 'serial_code', 'device_name', 'provider', 'attributes', 'employee' => ['id', 'email']],
            ],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
    }

    public function test_it_filters_assets_by_search_term(): void
    {
        Asset::factory()->create(['device_name' => 'Finance Team MacBook', 'serial_code' => 'FINANCE1']);
        Asset::factory()->create(['device_name' => 'QA Mac Mini', 'serial_code' => 'QAMINI1']);

        $response = $this->getJson('/api/assets?search=Finance');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Finance Team MacBook', $response->json('data.0.device_name'));
    }

    public function test_it_shows_asset_attributes_and_employee(): void
    {
        $asset = Asset::factory()->create([
            'attributes' => ['model' => 'MacBook Air', 'ram_gb' => 8.0, 'storage_gb' => 256.0],
        ]);

        $response = $this->getJson("/api/assets/{$asset->id}");

        $response->assertOk()
            ->assertJsonPath('data.attributes.model', 'MacBook Air')
            ->assertJsonPath('data.employee.id', $asset->employee_id);
    }

    public function test_it_deletes_an_asset_from_the_local_database(): void
    {
        $asset = Asset::factory()->create();

        $this->deleteJson("/api/assets/{$asset->id}")->assertNoContent();

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('employees', ['id' => $asset->employee_id]);
    }
}
