<?php

namespace App\Support;

class LeadDataQuality
{
    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    public static function assess(array $normalized): array
    {
        $fields = config('lead_quality.fields', ['company', 'phone', 'website', 'address']);

        $present = [];
        $missing = [];

        foreach ($fields as $field) {
            $value = $field === 'address'
                ? data_get($normalized, 'metadata.address')
                : ($normalized[$field] ?? null);

            if (filled($value)) {
                $present[] = $field;
            } else {
                $missing[] = $field;
            }
        }

        $score = (int) round((count($present) / max(count($fields), 1)) * 100);

        return [
            'score' => $score,
            'missing_fields' => $missing,
            'is_complete' => $missing === [],
            'has_website' => ! in_array('website', $missing, true),
            'has_phone' => ! in_array('phone', $missing, true),
            'assessed_at' => now()->toIso8601String(),
            'enrichment_hint' => in_array('website', $missing, true) && ! GooglePlacesConfig::isConfigured()
                ? GooglePlacesConfig::unavailableMessage()
                : null,
        ];
    }
}
