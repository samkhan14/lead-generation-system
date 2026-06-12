# Phase 5 — Google Maps Ingestion Pipeline (Revised)

## Status

**Implemented (v2).**

## Goal

Automated lead discovery via Google Maps with:
- CRM-driven scrape job UI (keyword, industry, country, city, area)
- Real-time job progress with completion popup notification
- Detailed per-run logs (started, success/duplicate/failed counts, scraper used)
- Playwright as **primary** scraper; Google Places API as **automatic fallback**

## Architecture

```
CRM User fills form  ──POST /scraper──►  ScraperController
                                              │
                                         Creates ScrapeJob (pending)
                                              │
                                   Dispatches ProcessScrapeJob (queue)
                                              │
                                    HTTP POST ► SRP Service :3100/run
                                              │
                               ┌─────────────┼──────────────┐
                               ▼             │              ▼
                          Playwright       (fails)    Places API
                       Google Maps       <3 results   (fallback)
                               │                          │
                               └──────────┬───────────────┘
                                          ▼
                              POST /api/leads/ingest  (per lead)
                              POST /api/scrape/jobs/{uuid}/log
                              POST /api/scrape/jobs/{uuid}/complete
                                          │
                                   CRM polls /scraper/{uuid}/status
                                          │
                              ┌─── job.status === 'completed' ──┐
                              ▼                                  ▼
                    ScrapeJob updated                  Popup notification shown
                    Run logs saved                      in Vue UI
```

## Database

### `scrape_jobs`
| Column | Type | Notes |
|---|---|---|
| `uuid` | string | Route key, sent to SRP service |
| `keyword` | string | Search term |
| `industry` | string? | Optional context |
| `country` | string | Required |
| `city` | string? | Optional |
| `area` | string? | Optional (neighbourhood) |
| `status` | enum | pending / running / completed / failed |
| `max_results` | int | Default 20, max 100 |
| `scraper_used` | string? | playwright / places_api |
| `started_at` | timestamp? | Set by SRP start callback |
| `completed_at` | timestamp? | Set by SRP complete callback |
| `total_found` | int | Businesses found |
| `created_count` | int | New leads created |
| `duplicate_count` | int | Skipped (already exist) |
| `failed_count` | int | Ingest errors |
| `error_message` | text? | Set on failure |
| `created_by` | FK users | Who triggered the job |

### `scrape_run_logs`
| Column | Type | Notes |
|---|---|---|
| `scrape_job_id` | FK | Cascade delete |
| `level` | string | info / success / warning / error |
| `message` | string | Human-readable log line |
| `context` | json? | lead_id, status, score, etc. |
| `created_at` | timestamp | Log timestamp |

## SRP Service (Node.js)

**Location:** `D:\sumaim\testprojects\srp-service`

### Setup
```bash
cd D:\sumaim\testprojects\srp-service
npm install
npm run install:browsers   # installs Playwright Chromium
cp .env.example .env       # fill in LARAVEL_URL and INGEST_API_TOKEN
npm run dev                # development
npm start                  # production
```

### Environment
```
PORT=3100
LARAVEL_URL=http://localhost
INGEST_API_TOKEN=your-token
GOOGLE_PLACES_API_KEY=your-key   # optional, only needed as fallback
PLAYWRIGHT_HEADLESS=true
SCRAPE_DELAY_MS=1200
```

### Scraping Strategy
1. **Playwright (primary)** — opens `maps.google.com`, searches, scrolls list panel, clicks each result, extracts: name, phone, website, address, rating, review count.
2. **Google Places API (fallback)** — triggered automatically if Playwright returns fewer than 3 results OR throws. Requires `GOOGLE_PLACES_API_KEY`.

### Callback Flow
| Endpoint | When |
|---|---|
| `POST /api/scrape/jobs/{uuid}/start` | Job picked up, starting scrape |
| `POST /api/scrape/jobs/{uuid}/log` | Per-result or milestone log entry |
| `POST /api/scrape/jobs/{uuid}/complete` | Final stats + status |
| `POST /api/leads/ingest` | Per-business ingest (existing API) |

All callbacks authenticated via `Authorization: Bearer {INGEST_API_TOKEN}`.

## Laravel API

### Ingest endpoint (existing)
```
POST /api/leads/ingest
Authorization: Bearer {INGEST_API_TOKEN}
```

### Scrape callbacks (new, Phase 5 v2)
```
POST /api/scrape/jobs/{uuid}/start
POST /api/scrape/jobs/{uuid}/log
POST /api/scrape/jobs/{uuid}/complete
```

## CRM UI

### `/scraper` — Scraper Index
- **Form:** keyword (required), industry (optional), country (required), city (optional), area (optional), max results
- **History table:** all jobs with status, stats, link to details
- **Polling:** auto-polls every 3s while any job is `running`
- **Popup notification:** `ScraperNotification.vue` overlay shows completion stats

### `/scraper/{uuid}` — Job Detail
- Status header + configuration summary
- Stats cards (total found / created / duplicates / failed)
- Full run log table with timestamp, level icon, message

## Permissions

| Permission | Role | Description |
|---|---|---|
| `scraper.view` | agent, super_admin | View job history and details |
| `scraper.run` | agent, super_admin | Create and trigger scrape jobs |

## Exit Criteria ✅

- [x] CRM screen with keyword, industry, country, city, area fields
- [x] Playwright primary scraper (Google Maps)
- [x] Google Places API automatic fallback
- [x] Laravel queue job dispatches to SRP service
- [x] SRP service HTTP server with `/run` endpoint
- [x] Callback flow: start → log (per result) → complete
- [x] Real-time status polling (3s interval)
- [x] Popup notification on job completion
- [x] Detailed scrape run logs with level, message, context
- [x] Job success/failed/duplicate ratio stats
- [x] Duration tracking (started_at → completed_at)
- [x] `scraper.view` and `scraper.run` permissions added
- [x] 15 feature tests covering full lifecycle
- [x] 71/71 total tests passing
