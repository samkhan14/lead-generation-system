<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nominatim / Overpass
    |--------------------------------------------------------------------------
    | Free OSM APIs — no API key. Nominatim requires a descriptive User-Agent.
    | Set OSM_USER_AGENT for a full override, or OSM_CONTACT_EMAIL (+ APP_URL).
    */
    'nominatim_url' => env('OSM_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
    'overpass_url' => env('OSM_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
    'user_agent' => \App\Support\OpenStreetMapConfig::userAgent(),

    /*
    |--------------------------------------------------------------------------
    | Search radius (metres) by location specificity
    |--------------------------------------------------------------------------
    */
    'radius_with_area_m' => (int) env('OSM_RADIUS_AREA_M', 3000),
    'radius_with_city_m' => (int) env('OSM_RADIUS_CITY_M', 8000),
    'radius_country_only_m' => (int) env('OSM_RADIUS_COUNTRY_M', 25000),

    /*
    |--------------------------------------------------------------------------
    | Keyword → OSM tag filters
    |--------------------------------------------------------------------------
    | Each keyword maps to one or more {key: value} tag pairs searched via Overpass.
    | When no mapping exists, the connector falls back to name/tag regex search.
    */
    'keyword_tags' => [
        'dentist' => [['amenity' => 'dentist']],
        'restaurant' => [['amenity' => 'restaurant'], ['amenity' => 'fast_food']],
        'cafe' => [['amenity' => 'cafe']],
        'coffee' => [['amenity' => 'cafe']],
        'gym' => [['leisure' => 'fitness_centre'], ['leisure' => 'sports_centre']],
        'hotel' => [['tourism' => 'hotel'], ['tourism' => 'guest_house']],
        'pharmacy' => [['amenity' => 'pharmacy']],
        'hospital' => [['amenity' => 'hospital'], ['amenity' => 'clinic']],
        'clinic' => [['amenity' => 'clinic'], ['amenity' => 'doctors']],
        'lawyer' => [['office' => 'lawyer']],
        'salon' => [['shop' => 'hairdresser'], ['shop' => 'beauty']],
        'barber' => [['shop' => 'hairdresser']],
        'plumber' => [['craft' => 'plumber']],
        'electrician' => [['craft' => 'electrician']],
        'bakery' => [['shop' => 'bakery']],
        'supermarket' => [['shop' => 'supermarket'], ['shop' => 'convenience']],
        'grocery' => [['shop' => 'supermarket'], ['shop' => 'convenience']],
        'electronics' => [['shop' => 'electronics'], ['shop' => 'computer']],
        'computer' => [['shop' => 'computer'], ['shop' => 'electronics']],
        'garage' => [['shop' => 'car_repair'], ['amenity' => 'car_repair']],
        'mechanic' => [['shop' => 'car_repair'], ['amenity' => 'car_repair']],
        'school' => [['amenity' => 'school']],
        'bank' => [['amenity' => 'bank']],
        'atm' => [['amenity' => 'atm']],
        'pet' => [['shop' => 'pet']],
        'florist' => [['shop' => 'florist']],
        'jewelry' => [['shop' => 'jewelry']],
        'jeweller' => [['shop' => 'jewelry']],
        'tailor' => [['craft' => 'tailor'], ['shop' => 'tailor']],
        'laundry' => [['shop' => 'laundry'], ['shop' => 'dry_cleaning']],
        'real estate' => [['office' => 'estate_agent']],
        'estate agent' => [['office' => 'estate_agent']],
    ],

    'fallback_name_search' => true,

];
