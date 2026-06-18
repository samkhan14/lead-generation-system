# Phase 6C — Reddit Lead Connector (Realtime)

## Status

**Implemented (v1).** Posts-only, realtime, intent-classified warm leads.

## Goal

Discover warm leads from Reddit — people actively asking for services — through the
same pipeline as Google Maps, with no separate leads table and no second ingest path.

## Architecture

```
CRM Scraper form (Lead Source = Reddit)
        │  source_channel = reddit, keyword = intent phrase, industry = subreddits
        ▼
scrape_jobs ──► ProcessScrapeJob ──► SRP /run (routes by source_channel)
                                          │
                                  connectors/reddit.ts (realtime)
                                          │  sort=new + recency cutoff (no old data)
                                  HttpClient → reddit search.json
                                          │  classify intent (config-driven)
                                          ▼
                              POST /api/leads/ingest (per warm post)
                              POST /api/scrape/jobs/{uuid}/{start,log,complete}
```

## Single source of truth

- **Business rules** (subreddits, time window, intent keywords, lead kinds) live in
  `config/reddit.php` and are passed to the SRP service in the job payload. The Node
  service is a pure execution engine.
- **Shared mechanics** are central in the SRP service: `core/http-client.ts`,
  `core/browser.ts`, `core/ingest-client.ts`, `core/callback-client.ts`.
- **One business-logic file per connector**: `connectors/google-maps.ts`,
  `connectors/reddit.ts`.

## Realtime guarantee

- Reddit search uses `sort=new` (never hot/top).
- `t={time_filter}` bounds the window (default `week`).
- A hard cutoff drops any post older than `max_age_days` (default 14).

## Lead kinds (warm only)

| lead_kind | intent_level | Example |
|---|---|---|
| service_request | high | "Looking for a developer to build me a website" |
| problem_post | medium | "Not getting any leads, my site is bad" |
| feedback_request | medium | "Roast my landing page" |
| local_recommendation | medium | "Any good agency recommendations?" |

Posts with no matching intent, `[For Hire]`/`[Hiring]` flairs (job seekers — handled
by the separate job product), NSFW, or very short bodies are skipped.

## Lead mapping

| Lead field | Reddit source |
|---|---|
| `source` | `reddit` |
| `first_name` | `u/{author}` |
| `website` | first external URL in post body (if any) |
| `notes` | title + excerpt + post URL |
| `metadata.reddit_post_id` | dedupe key |
| `metadata.{subreddit,post_url,author,upvotes,comment_count,posted_at,lead_kind,intent_level,intent_keywords_matched}` | |

## Scoring (config `lead_scoring.php`, version `v6`)

Reddit leans on **intent** (metadata `intent_level` + keywords + source) and an
opportunity profile keyed off `lead_kind` and missing website. `reddit` is a trusted
source; author + post traction add authenticity. A `service_request` post reliably
lands **warm**.

## Pitch

`reddit_outreach` pitch type (`config/lead_pitches.php`): reply helpfully in-thread
first, then follow up — surfaced on the lead detail page and in list pitch summaries.

## Dedupe

`LeadIngestionService::findDuplicate()` checks `metadata.reddit_post_id` before
email/phone/website, so re-running a job marks duplicates instead of creating rows.

## Running a job

1. Ensure the SRP service is running (`npm run dev`) and the queue worker is up
   (`php artisan queue:work`).
2. On `/scraper`, set **Lead Source = Reddit**, enter an intent keyword (e.g.
   "need a website"), optionally list subreddits in the Industry field, submit.
3. Reddit and Google Maps jobs run concurrently and both appear in history with live
   progress.

## Environment (SRP service)

```
REDDIT_USER_AGENT=srp-service/1.0 (lead research; contact: you@example.com)
REDDIT_MIN_DELAY_MS=2500
REDDIT_TIME_FILTER=week   # optional (CRM also sends this)
REDDIT_MAX_AGE_DAYS=14    # optional
```

### Realtime on server/datacenter IPs (recommended)

Public `reddit.com` JSON is often WAF-blocked (403) on server IPs. PullPush
(free fallback) can lag **months** behind — posts may fetch successfully but all
fail the 14-day freshness guard (`Found: 0`).

Use **free Reddit OAuth** (~100 req/min, no paid tier needed):

1. Create a **script** app at https://www.reddit.com/prefs/apps
2. Set on the SRP service `.env`:

```
REDDIT_CLIENT_ID=your_client_id
REDDIT_CLIENT_SECRET=your_secret
REDDIT_USERNAME=your_reddit_username
REDDIT_PASSWORD=your_reddit_password
REDDIT_USER_AGENT=srp-service/1.0 (by u/your_username)
```

3. Restart the SRP service and re-run the job. Logs should show `via reddit-oauth`.

When zero leads are ingested, job logs now report skip breakdown (stale / excluded /
no intent) and the newest post date seen.

## Lead detail UI

The lead show page renders a **Reddit context** card (source = `reddit`): subreddit
link, author, posted time, upvotes/comments, matched intent signals, the original
post title, and a "View original post" button. The suggested reply appears in the
existing "What to pitch" card via the `reddit_outreach` pitch.

## Leads index filters

`App\Support\LeadQueryFilters` adds Reddit-aware filters, shown on `/leads` when the
source is Reddit or "all":

- **Source** → Reddit
- **Subreddit** (distinct `metadata.subreddit`)
- **Reddit intent** (`metadata.lead_kind`)
- **Posted** within today / week / month (`metadata.posted_at`, ISO-8601 compare)

Example: "Reddit service requests this week" = source `reddit` + intent
`service_request` + posted `7`.

## Per-country subreddit packs

`config/reddit.php` → `country_subreddits` maps a country to local communities.
`ProcessScrapeJob::resolveSubreddits()` merges the country pack with the global
defaults. The Industry field still overrides everything per job.

## Scheduled watches (passive feed)

A `ScrapeJob` with `is_watch = true` is a template. `php artisan scrape:watch`
(scheduled hourly, see `routes/console.php`) re-dispatches a fresh run for each
watch whose `watch_interval_hours` (default `config('reddit.watch_interval_hours')`)
has elapsed. Dedupe by `reddit_post_id` means only genuinely new posts become leads,
so this is a safe passive warm-lead feed. To run the scheduler in production:
`php artisan schedule:work` (or a cron entry calling `schedule:run`).

## Playwright fallback (off by default)

`connectors/reddit.ts` tries reddit.com JSON → PullPush → (only if
`REDDIT_USE_PLAYWRIGHT=true`) a real-browser fetch via the central `BrowserService`.
Keep it disabled unless the HTTP paths fail in your environment.

## Tests

- `tests/Feature/RedditLeadTest.php`: ingest creates a warm reddit lead, dedupe by
  `reddit_post_id`, no business name required, service-request scoring + pitch, and
  subreddit/lead_kind/recency filtering.
- `tests/Feature/ScrapeWatchTest.php`: due watch re-dispatches, in-interval watch is
  skipped, non-watch jobs ignored.
