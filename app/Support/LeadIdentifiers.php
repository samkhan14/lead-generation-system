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
            'hotfrog.com', 'hotfrog.co.uk', 'hotfrog.com.au', 'hotfrog.ca', 'hotfrog.com.pk',
            'hotfrog.in', 'hotfrog.ie', 'hotfrog.co.za', 'hotfrog.de', 'hotfrog.fr',
            'bing.com', 'apple.com',
        ];

        foreach ($directoryHosts as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                return null;
            }
        }

        if (str_contains($host, 'hotfrog.')) {
            return null;
        }

        foreach ([
            'yellowpages.', 'yell.com', 'manta.com', 'foursquare.com',
            'themanifest.com', 'goodfirms.co', 'designrush.com', 'upcity.com',
        ] as $fragment) {
            if (str_contains($host, $fragment)) {
                return null;
            }
        }

        if (str_contains($host, 'google.') && str_contains($website, '/maps')) {
            return null;
        }

        return trim($website);
    }

    /**
     * Normalize Google place IDs scraped from Maps URLs (may embed ChIJ inside data= blobs).
     */
    public static function normalizeGooglePlaceId(?string $placeId): ?string
    {
        if ($placeId === null || trim($placeId) === '') {
            return null;
        }

        $placeId = trim($placeId);

        if (preg_match('/(ChIJ[\w-]+)/', $placeId, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(0x[a-f0-9]+:0x[a-f0-9]+)/i', $placeId, $matches)) {
            return $matches[1];
        }

        if (str_contains($placeId, 'data=') || str_contains($placeId, '!4m')) {
            return null;
        }

        return $placeId;
    }

    /**
     * Remove Google Maps icon font artifacts from scraped addresses.
     */
    public static function sanitizeAddress(?string $address): ?string
    {
        if ($address === null || trim($address) === '') {
            return null;
        }

        $cleaned = preg_replace('/[\x{E000}-\x{F8FF}]/u', '', $address);
        $cleaned = preg_replace('/\s+/u', ' ', trim((string) $cleaned));

        return $cleaned !== '' ? $cleaned : null;
    }
}
