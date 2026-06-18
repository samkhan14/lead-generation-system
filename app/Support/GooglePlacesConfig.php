<?php

namespace App\Support;

class GooglePlacesConfig
{
    public static function isConfigured(): bool
    {
        return filled(config('google_places.api_key'));
    }

    public static function unavailableMessage(): string
    {
        return 'GOOGLE_PLACES_API_KEY is not set — using Playwright-only mode. Re-scrape with city/area, or add the key later and run php artisan leads:enrich-directory.';
    }
}
