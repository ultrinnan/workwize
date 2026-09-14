<?php

declare(strict_types=1);

namespace App\Services\Mdm\Jamf;

use App\Data\MdmDevice;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Maps a raw Jamf device payload into the provider-agnostic MdmDevice DTO.
 *
 * The field fallbacks mirror the reference implementation and are intentionally
 * defensive: Jamf payloads vary between API versions and endpoints.
 */
final class JamfDeviceMapper
{
    /**
     * @param  array<string, mixed>  $device
     */
    public function map(array $device): MdmDevice
    {
        return new MdmDevice(
            serial: $this->serial($device),
            email: $this->email($device),
            name: $this->name($device),
            phone: $this->phone($device),
            position: $this->position($device),
            deviceName: $this->deviceName($device),
            externalId: $this->externalId($device),
            attributes: $this->attributes($device),
            lastSeenAt: $this->lastSeenAt($device),
        );
    }

    /**
     * @param  array<int, mixed>  $devices
     * @return list<MdmDevice>
     */
    public function mapMany(array $devices): array
    {
        $mapped = [];

        foreach ($devices as $device) {
            if (is_array($device)) {
                $mapped[] = $this->map($device);
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function serial(array $device): ?string
    {
        return $this->stringOrNull(data_get($device, 'hardware.serialNumber'));
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function email(array $device): ?string
    {
        $email = data_get($device, 'userAndLocation.email')
            ?? data_get($device, 'userAndLocation.emailAddress')
            ?? data_get($device, 'username');

        $email = $this->stringOrNull($email);

        if ($email === null) {
            return null;
        }

        $email = strtolower($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function name(array $device): ?string
    {
        return $this->stringOrNull(
            data_get($device, 'userAndLocation.realname')
                ?? data_get($device, 'userAndLocation.realName')
        );
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function phone(array $device): ?string
    {
        return $this->stringOrNull(
            data_get($device, 'userAndLocation.phone')
                ?? data_get($device, 'userAndLocation.phoneNumber')
        );
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function position(array $device): ?string
    {
        return $this->stringOrNull(data_get($device, 'userAndLocation.position'));
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function deviceName(array $device): string
    {
        $name = data_get($device, 'general.displayName')
            ?? data_get($device, 'general.name')
            ?? data_get($device, 'hardware.model')
            ?? data_get($device, 'model')
            ?? data_get($device, 'name');

        return $this->stringOrNull($name) ?? 'Unknown';
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function externalId(array $device): ?string
    {
        return $this->stringOrNull(data_get($device, 'udid'))
            ?? $this->stringOrNull(data_get($device, 'id'));
    }

    /**
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
    private function attributes(array $device): array
    {
        $attributes = [
            'make' => $this->stringOrNull(data_get($device, 'hardware.make')),
            'model' => $this->stringOrNull(
                data_get($device, 'hardware.model') ?? data_get($device, 'model')
            ),
            'model_identifier' => $this->stringOrNull(data_get($device, 'hardware.modelIdentifier')),
            'platform' => $this->stringOrNull(data_get($device, 'general.platform')),
            'processor' => $this->stringOrNull(data_get($device, 'hardware.processorType')),
            'core_count' => $this->numericOrNull(data_get($device, 'hardware.coreCount')),
            'ram_gb' => $this->ramGb($device),
            'storage_gb' => $this->storageGb($device),
            'battery_percent' => $this->numericOrNull(data_get($device, 'hardware.batteryCapacityPercent')),
        ];

        return array_filter(
            $attributes,
            static fn (mixed $value): bool => $value !== null
        );
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function ramGb(array $device): ?float
    {
        $mb = $this->numericOrNull(data_get($device, 'hardware.totalRamMegabytes'));

        if ($mb === null || $mb <= 0) {
            return null;
        }

        return round($mb / 1024, 2);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function storageGb(array $device): ?float
    {
        $disks = data_get($device, 'storage.disks');

        if (! is_array($disks)) {
            return null;
        }

        $totalMb = 0.0;

        foreach ($disks as $disk) {
            $size = is_array($disk) ? ($disk['sizeMegabytes'] ?? null) : null;
            $size = $this->numericOrNull($size);

            if ($size !== null) {
                $totalMb += $size;
            }
        }

        if ($totalMb <= 0) {
            return null;
        }

        return round($totalMb / 1024, 2);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function lastSeenAt(array $device): ?CarbonImmutable
    {
        $value = $this->stringOrNull(
            data_get($device, 'general.lastContactTime')
                ?? data_get($device, 'general.reportDate')
        );

        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function numericOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
