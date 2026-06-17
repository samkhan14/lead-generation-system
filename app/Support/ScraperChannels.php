<?php

namespace App\Support;

use App\Models\ScrapeJob;

/**
 * Single registry for scrape source channels (warm directories + hot intent sources).
 * UI, validation, and job dispatch all read from config/scraper.php via this class.
 */
class ScraperChannels
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('scraper.channels', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function enabled(): array
    {
        return array_filter(
            self::all(),
            fn (array $channel) => ($channel['enabled'] ?? true) === true,
        );
    }

    /**
     * @return array<int, string>
     */
    public static function enabledKeys(): array
    {
        return array_keys(self::enabled());
    }

    /**
     * @return array<int, string>
     */
    public static function runnableKeys(): array
    {
        return array_keys(array_filter(
            self::enabled(),
            fn (array $channel) => ($channel['implemented'] ?? true) === true,
        ));
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function isEnabled(string $key): bool
    {
        $channel = self::get($key);

        return $channel !== null && ($channel['enabled'] ?? true) === true;
    }

    public static function isImplemented(string $key): bool
    {
        $channel = self::get($key);

        return $channel !== null && ($channel['implemented'] ?? true) === true;
    }

    public static function requiresLocation(string $key): bool
    {
        return (bool) (self::get($key)['requires_location'] ?? false);
    }

    public static function tier(string $key): string
    {
        return (string) (self::get($key)['tier'] ?? 'warm');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forUi(): array
    {
        return collect(self::enabled())
            ->map(fn (array $channel, string $key) => self::uiItem($key, $channel))
            ->values()
            ->all();
    }

    /**
     * @return array{warm: array<int, array<string, mixed>>, hot: array<int, array<string, mixed>>}
     */
    public static function groupedForUi(): array
    {
        $warm = [];
        $hot = [];

        foreach (self::enabled() as $key => $channel) {
            $item = self::uiItem($key, $channel);

            if (($channel['tier'] ?? 'warm') === 'hot') {
                $hot[] = $item;
            } else {
                $warm[] = $item;
            }
        }

        return compact('warm', 'hot');
    }

    /**
     * Extra connector config merged into the SRP job payload (business rules stay in CRM).
     *
     * @return array<string, mixed>
     */
    public static function payloadFor(ScrapeJob $job): array
    {
        return match ($job->source_channel) {
            'reddit' => [
                'reddit' => [
                    'subreddits' => self::resolveRedditSubreddits($job),
                    'time_filter' => config('reddit.time_filter'),
                    'max_age_days' => config('reddit.max_age_days'),
                    'min_post_length' => config('reddit.min_post_length'),
                    'exclude_flairs' => config('reddit.exclude_flairs'),
                    'exclude_keywords' => config('reddit.exclude_keywords'),
                    'lead_kinds' => config('reddit.lead_kinds'),
                ],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $channel
     * @return array<string, mixed>
     */
    private static function uiItem(string $key, array $channel): array
    {
        return [
            'value' => $key,
            'label' => $channel['label'] ?? $key,
            'tier' => $channel['tier'] ?? 'warm',
            'keyword_label' => $channel['keyword_label'] ?? 'Keyword',
            'keyword_placeholder' => $channel['keyword_placeholder'] ?? '',
            'requires_location' => (bool) ($channel['requires_location'] ?? false),
            'industry_label' => $channel['industry_label'] ?? 'Industry',
            'industry_placeholder' => $channel['industry_placeholder'] ?? 'e.g. healthcare, F&B',
            'implemented' => (bool) ($channel['implemented'] ?? true),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function resolveRedditSubreddits(ScrapeJob $job): array
    {
        $country = strtolower(trim((string) $job->country));
        $countryPack = config("reddit.country_subreddits.{$country}", []);

        return array_values(array_unique(array_merge(
            is_array($countryPack) ? $countryPack : [],
            (array) config('reddit.default_subreddits'),
        )));
    }
}
