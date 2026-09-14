<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SyncRun
 */
class SyncRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'status' => $this->status,
            'total_devices' => $this->total_devices,
            'created_assets' => $this->created_assets,
            'updated_assets' => $this->updated_assets,
            'restored_assets' => $this->restored_assets,
            'unassigned_assets' => $this->unassigned_assets,
            'missing_assets' => $this->missing_assets,
            'skipped_unassigned' => $this->skipped_unassigned,
            'skipped_missing_serial' => $this->skipped_missing_serial,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'error_message' => $this->error_message,
        ];
    }
}
