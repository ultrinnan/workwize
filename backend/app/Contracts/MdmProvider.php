<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\MdmDevice;

/**
 * Contract implemented by every MDM integration.
 *
 * Adding a new provider means implementing this interface and registering it
 * in AppServiceProvider. DeviceSyncService never references a concrete
 * provider, so the sync behaviour stays identical across MDMs.
 */
interface MdmProvider
{
    /**
     * Machine-readable provider name (e.g. "jamf").
     */
    public function name(): string;

    /**
     * Fetch every device known to the provider.
     *
     * Devices that are not assigned to a user or that are missing a serial
     * are still returned; the sync engine decides how to handle them.
     *
     * @return list<MdmDevice>
     */
    public function fetchDevices(): array;
}
