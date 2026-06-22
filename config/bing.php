<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Azure Maps Search — optional fallback when Playwright fails.
    | API key lives on the SRP service (.env AZURE_MAPS_KEY).
    |
    | Free tier: https://azure.microsoft.com/products/azure-maps
    */
    'api_base_url' => env('BING_API_BASE_URL', 'https://atlas.microsoft.com'),
    'api_version' => env('BING_API_VERSION', '1.0'),
    'search_path' => env('BING_SEARCH_PATH', '/search/fuzzy/json'),
    'entity_type' => env('BING_ENTITY_TYPE', 'POI'),
    'max_limit' => (int) env('BING_MAX_LIMIT', 100),

];
