<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directory lead sources (merge-on-re-ingest)
    |--------------------------------------------------------------------------
    */
    'directory_sources' => [
        'google_maps', 'yelp', 'openstreetmap', 'bing_places', 'hotfrog',
        'yellow_pages', 'manta', 'foursquare', 'the_manifest', 'goodfirms', 'designrush', 'upcity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality fields
    |--------------------------------------------------------------------------
    */
    'fields' => ['company', 'phone', 'website', 'address'],

    /*
    |--------------------------------------------------------------------------
    | Verification pipeline — runs automatically on every ingest via queue.
    |--------------------------------------------------------------------------
    | Places API layer only runs when GOOGLE_PLACES_API_KEY is set in .env.
    | website_analysis calls SRP /analyze-website via Playwright — ~30-90s per lead.
    */
    'verification' => [
        'srp_timeout' => 90,
        'min_phone_digits' => 7,

        'layers' => [
            'places_api',
            'playwright_google_search',
            'website_http',
            'website_analysis',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Website analysis settings
    |--------------------------------------------------------------------------
    */
    'website_analysis' => [
        'timeout' => 120,
        'revamp_year_threshold' => 2022,
        'cms_high_revamp' => ['Joomla', 'Drupal', 'static'],
    ],

];
