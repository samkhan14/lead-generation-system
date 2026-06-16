<?php

use App\Models\Lead;
use App\Services\LeadPitchService;
use App\Services\LeadScoringService;
use App\Support\LeadQueryFilters;

use function Pest\Laravel\postJson;

function redditPayload(array $overrides = []): array
{
    return array_merge([
        'source' => 'reddit',
        'first_name' => 'u/startup_owner',
        'last_name' => '(Reddit)',
        'website' => null,
        'notes' => "Looking for a developer to build me a website\n\nI run a small bakery and need a site to take orders. Can someone recommend an agency?\n\nhttps://www.reddit.com/r/smallbusiness/comments/abc123/",
        'scrape_keyword' => 'need a website',
        'scrape_country' => 'Pakistan',
        'metadata' => [
            'reddit_post_id' => 'abc123',
            'subreddit' => 'smallbusiness',
            'post_url' => 'https://www.reddit.com/r/smallbusiness/comments/abc123/',
            'author' => 'startup_owner',
            'post_title' => 'Looking for a developer to build me a website',
            'upvotes' => 12,
            'comment_count' => 6,
            'posted_at' => now()->toIso8601String(),
            'lead_kind' => 'service_request',
            'intent_level' => 'high',
            'intent_keywords_matched' => ['looking for', 'build me'],
        ],
    ], $overrides);
}

function redditIngestHeaders(): array
{
    return ['Authorization' => 'Bearer '.config('ingest.token')];
}

test('reddit post ingest creates a warm lead with reddit metadata', function () {
    $response = postJson(route('api.leads.ingest'), redditPayload(), redditIngestHeaders());

    $response->assertCreated();

    $lead = Lead::query()->where('metadata->reddit_post_id', 'abc123')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->source)->toBe('reddit')
        ->and($lead->first_name)->toBe('u/startup_owner')
        ->and(data_get($lead->metadata, 'subreddit'))->toBe('smallbusiness')
        ->and(data_get($lead->metadata, 'lead_kind'))->toBe('service_request')
        ->and($lead->latestScore->temperature)->toBeIn(['warm', 'hot'])
        ->and($lead->latestScore->scoring_version)->toBe('v6');
});

test('reddit lead is deduplicated by reddit_post_id', function () {
    postJson(route('api.leads.ingest'), redditPayload(), redditIngestHeaders())->assertCreated();

    postJson(route('api.leads.ingest'), redditPayload([
        'notes' => 'Same post re-scraped on a later run',
    ]), redditIngestHeaders())->assertStatus(409);

    expect(Lead::query()->where('metadata->reddit_post_id', 'abc123')->count())->toBe(1);
});

test('reddit ingest does not require business name or company', function () {
    postJson(route('api.leads.ingest'), redditPayload([
        'metadata' => array_merge(redditPayload()['metadata'], ['reddit_post_id' => 'xyz789']),
    ]), redditIngestHeaders())->assertCreated();
});

test('service request reddit lead scores warm with reddit outreach pitch', function () {
    $lead = Lead::query()->create([
        'first_name' => 'u/cafe_owner',
        'last_name' => '(Reddit)',
        'source' => 'reddit',
        'notes' => 'Looking for an agency to build me a website, need a developer asap',
        'metadata' => [
            'reddit_post_id' => 'pitch1',
            'author' => 'cafe_owner',
            'subreddit' => 'smallbusiness',
            'lead_kind' => 'service_request',
            'intent_level' => 'high',
            'upvotes' => 9,
            'comment_count' => 5,
        ],
    ]);

    $score = app(LeadScoringService::class)->score($lead);

    expect($score->temperature)->toBeIn(['warm', 'hot'])
        ->and($score->score)->toBeGreaterThanOrEqual(40);

    $pitches = collect(app(LeadPitchService::class)->recommendations($lead->fresh()))
        ->pluck('service')
        ->all();

    expect($pitches)->toContain('Helpful Reddit reply, then direct follow-up');
});

function redditLead(array $metadata): Lead
{
    return Lead::query()->create([
        'first_name' => 'u/poster',
        'last_name' => '(Reddit)',
        'source' => 'reddit',
        'notes' => 'looking for a website',
        'metadata' => $metadata,
    ]);
}

test('reddit leads filter by subreddit, lead_kind and recency', function () {
    $fresh = redditLead([
        'reddit_post_id' => 'p1',
        'subreddit' => 'smallbusiness',
        'lead_kind' => 'service_request',
        'posted_at' => now()->subDay()->toIso8601String(),
    ]);

    $other = redditLead([
        'reddit_post_id' => 'p2',
        'subreddit' => 'webdev',
        'lead_kind' => 'problem_post',
        'posted_at' => now()->subDays(40)->toIso8601String(),
    ]);

    $bySubreddit = LeadQueryFilters::apply(Lead::query(), ['subreddit' => 'smallbusiness'])->pluck('id');
    expect($bySubreddit)->toContain($fresh->id)->not->toContain($other->id);

    $byKind = LeadQueryFilters::apply(Lead::query(), ['lead_kind' => 'problem_post'])->pluck('id');
    expect($byKind)->toContain($other->id)->not->toContain($fresh->id);

    $byRecency = LeadQueryFilters::apply(Lead::query(), ['posted_within' => 7])->pluck('id');
    expect($byRecency)->toContain($fresh->id)->not->toContain($other->id);
});
