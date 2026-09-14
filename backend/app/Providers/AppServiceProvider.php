<?php

namespace App\Providers;

use App\Contracts\MdmProvider;
use App\Services\Mdm\Jamf\JamfDeviceMapper;
use App\Services\Mdm\Jamf\JamfProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MdmProvider::class, function ($app): MdmProvider {
            $provider = (string) config('mdm.default');

            return match ($provider) {
                'jamf' => new JamfProvider(
                    $app->make(JamfDeviceMapper::class),
                    (string) config('mdm.providers.jamf.mock_path'),
                ),
                default => throw new InvalidArgumentException(
                    "Unsupported MDM provider [{$provider}]."
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
