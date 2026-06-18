<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Places API (optional enrichment fallback)
    |--------------------------------------------------------------------------
    | Used only when Playwright misses website/phone, or by leads:enrich-directory.
    | When empty, scraping continues without API — no hard failure.
    */
    'api_key' => env('GOOGLE_PLACES_API_KEY'),

    'details_url' => 'https://places.googleapis.com/v1/places/',

    'search_url' => 'https://places.googleapis.com/v1/places:searchText',

];
