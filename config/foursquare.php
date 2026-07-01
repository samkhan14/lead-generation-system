<?php

return [
    'api_base_url' => env('FOURSQUARE_API_BASE_URL', 'https://api.foursquare.com'),
    'search_path' => env('FOURSQUARE_SEARCH_PATH', '/v3/places/search'),
    'max_limit' => (int) env('FOURSQUARE_MAX_LIMIT', 50),
];
