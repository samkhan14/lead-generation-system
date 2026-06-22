<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directory lead sources (merge-on-re-ingest)
    |--------------------------------------------------------------------------
    */
    'directory_sources' => ['google_maps', 'yelp', 'openstreetmap', 'bing_places'],

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
    */
    'verification' => [
        'srp_timeout' => 90,
        'min_phone_digits' => 7,

        'layers' => [
            'places_api',
            'playwright_google_search',
            'website_http',
        ],
    ],

];
