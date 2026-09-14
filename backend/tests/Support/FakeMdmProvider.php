<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\MdmProvider;
use App\Data\MdmDevice;

/**
 * In-memory provider used to exercise the sync engine without touching disk.
 */
final class FakeMdmProvider implements MdmProvider
{
    /**
     * @param  list<MdmDevice>  $devices
     */
    public function __construct(
        private array $devices,
        private string $name = 'fake',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function fetchDevices(): array
    {
        return $this->devices;
    }
}
