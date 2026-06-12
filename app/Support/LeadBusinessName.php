<?php

namespace App\Support;

class LeadBusinessName
{
    /**
     * @return array{company: string, first_name: string, last_name: string}
     */
    public static function fromBusinessName(string $businessName): array
    {
        $businessName = trim($businessName);
        $parts = preg_split('/\s+/', $businessName, 2) ?: [];

        return [
            'company' => $businessName,
            'first_name' => $parts[0] ?: 'Business',
            'last_name' => $parts[1] ?? 'Lead',
        ];
    }
}
