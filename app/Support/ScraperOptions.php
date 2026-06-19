<?php

namespace App\Support;

class ScraperOptions
{
    /**
     * @return array{
     *   business_type_groups: array<int, array{label: string, options: array<int, array{value: string, label: string}>}>,
     *   intent_keyword_groups: array<int, array{label: string, options: array<int, array{value: string, label: string}>}>,
     * }
     */
    public static function forUi(): array
    {
        return [
            'business_type_groups' => self::groups('scraper_options.business_types'),
            'intent_keyword_groups' => self::groups('scraper_options.intent_keywords'),
        ];
    }

    /**
     * Keyword / business-type options for a given source channel.
     *
     * @return array<int, array{label: string, options: array<int, array{value: string, label: string}>}>
     */
    public static function keywordGroupsFor(string $channel): array
    {
        return $channel === 'reddit'
            ? self::groups('scraper_options.intent_keywords')
            : self::groups('scraper_options.business_types');
    }

    /**
     * @return array<int, string>
     */
    public static function keywordValuesFor(string $channel): array
    {
        return self::flatValues(self::keywordGroupsFor($channel));
    }

    /**
     * @return array<int, array{label: string, options: array<int, array{value: string, label: string}>}>
     */
    private static function groups(string $configKey): array
    {
        $raw = config($configKey, []);

        return collect($raw)
            ->map(fn (array $options, string $groupLabel) => [
                'label' => $groupLabel,
                'options' => array_values($options),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{label: string, options: array<int, array{value: string, label: string}>}>  $groups
     * @return array<int, string>
     */
    private static function flatValues(array $groups): array
    {
        return collect($groups)
            ->flatMap(fn (array $group) => collect($group['options'])->pluck('value'))
            ->unique()
            ->values()
            ->all();
    }
}
