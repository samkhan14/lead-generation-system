# Phase 4 — Lead Intelligence Engine

## Status

**Implemented.**

## Goal

Upgrade the scoring system from basic v1 completeness rules to a multi-dimensional **Lead Intelligence Engine** that evaluates intent, opportunity, and authenticity.

## Intelligence dimensions

### Intent score (0–100)
Signals that the lead is actively interested:
- Intent keywords in notes (`interested`, `pricing`, `demo`, `urgent`, etc.)
- `metadata.intent_level` (`high` / `medium` / `low`) for scraper/API payloads
- High-intent sources (`api`, `scraper`)
- Recent `last_contacted_at` within 14 days

### Opportunity score (0–100)
Signals business value and reachability:
- Company present
- Decision-maker job title (CEO, Director, Manager, etc.)
- Full contact bundle (email + phone + website)
- Corporate email domain (non-free provider)
- Email domain matches website

### Authenticity score (0–100)
Signals the lead is genuine:
- Valid phone length (10+ digits)
- Corporate email domain
- Website provided
- Non-generic name
- Trusted source (`manual`, `api`)

Penalties:
- Free email without company context
- Generic names (`test`, `unknown`, etc.)

## Final score (v2)

Weighted composite stored as `lead_scores.score`:

| Dimension | Weight |
|-----------|--------|
| Intent | 35% |
| Opportunity | 35% |
| Authenticity | 30% |

Temperature thresholds unchanged:
- **HOT** ≥ 70
- **WARM** 40–69
- **COLD** &lt; 40

Configuration: `config/lead_scoring.php`

## Database

`lead_scores` columns added:
- `intent_score`
- `opportunity_score`
- `authenticity_score`
- `scoring_version` (`v2`)

Detailed breakdown + signals stored in `factors` JSON.

## UI

- Lead detail page shows **Intent / Opportunity / Authenticity** cards with detected signals
- Lead list shows compact `I/O/A` sub-scores under final score
- Score history shows intelligence sub-scores per snapshot

## Key files

- `config/lead_scoring.php`
- `app/Services/LeadScoringService.php`
- `resources/js/Components/Admin/IntelligenceScoreCard.vue`
- `resources/js/Pages/Leads/Show.vue`

## Exit criteria

- [x] Intent scoring
- [x] Opportunity scoring
- [x] Authenticity scoring
- [x] Weighted final score v2
- [x] Intelligence breakdown in UI
- [x] Tests for all dimensions

## Next phase preview

- Scraper metadata ingestion (`metadata.intent_level`, scrape confidence)
- Re-score on lead update
- ML / rules tuning dashboard
- Queue-based async scoring for bulk imports
