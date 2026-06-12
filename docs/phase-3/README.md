# Phase 3 — Lead Core Module

## Status

**Implemented.**

## Goal

First real product milestone: manual lead management with automatic scoring, deduplication, and HOT/WARM filtering.

## Features

### Create lead
- Manual form with contact fields including **website**
- Score calculated automatically on save (no manual score input)
- Dedup check on email, phone, and website

### List leads
- Paginated table with score, grade, and temperature badge
- Contact column shows email + phone

### HOT / WARM filter
- Filter tabs on lead index: **All**, **HOT**, **WARM**
- Based on latest score temperature:
  - **HOT**: score ≥ 70
  - **WARM**: score 40–69
  - **COLD**: score &lt; 40 (not filtered in UI, shown on detail)

### Scoring engine (v1)
`App\Services\LeadScoringService` calculates score from:

| Factor | Max points |
|--------|------------|
| Completeness (email, phone, website, company, job title) | 40 |
| Source quality (api, scraper, import, manual) | 30 |
| Contact richness (email+phone, website, notes) | 30 |

Stored in `lead_scores` with `score`, `score_grade`, `temperature`, and `factors` breakdown.

### Dedup logic
`App\Support\LeadIdentifiers` normalizes:
- **Email** → lowercase trim
- **Phone** → digits only
- **Website** → strip protocol/www, lowercase

`Lead::findDuplicate()` blocks create if any normalized identifier matches an existing lead.

Duplicate errors show a link to the existing lead.

## Key files

- `app/Services/LeadScoringService.php`
- `app/Support/LeadIdentifiers.php`
- `app/Models/Lead.php` — normalized fields, `findDuplicate()`, `withTemperature()` scope
- `app/Http/Controllers/LeadController.php`
- `resources/js/Pages/Leads/*`
- `resources/js/Components/Admin/TemperatureBadge.vue`

## Exit criteria

- [x] Create lead with auto-scoring
- [x] List leads with temperature display
- [x] HOT/WARM filters working
- [x] Dedup on email, phone, website
- [x] Tests for scoring, dedup, and filters

## Next phase preview

- Scraping ingestion pipeline
- Bulk import
- Lead edit / reassignment
- Re-score on demand or on field change
