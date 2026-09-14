<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_code' => $this->serial_code,
            'device_name' => $this->device_name,
            'provider' => $this->provider,
            'external_id' => $this->external_id,
            'attributes' => $this->attributes ?? [],
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'missing_at' => $this->missing_at?->toIso8601String(),
            'employee' => $this->whenLoaded(
                'employee',
                fn (): ?EmployeeResource => $this->employee
                    ? new EmployeeResource($this->employee)
                    : null,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
