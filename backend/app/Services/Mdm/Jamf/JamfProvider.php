<?php

declare(strict_types=1);

namespace App\Services\Mdm\Jamf;

use App\Contracts\MdmProvider;
use App\Exceptions\MdmProviderException;
use JsonException;

/**
 * Jamf implementation of the MdmProvider contract.
 *
 * Per the assignment the backend reads the provided Jamf mock response
 * directly from disk. Swapping this for a real Jamf API client only requires
 * changing how `fetchDevices()` obtains the raw payload; the mapping and sync
 * behaviour stay the same.
 */
final class JamfProvider implements MdmProvider
{
    public function __construct(
        private readonly JamfDeviceMapper $mapper,
        private readonly string $mockPath,
    ) {}

    public function name(): string
    {
        return 'jamf';
    }

    public function fetchDevices(): array
    {
        if (! is_file($this->mockPath) || ! is_readable($this->mockPath)) {
            throw new MdmProviderException(
                "Jamf mock response is not readable at [{$this->mockPath}]."
            );
        }

        $contents = file_get_contents($this->mockPath);

        if ($contents === false) {
            throw new MdmProviderException(
                "Unable to read the Jamf mock response at [{$this->mockPath}]."
            );
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MdmProviderException(
                "Invalid JSON in Jamf mock response: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! is_array($payload)) {
            return [];
        }

        $results = $payload['results'] ?? $payload;

        if (! is_array($results)) {
            return [];
        }

        return $this->mapper->mapMany($results);
    }
}
