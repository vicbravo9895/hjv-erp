<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Samsara API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Samsara API integration including authentication,
    | endpoints, and synchronization settings.
    |
    */

    'api_token' => env('SAMSARA_API_TOKEN'),
    'api_url' => env('SAMSARA_API_URL', 'https://api.samsara.com'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Samsara API endpoints for different resources
    |
    */
    'endpoints' => [
        'vehicles' => env('SAMSARA_VEHICLES_ENDPOINT', '/fleet/vehicles/stats/feed'),
        'trailers' => env('SAMSARA_TRAILERS_ENDPOINT', '/beta/fleet/trailers/stats/feed'),
        'drivers' => env('SAMSARA_DRIVERS_ENDPOINT', '/fleet/drivers'),
        'addresses' => env('SAMSARA_ADDRESSES_ENDPOINT', '/addresses'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Synchronization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic synchronization with Samsara API
    |
    */
    'sync' => [
        // HTTP client settings
        'timeout' => env('SAMSARA_SYNC_TIMEOUT', 30),
        'retry_times' => env('SAMSARA_SYNC_RETRY_TIMES', 3),
        'retry_delay' => env('SAMSARA_SYNC_RETRY_DELAY', 1000), // milliseconds

        // Pagination settings
        'page_limit' => env('SAMSARA_SYNC_PAGE_LIMIT', 100),

        // Schedule settings
        'schedule_frequency' => env('SAMSARA_SYNC_FREQUENCY', '*/5'), // Every 5 minutes by default
        'enable_vehicles_sync' => env('SAMSARA_SYNC_VEHICLES_ENABLED', true),
        'enable_trailers_sync' => env('SAMSARA_SYNC_TRAILERS_ENABLED', true),
        'enable_drivers_sync' => env('SAMSARA_SYNC_DRIVERS_ENABLED', false),

        // Operating hours for sync (24-hour format)
        'operating_hours' => [
            'start' => env('SAMSARA_SYNC_START_HOUR', 6), // 6 AM
            'end' => env('SAMSARA_SYNC_END_HOUR', 22), // 10 PM
        ],

        // Default tag IDs to filter data (comma-separated in .env)
        'default_tag_ids' => env('SAMSARA_DEFAULT_TAG_IDS') ? 
            explode(',', env('SAMSARA_DEFAULT_TAG_IDS')) : null,

        // Sync only during weekdays
        'weekdays_only' => env('SAMSARA_SYNC_WEEKDAYS_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Mapping
    |--------------------------------------------------------------------------
    |
    | Configuration for mapping Samsara data to local database fields
    |
    */
    'mapping' => [
        'vehicle_status' => [
            'active' => 'available',
            'inactive' => 'out_of_service',
        ],
        
        'engine_states' => [
            'Running' => 'running',
            'Off' => 'off',
            'Idle' => 'idle',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Samsara integration logging
    |
    */
    'logging' => [
        'channel' => env('SAMSARA_LOG_CHANNEL', 'daily'),
        'level' => env('SAMSARA_LOG_LEVEL', 'info'),
        'log_api_requests' => env('SAMSARA_LOG_API_REQUESTS', false),
        'log_sync_details' => env('SAMSARA_LOG_SYNC_DETAILS', true),
    ],
];