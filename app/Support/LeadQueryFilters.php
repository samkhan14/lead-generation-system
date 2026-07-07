<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\LeadScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadQueryFilters
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        $query = static::applyTemperature($query, $filters['temperature'] ?? null);
        $query = static::applySearch($query, $filters['q'] ?? null);
        $query = static::applySource($query, $filters['source'] ?? null);
        $query = static::applyCountry($query, $filters['country'] ?? null);
        $query = static::applyCity($query, $filters['city'] ?? null);
        $query = static::applyArea($query, $filters['area'] ?? null);
        $query = static::applyKeyword($query, $filters['keyword'] ?? null);
        $query = static::applyHasWebsite($query, $filters['has_website'] ?? null);
        $query = static::applyPitchType($query, $filters['pitch_type'] ?? null);
        $query = static::applySubreddit($query, $filters['subreddit'] ?? null);
        $query = static::applyLeadKind($query, $filters['lead_kind'] ?? null);
        $query = static::applyPostedWithin($query, $filters['posted_within'] ?? null);
        $query = static::applySort($query, $filters['sort'] ?? 'created_desc');

        return $query;
    }

    public static function applyTemperature(Builder $query, ?string $temperature): Builder
    {
        if (! in_array($temperature, ['hot', 'warm', 'cold'], true)) {
            return $query;
        }

        return $query->withTemperature($temperature);
    }

    public static function applySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('website', 'like', "%{$search}%")
                ->orWhere('source', 'like', "%{$search}%")
                ->orWhere('metadata->address', 'like', "%{$search}%")
                ->orWhere('metadata->scrape_keyword', 'like', "%{$search}%")
                ->orWhere('metadata->scrape_city', 'like', "%{$search}%")
                ->orWhere('metadata->scrape_area', 'like', "%{$search}%")
                ->orWhere('metadata->scrape_country', 'like', "%{$search}%");
        });
    }

    public static function applySource(Builder $query, ?string $source): Builder
    {
        if (! in_array($source, ['google_maps', 'reddit', 'manual', 'api', 'import', 'scraper'], true)) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public static function applySubreddit(Builder $query, ?string $subreddit): Builder
    {
        $subreddit = ltrim(trim((string) $subreddit), 'r/');

        if ($subreddit === '') {
            return $query;
        }

        return $query->where('metadata->subreddit', $subreddit);
    }

    public static function applyLeadKind(Builder $query, ?string $leadKind): Builder
    {
        if (! array_key_exists((string) $leadKind, config('reddit.lead_kinds', []))) {
            return $query;
        }

        return $query->where('metadata->lead_kind', $leadKind);
    }

    public static function applyPostedWithin(Builder $query, int|string|null $days): Builder
    {
        $days = (int) $days;

        if (! in_array($days, [1, 7, 30], true)) {
            return $query;
        }

        $cutoff = now()->subDays($days)->toIso8601String();

        return $query->where('metadata->posted_at', '>=', $cutoff);
    }

    public static function applyCountry(Builder $query, ?string $country): Builder
    {
        $country = trim((string) $country);

        if ($country === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($country): void {
            $query
                ->where('metadata->scrape_country', $country)
                ->orWhere('metadata->address', 'like', "%{$country}%")
                ->orWhere('metadata->scrape_city', 'like', "%{$country}%");
        });
    }

    public static function applyCity(Builder $query, ?string $city): Builder
    {
        $city = trim((string) $city);

        if ($city === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($city): void {
            $query
                ->where('metadata->scrape_city', 'like', "%{$city}%")
                ->orWhere('metadata->address', 'like', "%{$city}%");
        });
    }

    public static function applyArea(Builder $query, ?string $area): Builder
    {
        $area = trim((string) $area);

        if ($area === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($area): void {
            $query
                ->where('metadata->scrape_area', 'like', "%{$area}%")
                ->orWhere('metadata->address', 'like', "%{$area}%");
        });
    }

    public static function applyKeyword(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where('metadata->scrape_keyword', 'like', "%{$keyword}%");
    }

    public static function applyHasWebsite(Builder $query, ?string $hasWebsite): Builder
    {
        if ($hasWebsite === 'yes') {
            return $query->whereNotNull('website')->where('website', '!=', '');
        }

        if ($hasWebsite === 'no') {
            return $query->where(function (Builder $query): void {
                $query->whereNull('website')->orWhere('website', '=', '');
            });
        }

        return $query;
    }

    public static function applyPitchType(Builder $query, ?string $pitchType): Builder
    {
        if (! array_key_exists($pitchType ?? '', config('lead_pitches.types', []))) {
            return $query;
        }

        $reviewThreshold = (int) config('lead_pitches.reviews_growth_threshold', 20);
        $ratingThreshold = (float) config('lead_pitches.reputation_rating_threshold', 4.0);
        $localSources = (array) config('lead_pitches.local_sources', []);
        $agencySources = (array) config('lead_pitches.agency_sources', []);

        return match ($pitchType) {
            'website_build' => $query->where(function (Builder $query): void {
                $query->whereNull('website')->orWhere('website', '=', '');
            }),
            'website_audit' => $query->whereNotNull('website')->where('website', '!=', ''),
            'reviews_growth' => static::applyNumericMetadataFilter($query, 'review_count', '<', $reviewThreshold),
            'reputation_repair' => static::applyNumericMetadataFilter($query, 'rating', '<', $ratingThreshold),
            'gbp_optimization' => $query->where('source', 'google_maps'),
            'phone_outreach' => $query
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->where(function (Builder $query): void {
                    $query->whereNull('email')->orWhere('email', '=', '');
                }),
            'reddit_outreach' => $query->where('source', 'reddit'),
            'local_seo' => $query
                ->whereIn('source', $localSources)
                ->whereNotNull('website')
                ->where('website', '!=', ''),
            'seo_growth' => $query->whereIn('source', $agencySources),
            'google_ads' => $query
                ->whereNotNull('website')->where('website', '!=', '')
                ->whereNotNull('phone')->where('phone', '!=', ''),
            'social_media_growth' => static::applyNumericMetadataFilter(
                $query->whereIn('source', $localSources),
                'review_count',
                '<',
                $reviewThreshold,
            ),
            'content_marketing' => $query
                ->whereIn('source', $agencySources)
                ->whereNotNull('website')
                ->where('website', '!=', ''),
            default => $query,
        };
    }

    public static function applySort(Builder $query, ?string $sort): Builder
    {
        $sort = $sort ?: 'created_desc';

        return match ($sort) {
            'created_asc' => $query->orderBy('leads.created_at'),
            'score_desc' => $query->orderByDesc(
                LeadScore::select('score')
                    ->whereColumn('lead_id', 'leads.id')
                    ->latest('calculated_at')
                    ->limit(1),
            ),
            'score_asc' => $query->orderBy(
                LeadScore::select('score')
                    ->whereColumn('lead_id', 'leads.id')
                    ->latest('calculated_at')
                    ->limit(1),
            ),
            'rating_desc' => static::applyMetadataSort($query, 'rating', 'desc'),
            'rating_asc' => static::applyMetadataSort($query, 'rating', 'asc'),
            'company_asc' => $query->orderBy('company')->orderByDesc('leads.created_at'),
            'company_desc' => $query->orderByDesc('company')->orderByDesc('leads.created_at'),
            default => $query->latest('leads.created_at'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function distinctMetadataValues(string $key): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return Lead::query()
                ->whereNotNull("metadata->{$key}")
                ->get(['metadata'])
                ->pluck("metadata.{$key}")
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return Lead::query()
            ->whereNotNull("metadata->{$key}")
            ->selectRaw('DISTINCT '.static::jsonExtract('metadata', "$.{$key}").' as value')
            ->orderBy('value')
            ->pluck('value')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();
    }

    private static function applyNumericMetadataFilter(
        Builder $query,
        string $key,
        string $operator,
        int|float $value,
    ): Builder {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->where("metadata->{$key}", $operator, $value);
        }

        $cast = $key === 'rating' ? 'DECIMAL(3,1)' : 'UNSIGNED';

        return $query->whereRaw(
            'CAST('.static::jsonExtract('metadata', "$.{$key}")." AS {$cast}) {$operator} ?",
            [$value],
        );
    }

    private static function applyMetadataSort(Builder $query, string $key, string $direction): Builder
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $direction === 'desc'
                ? $query->orderByDesc("metadata->{$key}")
                : $query->orderBy("metadata->{$key}");
        }

        $expression = DB::raw('CAST('.static::jsonExtract('leads.metadata', "$.{$key}").' AS DECIMAL(3,1))');

        return $direction === 'desc'
            ? $query->orderByDesc($expression)
            : $query->orderBy($expression);
    }

    private static function jsonExtract(string $column, string $path): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "json_extract({$column}, '{$path}')",
            default => "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}'))",
        };
    }
}
