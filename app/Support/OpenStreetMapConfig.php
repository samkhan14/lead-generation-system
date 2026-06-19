<?php

namespace App\Support;

class OpenStreetMapConfig
{
    public static function userAgent(): string
    {
        if ($explicit = env('OSM_USER_AGENT')) {
            return $explicit;
        }

        $appName = preg_replace('/\s+/', '-', strtolower((string) config('app.name', 'laravel')));
        $appUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $email = trim((string) env('OSM_CONTACT_EMAIL', ''));

        if ($email !== '') {
            return sprintf('%s-lead-scraper/1.0 (%s; +%s)', $appName, $email, $appUrl);
        }

        return sprintf('%s-lead-scraper/1.0 (+%s)', $appName, $appUrl);
    }
}
