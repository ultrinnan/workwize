<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial_code' => strtoupper(fake()->unique()->bothify('???####????')),
            'employee_id' => Employee::factory(),
            'device_name' => fake()->words(3, true),
            'provider' => 'jamf',
            'external_id' => fake()->uuid(),
            'attributes' => [
                'make' => 'Apple',
                'model' => 'MacBook Pro',
                'ram_gb' => 16.0,
                'storage_gb' => 512.0,
            ],
            'last_seen_at' => now(),
            'missing_at' => null,
        ];
    }

    public function missing(): static
    {
        return $this->state(fn (): array => ['missing_at' => now()]);
    }
}
