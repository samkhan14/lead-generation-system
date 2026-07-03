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
    | Pre-store contact verification
    |--------------------------------------------------------------------------
    | Leads are rejected before persistence unless both email and phone pass.
    | email_dns_check is optional because DNS/network checks can be flaky in local
    | and CI environments; plug external verification APIs into the service later.
    */
    'contact_verification' => [
        'require_email' => true,
        'require_phone' => true,
        'discover_email_from_website' => (bool) env('LEAD_DISCOVER_EMAIL_FROM_WEBSITE', true),
        'website_email_timeout' => (int) env('LEAD_WEBSITE_EMAIL_TIMEOUT', 6),
        'email_dns_check' => (bool) env('LEAD_EMAIL_DNS_CHECK', false),
        'min_phone_digits' => (int) env('LEAD_MIN_PHONE_DIGITS', 7),
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
