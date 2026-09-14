<?php

declare(strict_types=1);

namespace App\Services\Mdm;

use App\Contracts\MdmProvider;
use App\Data\MdmDevice;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\SyncRun;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Provider-agnostic sync engine.
 *
 * Given any MdmProvider it imports assigned devices into the local database,
 * enforces the MDM sync behaviour rules and records a report of the run.
 */
final class DeviceSyncService
{
    /**
     * Run a full sync for the given provider and return the recorded report.
     *
     * @throws Throwable
     */
    public function sync(MdmProvider $provider): SyncRun
    {
        $providerName = $provider->name();

        $run = SyncRun::query()->create([
            'provider' => $providerName,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $result = [
            'created_assets' => 0,
            'updated_assets' => 0,
            'restored_assets' => 0,
            'unassigned_assets' => 0,
            'missing_assets' => 0,
            'skipped_unassigned' => 0,
            'skipped_missing_serial' => 0,
        ];

        try {
            $devices = $provider->fetchDevices();

            DB::transaction(function () use ($devices, $providerName, &$result): void {
                $seenSerials = [];

                foreach ($devices as $device) {
                    if ($device->serial === null) {
                        $result['skipped_missing_serial']++;

                        continue;
                    }

                    $seenSerials[] = $device->serial;

                    if ($device->email === null) {
                        if ($this->removeUnassignedAsset($device->serial, $providerName)) {
                            $result['unassigned_assets']++;
                        }

                        $result['skipped_unassigned']++;

                        continue;
                    }

                    $employee = $this->syncEmployee($device);
                    $outcome = $this->syncAsset($device, $employee, $providerName);

                    if ($outcome !== null) {
                        $result[$outcome]++;
                    }
                }

                $result['missing_assets'] = $this->markMissingAssets($providerName, $seenSerials);
            });

            $run->fill([
                'status' => 'success',
                'total_devices' => count($devices),
                'finished_at' => now(),
                ...$result,
            ])->save();
        } catch (Throwable $exception) {
            $run->fill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }

        return $run;
    }

    /**
     * Create or update the employee, only overwriting fields with real values.
     * Existing data is never wiped by a null from the provider.
     */
    private function syncEmployee(MdmDevice $device): Employee
    {
        $employee = Employee::query()->firstOrNew(['email' => $device->email]);

        $changed = ! $employee->exists;

        $incoming = [
            'name' => $device->name,
            'phone' => $device->phone,
            'position' => $device->position,
        ];

        foreach ($incoming as $field => $value) {
            if ($value !== null && $employee->{$field} !== $value) {
                $employee->{$field} = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $employee->save();
        }

        return $employee;
    }

    /**
     * Create, restore or update the asset for a device.
     *
     * @return key-of<array{
     *     created_assets: int,
     *     updated_assets: int,
     *     restored_assets: int
     * }>|null
     */
    private function syncAsset(MdmDevice $device, Employee $employee, string $providerName): ?string
    {
        /** @var Asset $asset */
        $asset = Asset::query()->firstOrNew(['serial_code' => $device->serial]);

        $isNew = ! $asset->exists;
        $wasMissing = $asset->exists && $asset->missing_at !== null;

        $desired = [
            'employee_id' => $employee->id,
            'device_name' => $device->deviceName,
            'provider' => $providerName,
            'external_id' => $device->externalId,
            'attributes' => $device->attributes,
        ];

        $changed = $isNew || $this->hasChanged($asset, $desired);

        $asset->fill($desired);
        $asset->missing_at = null;
        $asset->last_seen_at = $device->lastSeenAt ?? now();
        $asset->save();

        return match (true) {
            $isNew => 'created_assets',
            $wasMissing => 'restored_assets',
            $changed => 'updated_assets',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $desired
     */
    private function hasChanged(Asset $asset, array $desired): bool
    {
        foreach ($desired as $key => $value) {
            if ($asset->{$key} != $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * A device that is still reported by the provider but is no longer assigned
     * must disappear from the assigned assets list.
     */
    private function removeUnassignedAsset(string $serial, string $providerName): bool
    {
        $asset = Asset::query()
            ->where('serial_code', $serial)
            ->where('provider', $providerName)
            ->first();

        if ($asset === null) {
            return false;
        }

        $asset->delete();

        return true;
    }

    /**
     * Flag assets that were imported before but are absent from the latest
     * provider payload (deactivated/removed in the MDM).
     *
     * @param  list<string>  $seenSerials
     */
    private function markMissingAssets(string $providerName, array $seenSerials): int
    {
        $query = Asset::query()
            ->where('provider', $providerName)
            ->whereNull('missing_at');

        if ($seenSerials !== []) {
            $query->whereNotIn('serial_code', $seenSerials);
        }

        return $query->update(['missing_at' => now()]);
    }
}
