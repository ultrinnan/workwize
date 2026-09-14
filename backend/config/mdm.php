<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default MDM Provider
    |--------------------------------------------------------------------------
    |
    | The provider used when a sync is triggered. Additional providers can be
    | registered inside AppServiceProvider without touching the sync logic.
    |
    */

    'default' => env('MDM_PROVIDER', 'jamf'),

    'providers' => [

        'jamf' => [
            // The assignment ships a static Jamf API response. In a real
            // integration this path would be replaced by an HTTP client.
            'mock_path' => env(
                'MDM_JAMF_MOCK_PATH',
                resource_path('mdm/jamf-mock-response.json'),
            ),
        ],

    ],

];
