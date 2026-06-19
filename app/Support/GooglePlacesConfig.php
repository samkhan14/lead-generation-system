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
        return 'GOOGLE_PLACES_API_KEY is not set — Playwright-only mode. Verification will still run via Google Search.';
    }
}
