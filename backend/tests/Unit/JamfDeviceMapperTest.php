<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Mdm\Jamf\JamfDeviceMapper;
use PHPUnit\Framework\TestCase;

class JamfDeviceMapperTest extends TestCase
{
    private JamfDeviceMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new JamfDeviceMapper;
    }

    public function test_it_maps_a_full_jamf_payload(): void
    {
        $device = $this->mapper->map([
            'id' => '101',
            'udid' => 'UDID-1',
            'general' => ['name' => 'Alex MacBook', 'platform' => 'Mac', 'lastContactTime' => '2026-01-05T10:30:12Z'],
            'userAndLocation' => [
                'username' => 'alex.smith',
                'realname' => 'Alex Smith',
                'email' => 'Alex.Smith@Company.Test',
                'position' => 'Backend Engineer',
                'phone' => '+491234567890',
            ],
            'hardware' => [
                'make' => 'Apple',
                'model' => 'MacBook Pro',
                'modelIdentifier' => 'MacBookPro18,3',
                'serialNumber' => 'C02DL0XYZQ6N',
                'processorType' => 'Apple M1 Pro',
                'coreCount' => 10,
                'totalRamMegabytes' => 16384,
                'batteryCapacityPercent' => 91,
            ],
        ]);

        $this->assertSame('C02DL0XYZQ6N', $device->serial);
        $this->assertSame('alex.smith@company.test', $device->email);
        $this->assertSame('Alex Smith', $device->name);
        $this->assertSame('Backend Engineer', $device->position);
        $this->assertSame('Alex MacBook', $device->deviceName);
        $this->assertSame('UDID-1', $device->externalId);
        $this->assertSame(16.0, $device->attributes['ram_gb']);
        $this->assertSame('Apple', $device->attributes['make']);
        $this->assertSame(10.0, $device->attributes['core_count']);
        $this->assertSame('2026-01-05 10:30:12', $device->lastSeenAt?->format('Y-m-d H:i:s'));
    }

    public function test_it_returns_null_for_an_invalid_email(): void
    {
        $device = $this->mapper->map([
            'hardware' => ['serialNumber' => 'SER-1'],
            'userAndLocation' => ['email' => 'not-an-email'],
        ]);

        $this->assertNull($device->email);
    }

    public function test_it_returns_null_for_a_missing_serial(): void
    {
        $device = $this->mapper->map([
            'userAndLocation' => ['email' => 'user@company.test'],
        ]);

        $this->assertNull($device->serial);
    }

    public function test_it_sums_storage_disks(): void
    {
        $device = $this->mapper->map([
            'storage' => [
                'disks' => [
                    ['sizeMegabytes' => 524288],
                    ['sizeMegabytes' => 524288],
                ],
            ],
        ]);

        $this->assertSame(1024.0, $device->attributes['storage_gb']);
    }

    public function test_it_omits_unavailable_attributes(): void
    {
        $device = $this->mapper->map([
            'hardware' => ['serialNumber' => 'SER-1'],
        ]);

        $this->assertArrayNotHasKey('storage_gb', $device->attributes);
        $this->assertArrayNotHasKey('ram_gb', $device->attributes);
    }
}
