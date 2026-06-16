<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SRP Service URL
    |--------------------------------------------------------------------------
    | The base URL of the standalone Node.js scraper service (srp-service).
    | This service must be running before scrape jobs can be dispatched.
    */
    'service_url' => env('SRP_SERVICE_URL', 'http://localhost:3100'),

    /*
    |--------------------------------------------------------------------------
    | Job Timeout
    |--------------------------------------------------------------------------
    | Maximum seconds to wait for the scraper service to acknowledge the job.
    | The actual scraping runs asynchronously and reports back via callbacks.
    */
    'dispatch_timeout' => (int) env('SRP_DISPATCH_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Lead Source Channels
    |--------------------------------------------------------------------------
    | Each connector is an independent source. Jobs are isolated per channel so
    | multiple connectors (e.g. Google Maps and Reddit) can run concurrently
    | without conflicting. Add new connectors here as they come online.
    */
    'default_channel' => 'google_maps',

    'channels' => [
        'google_maps' => [
            'label' => 'Google Maps',
            'keyword_label' => 'Keyword',
            'keyword_placeholder' => 'e.g. dentist, restaurant',
            'requires_location' => true,
        ],
        'reddit' => [
            'label' => 'Reddit',
            'keyword_label' => 'Intent keyword',
            'keyword_placeholder' => 'e.g. need a website, looking for developer',
            'requires_location' => false,
        ],
    ],
];
