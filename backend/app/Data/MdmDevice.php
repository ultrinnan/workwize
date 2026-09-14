<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Provider-agnostic representation of a single MDM device.
 *
 * Every MDM provider (Jamf, Intune, Kandji, ...) is responsible for mapping
 * its own payload into this DTO. The sync engine only ever works with this
 * shape, which keeps provider-specific logic out of the sync flow.
 */
final readonly class MdmDevice
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public ?string $serial,
        public ?string $email,
        public ?string $name,
        public ?string $phone,
        public ?string $position,
        public string $deviceName,
        public ?string $externalId,
        public array $attributes,
        public ?CarbonImmutable $lastSeenAt,
    ) {}
}
