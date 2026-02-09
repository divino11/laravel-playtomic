<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Playtomic API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Playtomic REST API. The default points to the
    | production v1 endpoint. Override this for testing or staging.
    |
    */

    'base_url' => env('PLAYTOMIC_BASE_URL', 'https://api.playtomic.io/v1'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a response from the Playtomic API.
    |
    */

    'timeout' => (int) env('PLAYTOMIC_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Default Search Radius
    |--------------------------------------------------------------------------
    |
    | Default radius in meters for venue searches. 50 000 m = 50 km.
    |
    */

    'default_radius' => (int) env('PLAYTOMIC_DEFAULT_RADIUS', 50000),

];
