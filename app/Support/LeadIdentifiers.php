<?php

namespace App\Support;

class LeadIdentifiers
{
    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    public static function normalizeWebsite(?string $website): ?string
    {
        $sanitized = self::sanitizeBusinessWebsite($website);

        if ($sanitized === null) {
            return null;
        }

        $normalized = strtolower(trim($sanitized));
        $normalized = preg_replace('#^https?://#', '', $normalized);
        $normalized = preg_replace('#^www\.#', '', $normalized);
        $normalized = rtrim($normalized, '/');

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Strip directory/listing profile URLs — not the business's own website.
     */
    public static function sanitizeBusinessWebsite(?string $website): ?string
    {
        if ($website === null || trim($website) === '') {
            return null;
        }

        $website = trim($website);

        if (! str_contains($website, '://')) {
            $website = 'https://'.$website;
        }

        $host = strtolower((string) parse_url($website, PHP_URL_HOST));

        if ($host === '') {
            return null;
        }

        $directoryHosts = [
            'yelp.com', 'yelp.co.uk', 'yelp.ca', 'yelp.de', 'yelp.fr', 'yelp.com.au',
            'google.com', 'maps.google.com', 'g.page', 'goo.gl',
            'facebook.com', 'fb.com', 'instagram.com',
            'openstreetmap.org',
            'bing.com', 'apple.com',
        ];

        foreach ($directoryHosts as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                return null;
            }
        }

        if (str_contains($host, 'google.') && str_contains($website, '/maps')) {
            return null;
        }

        return trim($website);
    }
}
