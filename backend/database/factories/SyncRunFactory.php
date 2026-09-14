<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    protected $model = SyncRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'jamf',
            'status' => 'success',
            'total_devices' => 0,
            'created_assets' => 0,
            'updated_assets' => 0,
            'restored_assets' => 0,
            'unassigned_assets' => 0,
            'missing_assets' => 0,
            'skipped_unassigned' => 0,
            'skipped_missing_serial' => 0,
            'started_at' => now(),
            'finished_at' => now(),
            'error_message' => null,
        ];
    }
}
