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
        if ($website === null || trim($website) === '') {
            return null;
        }

        $normalized = strtolower(trim($website));
        $normalized = preg_replace('#^https?://#', '', $normalized);
        $normalized = preg_replace('#^www\.#', '', $normalized);
        $normalized = rtrim($normalized, '/');

        return $normalized !== '' ? $normalized : null;
    }
}
