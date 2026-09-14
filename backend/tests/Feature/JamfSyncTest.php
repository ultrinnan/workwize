<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JamfSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_assigned_devices_from_the_jamf_mock_file(): void
    {
        $response = $this->postJson('/api/sync');

        $response
            ->assertCreated()
            ->assertJsonPath('data.provider', 'jamf')
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.created_assets', 7)
            ->assertJsonPath('data.skipped_unassigned', 0);

        $this->assertSame(7, Asset::query()->count());
        $this->assertSame(6, Employee::query()->count());

        $alex = Employee::query()->where('email', 'alex.smith@company.test')->firstOrFail();

        $this->assertSame(2, $alex->assets()->count());
        $this->assertSame('Backend Engineer', $alex->position);
    }

    public function test_a_second_sync_does_not_create_or_update_anything(): void
    {
        $this->postJson('/api/sync')->assertCreated();

        $this->postJson('/api/sync')
            ->assertCreated()
            ->assertJsonPath('data.created_assets', 0)
            ->assertJsonPath('data.updated_assets', 0)
            ->assertJsonPath('data.restored_assets', 0);

        $this->assertSame(7, Asset::query()->count());
    }

    public function test_synced_devices_are_visible_through_the_assets_api(): void
    {
        $this->postJson('/api/sync')->assertCreated();

        $response = $this->getJson('/api/assets');

        $response->assertOk()->assertJsonCount(7, 'data');

        $this->assertNotNull($response->json('data.0.employee'));
        $this->assertArrayHasKey('model', $response->json('data.0.attributes'));
    }
}
