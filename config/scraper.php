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
];
