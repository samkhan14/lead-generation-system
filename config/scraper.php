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
    | Callback Base URL
    |--------------------------------------------------------------------------
    | Laravel URL the SRP service uses for ingest + job callbacks. When using
    | Herd/Valet, set this to your .test domain — APP_URL alone may be wrong.
    */
    'callback_url' => env('SCRAPER_CALLBACK_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Job Timeout
    |--------------------------------------------------------------------------
    */
    'dispatch_timeout' => (int) env('SRP_DISPATCH_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Stale Running Jobs
    |--------------------------------------------------------------------------
    | Jobs stuck in "running" (SRP crash, Playwright hang) are marked failed
    | after this many minutes by scrape:fail-stale.
    */
    'stale_running_minutes' => (int) env('SCRAPER_STALE_RUNNING_MINUTES', 45),

    /*
    |--------------------------------------------------------------------------
    | Lead Source Channels
    |--------------------------------------------------------------------------
    | tier: warm = directory/listing sources | hot = intent/social (manual+auto)
    | enabled: shown in CRM and accepted for new jobs
    | implemented: SRP connector exists and can run
    */
    'default_channel' => 'google_maps',

    'channels' => [
        'google_maps' => [
            'tier' => 'warm',
            'enabled' => true,
            'implemented' => true,
            'label' => 'Google Maps',
            'keyword_label' => 'Keyword',
            'keyword_placeholder' => 'e.g. dentist, restaurant',
            'requires_location' => true,
            'dedupe_metadata_key' => 'google_place_id',
        ],
        'yelp' => [
            'tier' => 'warm',
            'enabled' => true,
            'implemented' => true,
            'label' => 'Yelp',
            'keyword_label' => 'Business type',
            'keyword_placeholder' => 'e.g. plumber, auto repair',
            'requires_location' => true,
            'dedupe_metadata_key' => 'yelp_business_id',
        ],
        'openstreetmap' => [
            'tier' => 'warm',
            'enabled' => false,
            'implemented' => false,
            'label' => 'OpenStreetMap',
            'keyword_label' => 'Keyword',
            'keyword_placeholder' => 'e.g. cafe, gym',
            'requires_location' => true,
            'dedupe_metadata_key' => 'osm_id',
        ],
        'bing_places' => [
            'tier' => 'warm',
            'enabled' => false,
            'implemented' => false,
            'label' => 'Bing Places',
            'keyword_label' => 'Keyword',
            'keyword_placeholder' => 'e.g. lawyer, salon',
            'requires_location' => true,
            'dedupe_metadata_key' => 'bing_entity_id',
        ],
        'reddit' => [
            'tier' => 'hot',
            'enabled' => false,
            'implemented' => true,
            'label' => 'Reddit',
            'keyword_label' => 'Intent keyword',
            'keyword_placeholder' => 'e.g. need a website, looking for developer',
            'requires_location' => false,
            'industry_label' => 'Subreddits',
            'industry_placeholder' => 'e.g. smallbusiness, entrepreneur',
            'dedupe_metadata_key' => 'reddit_post_id',
        ],
        'facebook' => [
            'tier' => 'hot',
            'enabled' => false,
            'implemented' => false,
            'label' => 'Facebook Pages',
            'keyword_label' => 'Page / niche keyword',
            'keyword_placeholder' => 'e.g. local bakery',
            'requires_location' => true,
            'dedupe_metadata_key' => 'facebook_page_id',
        ],
        'instagram' => [
            'tier' => 'hot',
            'enabled' => false,
            'implemented' => false,
            'label' => 'Instagram',
            'keyword_label' => 'Hashtag / niche',
            'keyword_placeholder' => 'e.g. #smallbusiness',
            'requires_location' => false,
            'dedupe_metadata_key' => 'instagram_profile_id',
        ],
    ],
];
