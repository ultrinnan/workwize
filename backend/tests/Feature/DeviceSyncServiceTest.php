<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\MdmDevice;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\SyncRun;
use App\Services\Mdm\DeviceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeMdmProvider;
use Tests\TestCase;

class DeviceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeviceSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DeviceSyncService::class);
    }

    public function test_it_imports_assigned_devices_and_creates_employees(): void
    {
        $run = $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
            $this->device(['serial' => 'SER-2', 'email' => 'bob@company.test']),
        ]);

        $this->assertSame('success', $run->status);
        $this->assertSame(2, $run->created_assets);
        $this->assertSame(0, $run->updated_assets);
        $this->assertSame(2, Asset::query()->count());
        $this->assertSame(2, Employee::query()->count());
    }

    public function test_it_skips_devices_without_an_email(): void
    {
        $run = $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
            $this->device(['serial' => 'SER-2', 'email' => null]),
        ]);

        $this->assertSame(1, $run->created_assets);
        $this->assertSame(1, $run->skipped_unassigned);
        $this->assertSame(1, Asset::query()->count());
    }

    public function test_it_skips_devices_without_a_serial(): void
    {
        $run = $this->sync([
            $this->device(['serial' => null, 'email' => 'alice@company.test']),
        ]);

        $this->assertSame(1, $run->skipped_missing_serial);
        $this->assertSame(0, Asset::query()->count());
        $this->assertSame(0, Employee::query()->count());
    }

    public function test_rerunning_sync_is_idempotent(): void
    {
        $devices = [
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ];

        $this->sync($devices);
        $second = $this->sync($devices);

        $this->assertSame(0, $second->created_assets);
        $this->assertSame(0, $second->updated_assets);
        $this->assertSame(1, Asset::query()->count());
        $this->assertSame(1, Employee::query()->count());
    }

    public function test_it_recreates_a_deleted_asset_on_resync(): void
    {
        $devices = [
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ];

        $this->sync($devices);
        Asset::query()->firstOrFail()->delete();
        $this->assertSame(0, Asset::query()->count());

        $run = $this->sync($devices);

        $this->assertSame(1, $run->created_assets);
        $this->assertSame(1, Asset::query()->count());
    }

    public function test_it_recreates_a_deleted_employee_on_resync(): void
    {
        $devices = [
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ];

        $this->sync($devices);

        Asset::query()->delete();
        Employee::query()->delete();

        $run = $this->sync($devices);

        $this->assertSame(1, $run->created_assets);
        $this->assertSame(1, Employee::query()->count());
        $this->assertSame(1, Asset::query()->count());
    }

    public function test_it_reflects_attribute_changes(): void
    {
        $this->sync([
            $this->device([
                'serial' => 'SER-1',
                'email' => 'alice@company.test',
                'attributes' => ['model' => 'MacBook Pro', 'ram_gb' => 8.0],
            ]),
        ]);

        $this->assertEquals(8.0, Asset::query()->firstOrFail()->attributes['ram_gb']);

        $run = $this->sync([
            $this->device([
                'serial' => 'SER-1',
                'email' => 'alice@company.test',
                'attributes' => ['model' => 'MacBook Pro', 'ram_gb' => 16.0],
            ]),
        ]);

        $this->assertSame(1, $run->updated_assets);
        $this->assertEquals(16.0, Asset::query()->firstOrFail()->attributes['ram_gb']);
    }

    public function test_it_reflects_employee_reassignment(): void
    {
        $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ]);

        $run = $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'bob@company.test']),
        ]);

        $this->assertSame(1, $run->updated_assets);

        $asset = Asset::query()->firstOrFail();

        $this->assertSame('bob@company.test', $asset->employee->email);
        $this->assertSame(2, Employee::query()->count());
    }

    public function test_it_removes_an_asset_when_the_device_becomes_unassigned(): void
    {
        $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ]);

        $run = $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => null]),
        ]);

        $this->assertSame(1, $run->unassigned_assets);
        $this->assertSame(0, Asset::query()->count());
    }

    public function test_it_flags_assets_missing_from_the_payload(): void
    {
        $this->sync([
            $this->device(['serial' => 'SER-1', 'email' => 'alice@company.test']),
        ]);

        $run = $this->sync([]);

        $this->assertSame(1, $run->missing_assets);

        $asset = Asset::query()->firstOrFail();

        $this->assertNotNull($asset->missing_at);
    }

    public function test_it_does_not_wipe_employee_fields_with_null_values(): void
    {
        $this->sync([
            $this->device([
                'serial' => 'SER-1',
                'email' => 'alice@company.test',
                'name' => 'Alice Doe',
                'phone' => '+491234',
                'position' => 'Engineer',
            ]),
        ]);

        $this->sync([
            $this->device([
                'serial' => 'SER-1',
                'email' => 'alice@company.test',
                'name' => null,
                'phone' => null,
                'position' => null,
            ]),
        ]);

        $employee = Employee::query()->firstOrFail();

        $this->assertSame('Alice Doe', $employee->name);
        $this->assertSame('+491234', $employee->phone);
        $this->assertSame('Engineer', $employee->position);
    }

    /**
     * @param  list<MdmDevice>  $devices
     */
    private function sync(array $devices): SyncRun
    {
        return $this->service->sync(new FakeMdmProvider($devices));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function device(array $overrides = []): MdmDevice
    {
        $value = static fn (string $key, mixed $default): mixed => array_key_exists($key, $overrides)
            ? $overrides[$key]
            : $default;

        return new MdmDevice(
            serial: $value('serial', 'SER-1'),
            email: $value('email', 'alice@company.test'),
            name: $value('name', 'Alice Doe'),
            phone: $value('phone', '+491234567890'),
            position: $value('position', 'Engineer'),
            deviceName: $value('deviceName', 'MacBook Pro'),
            externalId: $value('externalId', 'udid-1'),
            attributes: $value('attributes', ['model' => 'MacBook Pro', 'ram_gb' => 8.0]),
            lastSeenAt: $value('lastSeenAt', null),
        );
    }
}
