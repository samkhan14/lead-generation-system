# AI Workforce platform — testapp main chat

> **Composer ID:** `6ddbf6fe-1168-4657-a9ff-fc909f3fffef`  
> **Last exported:** 2026-07-01 00:36:28

---

## User

write a summary of this project what is the goal of this project, what are the features inside it, how its working, what are the results coming, what is the structure and architecture. mention each and everything then we will continue this project on next level just share the summary here

---

## Assistant

# Project Summary — Lead Intelligence CRM

## 1. Project Goal

This is a **Laravel + Inertia + Vue 3 CRM** focused on **Lead Intelligence**: discovering business leads from multiple sources, scoring them automatically, deduplicating them, and giving sales agents actionable insights (temperature, pitch recommendations, verification status).

The long-term vision is a production-ready business system that starts with leads and later expands into **deals, clients, and projects**. Right now it is a working internal tool for **automated lead discovery + smart prioritization**, not a full CRM.

**Core problem it solves:** Raw scraped data is noisy. This system turns directory listings and social intent posts into ranked, deduplicated, pitch-ready leads with a single unified pipeline.

---

## 2. Current Status

| Phase | Name | Status |
|-------|------|--------|
| 0 | Documentation & baseline | Done |
| 1 | Core database (leads, scores, roles) | Done |
| 2 | Auth + admin foundation | Done |
| 3 | Lead core module (manual CRUD, scoring, dedup) | Done |
| 4 | Lead Intelligence Engine (I/O/A scoring) | Done |
| 5 | Google Maps ingestion pipeline | Done |
| 6 | Multi-source scrapers + Reddit connector | Done |
| — | Lead verification, website analysis, pitch engine | Done (beyond original phase docs) |

**Test suite:** 134 tests, all passing (Pest).

---

## 3. Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Inertia.js + Vue 3 + Tailwind CSS + Vite |
| Auth | Laravel Breeze |
| Authorization | Spatie Laravel Permission |
| Queue | Laravel queues (jobs for scraping + verification) |
| Scraper service | External Node.js **SRP Service** (Playwright + HTTP connectors) |
| Tests | Pest (SQLite in tests) |

---

## 4. Architecture Overview

The system follows a **two-service architecture**:

```mermaid
flowchart TB
    subgraph CRM["Laravel CRM (this repo)"]
        UI[Inertia Vue UI]
        Controllers[Controllers]
        Services[Domain Services]
        Queue[Laravel Queue]
        DB[(MySQL/SQLite)]
    end

    subgraph SRP["SRP Service (Node.js, external)"]
        Connectors[Source Connectors]
        Playwright[Playwright Browser]
        HTTP[HTTP Clients]
    end

    UI --> Controllers --> Services --> DB
    Controllers --> Queue
    Queue -->|POST /run| SRP
    SRP -->|POST /api/leads/ingest| Services
    SRP -->|POST /api/scrape/jobs/{uuid}/*| Controllers
```

### Design principles (enforced in code)

1. **One pipeline, many sources** — Every lead (manual, API, Google Maps, Yelp, Reddit, etc.) flows through the same path:
   `POST /api/leads/ingest` → `LeadIngestionService` → dedupe → `LeadScoringService` → optional `VerifyLeadJob`

2. **Config-driven business rules** — Scoring, pitches, Reddit intent, scraper channels, countries, and quality rules live in `config/*.php`, not scattered in services.

3. **Metadata, not columns** — Source-specific fields (rating, subreddit, post URL, place IDs) live in `leads.metadata` JSON and are filtered via `LeadQueryFilters`.

4. **Thin controllers, fat services** — Controllers coordinate; business logic lives in dedicated services.

---

## 5. How It Works (End-to-End Flow)

### A. Manual lead creation
1. Agent fills form at `/leads/create`
2. `LeadIngestionService` normalizes identifiers (email, phone, website)
3. Dedup check blocks duplicates
4. Lead saved → `LeadScoringService` creates a `lead_scores` snapshot
5. `VerifyLeadJob` queued if verification is applicable

### B. Automated scraping
1. Agent submits scrape job at `/scraper` (keyword, country, city, source channel)
2. `ScrapeJob` record created (status: pending)
3. `ProcessScrapeJob` dispatched to Laravel queue
4. Queue worker POSTs to SRP Service `/run` with job payload + callback URLs
5. SRP connector runs (Playwright for Google Maps, HTTP for Yelp/OSM/Bing/Hotfrog/Reddit)
6. For each result, SRP calls `POST /api/leads/ingest` (Bearer token auth)
7. SRP sends progress callbacks: `start` → `log` (per result) → `complete`
8. CRM UI polls `/scraper/{uuid}/status` every 3s; popup on completion

### C. Scoring (Lead Intelligence Engine v6)
Each lead gets three sub-scores, combined into a weighted final score:

| Dimension | Weight | What it measures |
|-----------|--------|------------------|
| **Intent** | 25% | Keywords, source type, Reddit intent level, recent contact |
| **Opportunity** | 45% | Company, decision-maker title, contact completeness, no-website signals, reviews/ratings |
| **Authenticity** | 30% | Valid phone, corporate email, website, trusted source |

**Temperature thresholds:**
- **HOT** ≥ 70
- **WARM** 40–69
- **COLD** < 40

Config: `config/lead_scoring.php` (version `v6`)

### D. Verification pipeline (automatic)
After ingest, `VerifyLeadJob` runs layers from `config/lead_quality.php`:
1. Google Places API enrichment (fill missing phone/website/address)
2. Playwright Google search (find website if missing)
3. HTTP website check
4. Website analysis via SRP `/analyze-website` (CMS detection, revamp signals)

Results stored in `metadata.verification`; `verified_at` set on success.

### E. Pitch recommendations
`LeadPitchService` reads `config/lead_pitches.php` and suggests outreach types:
- Website build (no website)
- Website audit (has website)
- Reviews growth (low review count)
- Reputation repair (rating < 4.0)
- Google Business Profile optimization
- Phone outreach (phone but no email)
- Reddit reply outreach

Shown on lead detail page as "What to pitch."

### F. Scheduled automation
| Command | Schedule | Purpose |
|---------|----------|---------|
| `scrape:watch` | Hourly | Re-run passive Reddit/directory watch jobs |
| `scrape:fail-stale` | Every 15 min | Mark stuck running jobs as failed |
| Verification retry | Daily 02:30 | Re-dispatch pending verifications |

---

## 6. Features (Complete Inventory)

### Authentication & Admin
- Login / logout / password reset (Breeze)
- Public registration **disabled** (internal-only)
- Roles: `super_admin`, `agent`
- Permissions: `leads.*`, `scraper.view`, `scraper.run`
- Super admin bypasses all permission checks
- Admin layout with sidebar, responsive mobile menu
- Dashboard with lead stats + recent leads

### Lead Management (`/leads`)
- Create, list, show, delete leads
- Auto-scoring on create (no manual score input)
- Dedup on email, phone, website + source-specific metadata keys
- Temperature badges (HOT/WARM/COLD)
- Intelligence sub-scores (I/O/A) on list and detail
- Score history snapshots
- Manual re-verify button
- Verification badge + website analysis panel

**Filters on index:**
- Temperature, search, source, country/city/area/keyword
- Has website, pitch type
- Reddit: subreddit, lead kind, posted within
- Sort + pagination

### Scraper (`/scraper`)
- Multi-source job form with channel picker
- Job history table with status/stats
- Real-time polling + completion popup notification
- Job detail page with full run logs
- Watch mode for passive recurring feeds

### Lead Sources (Scraper Channels)

| Channel | Tier | Status | Dedupe Key |
|---------|------|--------|------------|
| Google Maps | Warm | Enabled | `google_place_id` |
| Yelp | Warm | Enabled | `yelp_business_id` |
| OpenStreetMap | Warm | Enabled | `osm_id` |
| Bing Places | Warm | Enabled | `bing_entity_id` |
| Hotfrog | Warm | Enabled | `hotfrog_business_id` |
| Reddit | Hot | Disabled in UI (implemented) | `reddit_post_id` |
| Facebook | Hot | Not implemented | — |
| Instagram | Hot | Not implemented | — |

### API Endpoints
| Endpoint | Auth | Purpose |
|----------|------|---------|
| `POST /api/leads/ingest` | Bearer token | Universal lead ingest |
| `POST /api/scrape/jobs/{uuid}/start` | Bearer token | Job started callback |
| `POST /api/scrape/jobs/{uuid}/log` | Bearer token | Per-result log |
| **POST /api/scrape/jobs/{uuid}/complete` | Bearer token | Final stats |

---

## 7. Database Schema

| Table | Purpose |
|-------|---------|
| `users` | Agents and admins |
| Spatie permission tables | Roles & permissions |
| `leads` | Core lead records (contact fields, source, metadata JSON, verified_at) |
| `lead_scores` | Score history (final + intent/opportunity/authenticity + factors JSON) |
| `scrape_jobs` | Scrape job config, status, stats, source_channel, watch fields |
| `scrape_run_logs` | Per-job log lines with level/message/context |
| Laravel defaults | sessions, cache, jobs, failed_jobs |

Key lead fields: normalized email/phone/website columns for fast dedup; `metadata` JSON for everything source-specific.

---

## 8. Project Structure

```
testapp/
├── app/
│   ├── Console/Commands/       # scrape:watch, scrape:fail-stale
│   ├── DataTransferObjects/    # LeadIngestResult
│   ├── Enums/                  # ScrapeJobStatus
│   ├── Http/
│   │   ├── Controllers/        # Lead, Scraper, Dashboard, API callbacks
│   │   ├── Middleware/         # VerifyIngestToken, HandleInertiaRequests
│   │   └── Requests/           # Form validation
│   ├── Jobs/                   # ProcessScrapeJob, VerifyLeadJob
│   ├── Models/                 # Lead, LeadScore, ScrapeJob, ScrapeRunLog, User
│   ├── Services/               # Core business logic
│   │   ├── LeadIngestionService
│   │   ├── LeadScoringService
│   │   ├── LeadPitchService
│   │   ├── LeadVerificationService
│   │   ├── LeadEnrichmentService
│   │   └── LeadWebsiteAnalysisService
│   └── Support/                # Query filters, identifiers, channel helpers
├── config/
│   ├── lead_scoring.php        # Scoring rules (single source of truth)
│   ├── lead_pitches.php        # Pitch types and openers
│   ├── lead_quality.php        # Verification layers
│   ├── scraper.php             # Channels + SRP service config
│   ├── reddit.php              # Reddit intent, subreddits, watches
│   ├── countries.php           # Country list for forms/filters
│   └── ingest.php              # API token config
├── resources/js/
│   ├── Layouts/AdminLayout.vue
│   ├── Pages/
│   │   ├── Dashboard.vue
│   │   ├── Leads/ (Index, Create, Show)
│   │   └── Scraper/ (Index, Show)
│   └── Components/Admin/       # TemperatureBadge, IntelligenceScoreCard, etc.
├── routes/
│   ├── web.php                 # Authenticated CRM routes
│   ├── api.php                 # Ingest + scrape callbacks
│   └── console.php             # Scheduler
├── tests/Feature/              # 134 tests across all modules
└── docs/                       # Phase 0–6 documentation
```

**External dependency:** SRP Service at `D:\sumaim\testprojects\srp-service` (Node.js) — connectors for each source, Playwright browser automation, ingest/callback clients.

---

## 9. Results & Outputs

When the system runs successfully, you get:

1. **Scored leads** in the CRM with temperature (HOT/WARM/COLD) and I/O/A breakdown
2. **Deduped records** — re-runs mark duplicates instead of creating duplicates
3. **Pitch recommendations** — actionable outreach suggestions per lead
4. **Verification status** — enriched contact data + website analysis
5. **Scrape job reports** — total found / created / duplicate / failed counts + detailed logs
6. **Filterable lead lists** — e.g. "Google Maps leads in Lahore with no website, HOT temperature, website_build pitch"
7. **Passive feeds** — watch jobs that continuously ingest new Reddit posts or directory listings

**Seed users for testing:**
| Email | Password | Role |
|-------|----------|------|
| `superadmin@example.com` | `password` | Super Admin |
| `agent@example.com` | `password` | Agent |

---

## 10. What Is NOT Built Yet (Next-Level Opportunities)

These are explicitly out of scope or planned for future phases:

| Area | Status |
|------|--------|
| Deals module | Not started |
| Clients module | Not started |
| Projects module | Not started |
| Lead edit / reassignment UI | Not built |
| User management (admin CRUD) | Not built |
| Bulk import | Not built |
| Facebook / Instagram connectors | Configured but not implemented |
| Reddit in UI | Implemented but **disabled** in config |
| ML / rules tuning dashboard | Not built |
| Production hardening (observability, backups, deployment) | Documented but not done |
| Public registration | Intentionally disabled |

---

## 11. Key Services Map

```
LeadIngestionService     → normalize, dedupe, create, trigger scoring + verification
LeadScoringService       → intent + opportunity + authenticity → final score
LeadPitchService         → match pitch types from config
LeadVerificationService  → multi-layer verification (Places, Playwright, HTTP, analysis)
LeadEnrichmentService    → Google Places lookup
LeadWebsiteAnalysisService → CMS/revamp detection via SRP
ProcessScrapeJob         → dispatch to SRP service
VerifyLeadJob            → async verification queue worker
LeadQueryFilters         → all lead index filtering (SQLite + MySQL safe)
LeadIdentifiers          → email/phone/website normalization
ScraperChannels          → channel registry from config
```

---

## 12. Running the System

Requires three processes:

1. **Laravel app** — `php artisan serve` or Herd/Valet
2. **Queue worker** — `php artisan queue:work`
3. **SRP Service** — `npm run dev` in the srp-service repo

Optional: `php artisan schedule:work` for watches and stale job cleanup.

Environment keys: `SRP_SERVICE_URL`, `INGEST_API_TOKEN`, `GOOGLE_PLACES_API_KEY`, Reddit OAuth credentials (for Reddit on server IPs).

---

This is a solid **Phase 1–6 Lead Intelligence platform** with a unified ingest pipeline, multi-source scraping, intelligent scoring, pitch recommendations, and verification — ready to be taken to the next level (deals/clients, lead assignment workflows, more connectors, production deployment, or enabling Reddit/Facebook in production).

Tell me which direction you want to take next and we can plan the next phase.

[REDACTED]

---

## User

CONTINUE EXISTING PROJECT (PHASE 7 : AI WORKFORCE PLATFORM)
You are continuing an EXISTING production-grade Laravel application.

DO NOT redesign the existing architecture.

You must EXTEND it while preserving all current modules.

=========================================================
CURRENT PROJECT
=========================================================

The project is NOT a new CRM.

It is an existing Lead Intelligence CRM built with:

- Laravel 13
- PHP 8.3+
- Inertia.js
- Vue 3
- Tailwind CSS
- MySQL
- Laravel Queues
- Pest
- Spatie Permission
- External SRP Service (Node.js)

Current features are already completed:

✓ Lead generation
✓ Google Maps
✓ Bing
✓ Multi-source ingestion
✓ Lead verification
✓ Website analysis
✓ Lead scoring
✓ Deduplication
✓ Pitch recommendation
✓ Queue architecture
✓ Tests

The current system is stable.

DO NOT BREAK EXISTING FEATURES.

=========================================================
NEW PROJECT DIRECTION
=========================================================

We are now entering a completely new bounded context.

This project is evolving from

Lead Intelligence CRM

into

AI Workforce CRM

The Lead Intelligence Engine remains untouched.

The new AI modules consume qualified leads generated by the existing system.

Existing Flow

Lead Sources
↓

SRP Service
↓

Lead Intelligence
↓

Verification
↓

Scoring

New Flow

Qualified Lead
↓

AI Employee
↓

Conversation
↓

Meeting
↓

Deal
↓

Project

=========================================================
GOAL
=========================================================

We are building an AI Workforce Platform.

The AI employees behave like real employees.

Every employee has:

- role
- responsibilities
- prompts
- memory
- permissions
- actions
- tools
- context
- provider
- model
- logs
- performance
- costs

The system must support unlimited AI employees in future.

Examples:

Sales Agent

Voice Agent

Email Agent

WhatsApp Agent

Support Agent

Proposal Agent

Appointment Setter

Collection Agent

The architecture must be reusable.

=========================================================
IMPORTANT ARCHITECTURE RULES
=========================================================

Laravel remains the source of truth.

Never let external providers own business logic.

Business rules belong inside Laravel.

Controllers remain thin.

Business logic belongs inside Services.

Use:

DTOs

Actions

Service Classes

Repositories only where needed

API Resources

Events

Listeners

Jobs

Policies

Form Requests

Value Objects where useful

Avoid God classes.

Follow SOLID.

Follow Clean Architecture.

Follow Laravel 13 best practices.

Code should look human written.

=========================================================
FIRST DEVELOPMENT TARGET
=========================================================

The first AI Employee is

AI Voice Sales Agent

BUT

Before integrating Retell,

we must build the reusable AI platform.

=========================================================
PHASE 7.1
Business Knowledge Module
=========================================================

Before an AI can sell,

it must know what the company sells.

Build a Services module.

Each service stores:

Service Name

Slug

Description

Features

Benefits

Deliverables

Pricing Notes

FAQs

Objections

Cross Sell Services

Upsells

Status

Tags

Version

The AI will dynamically load these.

No hardcoded services inside prompts.

=========================================================
PHASE 7.2
AI FOUNDATION
=========================================================

Build an AI Platform module.

Not Retell integration yet.

Create reusable modules.

1.

AI Employees

Stores

Name

Role

Description

Department

System Prompt

Behavior Prompt

Knowledge Source

Allowed Actions

Allowed Tools

Memory

Context Window

Temperature

Provider

Model

Voice

Language

Status

Fallback Provider

Fallback Model

Working Hours

2.

AI Providers

Support multiple providers.

Examples

OpenAI

Gemini

OpenRouter

Grok

Anthropic

Each provider stores

API Keys

Priority

Limits

Status

Retry Count

Timeout

3.

AI Models

Provider

Model Name

Capabilities

Supports Voice

Supports Vision

Supports Tools

Max Tokens

Pricing

Status

4.

Prompt Library

Prompt Templates

Variables

Categories

Versions

Revision History

Approval Status

Tags

5.

Knowledge Base

Company Information

Portfolio

Case Studies

Services

FAQs

Policies

Pricing Notes

6.

AI Gateway

This becomes the ONLY layer allowed to communicate with AI providers.

Responsibilities:

Provider Selection

Fallback Logic

Prompt Building

Context Building

Cost Tracking

Retry Logic

Logging

Response Parsing

Never call AI providers directly from business modules.

=========================================================
PHASE 7.3
VOICE PLATFORM
=========================================================

Voice providers must be abstracted.

Primary

Retell

Fallback

Vapi

Future

Other providers

Create

VoiceProviderInterface

RetellProvider

VapiProvider

VoiceManager

Provider selection must come from database.

Not config files.

=========================================================
PHASE 7.4
VOICE SALES AGENT
=========================================================

On Lead Details page

Add

Start AI Voice Call

When clicked

Dispatch Queue Job

Job communicates with VoiceManager

VoiceManager selects provider

Provider starts outbound call

Conversation happens

When conversation ends

Webhook returns data

Laravel stores

Transcript

Summary

Audio URL

Tokens

Duration

Outcome

Meeting

Confidence

Lead Status

Activities

Everything must be logged.

=========================================================
LEAD DETAILS PAGE
=========================================================

Add new tabs.

Overview

Verification

Score

AI Activities

Voice Calls

Conversation History

AI Logs

Timeline

Meetings

Prompt Used

Model Used

Costs

=========================================================
NODE SERVICE
=========================================================

Current SRP Service remains ONLY for scraping.

Do NOT place Voice AI there.

If a Node.js service becomes necessary,

design a NEW Voice Service.

Laravel orchestrates it.

Never mix scraping with voice logic.

=========================================================
USE LARAVEL AI
=========================================================

Evaluate Laravel AI (Laravel 13 AI package).

If it simplifies provider abstraction,

prompt execution,

tool calling,

or structured responses,

use it.

If custom implementation provides more flexibility,

explain why before implementation.

Do not blindly use packages.

=========================================================
EXPECTED DEVELOPMENT STYLE
=========================================================

Do NOT generate all code immediately.

Work in phases.

Before each phase

Explain

Architecture

Database changes

Folder structure

Flow

Events

Jobs

Services

Then implement.

After each phase

Wait for approval.

Do not jump ahead.

Always preserve backward compatibility with the existing Lead Intelligence CRM.
One Architectural Improvement I'd Add

Since your project is becoming much larger, I'd also ask Cursor to organize it by domain, not just Laravel defaults.

For example:

app/
 ├── Domains/
 │    ├── LeadIntelligence/
 │    ├── AI/
 │    ├── Communication/
 │    ├── Voice/
 │    ├── Services/
 │    ├── Deals/
 │    ├── Projects/
 │    └── Shared/
 ├── Support/
 ├── Infrastructure/
 └── AI/

This will keep the project maintainable as it grows into a full AI-powered agency operating system, instead of ending up with hundreds of service classes in a single app/Services directory. I strongly recommend adopting a domain-oriented structure now, before the codebase expands further.

let me know what did you understood and share your planings

---

## Assistant

# Understanding & Planning for Phase 7: AI Workforce Platform

I've reviewed your requirements thoroughly. Let me share my understanding and proposed architecture before we begin implementation.

---

## What I Understood

### Vision
The Lead Intelligence CRM evolves into an **AI Workforce Platform** where AI employees (voice agents, email agents, etc.) consume qualified leads and autonomously handle sales workflows — calls, conversations, meetings, deals.

### Boundaries
- **Existing modules are frozen** — Lead ingestion, scoring, verification, pitch recommendations remain untouched
- **New modules are additive** — AI workforce is a new bounded context that *consumes* leads, doesn't modify the lead pipeline
- **Laravel owns all business logic** — External providers (Retell, OpenAI) are abstracted behind interfaces; they execute, Laravel orchestrates

### First Deliverable
AI Voice Sales Agent that can call a qualified lead, have a conversation, and log the outcome — built on a **reusable AI platform** that supports unlimited future employees.

---

## Proposed Domain-Oriented Structure

I agree with your suggestion to reorganize by domain. Here's my proposed structure:

```
app/
├── Domains/
│   ├── LeadIntelligence/          # Existing (untouched)
│   │   ├── Services/
│   │   │   ├── LeadIngestionService.php
│   │   │   ├── LeadScoringService.php
│   │   │   ├── LeadVerificationService.php
│   │   │   ├── LeadPitchService.php
│   │   │   └── LeadEnrichmentService.php
│   │   ├── Jobs/
│   │   ├── Support/
│   │   └── DataTransferObjects/
│   │
│   ├── BusinessKnowledge/          # Phase 7.1
│   │   ├── Models/
│   │   │   └── Service.php
│   │   ├── Services/
│   │   │   └── ServiceCatalogService.php
│   │   ├── DataTransferObjects/
│   │   │   └── ServiceDTO.php
│   │   └── Http/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       └── Resources/
│   │
│   ├── AI/                         # Phase 7.2
│   │   ├── Models/
│   │   │   ├── AiEmployee.php
│   │   │   ├── AiProvider.php
│   │   │   ├── AiModel.php
│   │   │   ├── PromptTemplate.php
│   │   │   ├── PromptVersion.php
│   │   │   ├── KnowledgeBase.php
│   │   │   └── AiLog.php
│   │   ├── Services/
│   │   │   ├── AiGateway.php           # Single entry point for all AI calls
│   │   │   ├── ProviderSelector.php
│   │   │   ├── PromptBuilder.php
│   │   │   ├── ContextBuilder.php
│   │   │   └── CostTracker.php
│   │   ├── Contracts/
│   │   │   ├── AiProviderInterface.php
│   │   │   └── PromptableInterface.php
│   │   ├── Providers/
│   │   │   ├── OpenAiProvider.php
│   │   │   ├── AnthropicProvider.php
│   │   │   ├── GeminiProvider.php
│   │   │   └── OpenRouterProvider.php
│   │   ├── DataTransferObjects/
│   │   │   ├── AiRequest.php
│   │   │   ├── AiResponse.php
│   │   │   └── PromptContext.php
│   │   ├── Events/
│   │   │   ├── AiRequestSent.php
│   │   │   └── AiResponseReceived.php
│   │   └── Listeners/
│   │       └── LogAiInteraction.php
│   │
│   ├── Voice/                      # Phase 7.3
│   │   ├── Models/
│   │   │   ├── VoiceProvider.php
│   │   │   ├── VoiceCall.php
│   │   │   └── CallTranscript.php
│   │   ├── Services/
│   │   │   └── VoiceManager.php
│   │   ├── Contracts/
│   │   │   └── VoiceProviderInterface.php
│   │   ├── Providers/
│   │   │   ├── RetellProvider.php
│   │   │   └── VapiProvider.php
│   │   ├── Jobs/
│   │   │   └── InitiateVoiceCall.php
│   │   ├── Events/
│   │   │   ├── CallInitiated.php
│   │   │   ├── CallCompleted.php
│   │   │   └── CallFailed.php
│   │   └── Http/
│   │       └── Controllers/
│   │           └── VoiceWebhookController.php
│   │
│   ├── Sales/                      # Phase 7.4+
│   │   ├── Models/
│   │   │   ├── Conversation.php
│   │   │   ├── Meeting.php
│   │   │   └── Deal.php
│   │   └── Services/
│   │       └── SalesWorkflowService.php
│   │
│   └── Shared/                     # Cross-domain utilities
│       ├── Enums/
│       ├── ValueObjects/
│       └── Traits/
│
├── Http/Controllers/               # Thin controllers (existing pattern)
├── Models/                         # Shared models (User, Lead, etc.)
├── Support/                        # Existing support classes
└── Providers/
```

### Migration Strategy for Existing Code

Rather than moving files immediately (risky), I propose:
1. **New code** goes into `app/Domains/`
2. **Existing services** (`LeadScoringService`, etc.) stay in `app/Services/` for now
3. After Phase 7 is stable, we can optionally migrate existing code to `app/Domains/LeadIntelligence/` in a separate refactor phase

This preserves backward compatibility and avoids breaking 134 passing tests.

---

## Phase-by-Phase Architecture Plan

### Phase 7.1: Business Knowledge Module

**Purpose**: Store what the company sells so AI employees can dynamically load service knowledge.

**Database Schema**:
```
services
├── id
├── uuid
├── name
├── slug (unique)
├── description (text)
├── features (json)           # ["Feature 1", "Feature 2"]
├── benefits (json)
├── deliverables (json)
├── pricing_notes (text)
├── faqs (json)               # [{q: "", a: ""}, ...]
├── objections (json)         # [{objection: "", response: ""}, ...]
├── cross_sell_ids (json)     # [service_id, ...]
├── upsell_ids (json)
├── tags (json)
├── status (enum: draft, active, archived)
├── version (int, default 1)
├── created_by
├── timestamps
├── soft_deletes
```

**Key Classes**:
- `Service` model with JSON casts
- `ServiceCatalogService` for retrieval/formatting for AI context
- `ServiceResource` API resource
- Admin CRUD UI at `/admin/services`

**Flow**:
```
AI Employee needs product knowledge
    ↓
ContextBuilder::forEmployee($employee)
    ↓
ServiceCatalogService::getActiveServices()
    ↓
Returns structured array for prompt injection
```

---

### Phase 7.2: AI Foundation

**Purpose**: Reusable infrastructure for any AI employee type.

**Database Schema**:

```
ai_providers
├── id
├── slug (openai, anthropic, gemini, openrouter, grok)
├── name
├── api_key (encrypted)
├── api_base_url (nullable)
├── priority (int, for selection order)
├── rate_limit_rpm (nullable)
├── timeout_seconds (default 30)
├── retry_count (default 3)
├── status (enum: active, disabled, degraded)
├── timestamps

ai_models
├── id
├── ai_provider_id (FK)
├── slug (gpt-4o, claude-3-opus, etc.)
├── name
├── capabilities (json)       # {text: true, vision: true, tools: true, voice: false}
├── max_tokens (int)
├── input_price_per_1k (decimal)
├── output_price_per_1k (decimal)
├── status
├── timestamps

ai_employees
├── id
├── uuid
├── name ("Alex - Sales Agent")
├── role (enum: sales, support, appointment_setter, etc.)
├── department (nullable)
├── description
├── system_prompt (text)
├── behavior_prompt (text)
├── knowledge_sources (json)  # ["services", "faqs", "company_info"]
├── allowed_actions (json)    # ["schedule_meeting", "send_email", "transfer_call"]
├── allowed_tools (json)      # ["calendar_lookup", "crm_search"]
├── memory_enabled (bool)
├── context_window (int)
├── temperature (decimal, 0.0-2.0)
├── ai_provider_id (FK, primary)
├── ai_model_id (FK, primary)
├── fallback_provider_id (FK, nullable)
├── fallback_model_id (FK, nullable)
├── voice_provider_id (FK, nullable)  # For voice employees
├── voice_id (nullable)               # Provider-specific voice ID
├── language (default 'en')
├── working_hours (json)      # {start: "09:00", end: "18:00", timezone: "UTC", days: [1-5]}
├── status (enum: active, training, disabled)
├── created_by
├── timestamps

prompt_templates
├── id
├── slug
├── name
├── category (enum: system, behavior, task, objection_handling, etc.)
├── content (text)
├── variables (json)          # ["lead_name", "service_name", "company"]
├── tags (json)
├── status (enum: draft, active, archived)
├── created_by
├── timestamps

prompt_versions
├── id
├── prompt_template_id (FK)
├── version (int)
├── content (text)
├── change_notes
├── approved_by (nullable)
├── approved_at (nullable)
├── created_by
├── timestamps

knowledge_bases
├── id
├── slug
├── name
├── category (enum: company, portfolio, case_study, policy, faq)
├── content (text)
├── metadata (json)
├── status
├── timestamps

ai_logs
├── id
├── ai_employee_id (FK)
├── ai_provider_id (FK)
├── ai_model_id (FK)
├── lead_id (FK, nullable)
├── request_type (enum: chat, voice, tool_call)
├── prompt_tokens (int)
├── completion_tokens (int)
├── total_tokens (int)
├── cost_usd (decimal)
├── latency_ms (int)
├── status (enum: success, error, timeout, rate_limited)
├── error_message (nullable)
├── request_payload (json)
├── response_payload (json)
├── timestamps
```

**Key Classes**:

```php
// Contracts
interface AiProviderInterface {
    public function chat(AiRequest $request): AiResponse;
    public function supportsStreaming(): bool;
    public function supportsTools(): bool;
}

// Gateway (single entry point)
class AiGateway {
    public function send(AiEmployee $employee, PromptContext $context): AiResponse;
    // Handles: provider selection, fallback, logging, cost tracking
}

// DTOs
class AiRequest {
    public function __construct(
        public string $systemPrompt,
        public string $userPrompt,
        public array $messages,
        public array $tools,
        public float $temperature,
        public int $maxTokens,
    ) {}
}

class AiResponse {
    public function __construct(
        public bool $success,
        public ?string $content,
        public ?array $toolCalls,
        public int $promptTokens,
        public int $completionTokens,
        public float $costUsd,
        public int $latencyMs,
        public ?string $error,
    ) {}
}
```

**Flow**:
```
Business Module needs AI response
    ↓
AiGateway::send($employee, $context)
    ↓
ProviderSelector::select($employee)  # Primary or fallback
    ↓
PromptBuilder::build($employee, $context)  # Injects knowledge, lead data
    ↓
$provider->chat($request)
    ↓
CostTracker::log($employee, $response)
    ↓
Event: AiResponseReceived
    ↓
Return AiResponse to caller
```

---

### Phase 7.3: Voice Platform

**Purpose**: Abstract voice providers so we can swap Retell/Vapi without touching business logic.

**Database Schema**:
```
voice_providers
├── id
├── slug (retell, vapi)
├── name
├── api_key (encrypted)
├── api_base_url
├── webhook_secret (encrypted)
├── priority (for selection)
├── status
├── timestamps

voice_calls
├── id
├── uuid
├── voice_provider_id (FK)
├── ai_employee_id (FK)
├── lead_id (FK)
├── provider_call_id (varchar)  # Retell/Vapi's call ID
├── direction (enum: outbound, inbound)
├── to_phone
├── from_phone
├── status (enum: queued, ringing, in_progress, completed, failed, no_answer)
├── started_at
├── answered_at
├── ended_at
├── duration_seconds
├── recording_url
├── transcript (text)
├── summary (text)
├── outcome (enum: interested, not_interested, callback, meeting_booked, voicemail, no_answer)
├── meeting_booked_at (nullable)
├── confidence_score (decimal, 0-1)
├── sentiment (enum: positive, neutral, negative)
├── tokens_used (int)
├── cost_usd (decimal)
├── metadata (json)
├── timestamps
```

**Key Classes**:
```php
interface VoiceProviderInterface {
    public function initiateCall(VoiceCallRequest $request): VoiceCallResponse;
    public function getCallStatus(string $callId): CallStatus;
    public function endCall(string $callId): bool;
    public function parseWebhook(array $payload): WebhookEvent;
}

class VoiceManager {
    public function call(AiEmployee $employee, Lead $lead): VoiceCall;
    public function handleWebhook(string $provider, array $payload): void;
}
```

**Webhook Flow**:
```
Retell/Vapi call ends
    ↓
POST /api/voice/webhook/{provider}
    ↓
VoiceWebhookController
    ↓
VoiceManager::handleWebhook()
    ↓
Update VoiceCall record
    ↓
Event: CallCompleted
    ↓
Listeners: UpdateLeadStatus, LogActivity, NotifyUser
```

---

### Phase 7.4: Voice Sales Agent Integration

**Lead Details Page Updates**:

New tabs:
- **AI Activities** — All AI interactions with this lead
- **Voice Calls** — Call history with transcripts, recordings, outcomes
- **Timeline** — Unified activity feed (scoring, verification, calls, emails)

New action button:
- **Start AI Voice Call** → Dispatches `InitiateVoiceCall` job

**Flow**:
```
Agent clicks "Start AI Voice Call"
    ↓
POST /leads/{lead}/voice-call
    ↓
LeadVoiceController::store()
    ↓
InitiateVoiceCall::dispatch($lead, $employee)
    ↓
Queue worker picks up job
    ↓
VoiceManager::call($employee, $lead)
    ↓
ContextBuilder loads: lead data, services, prompts
    ↓
VoiceProvider::initiateCall()
    ↓
Retell/Vapi starts call
    ↓
(conversation happens)
    ↓
Webhook returns transcript/outcome
    ↓
VoiceCall record updated
    ↓
Lead page shows call result
```

---

## Laravel AI Evaluation

I looked at Laravel AI (the official Laravel 13 package). It provides:
- Unified interface for OpenAI, Anthropic, Gemini
- Streaming support
- Tool/function calling
- Structured output (JSON mode)

**My recommendation**: Use Laravel AI as the **foundation** for our `AiProviderInterface` implementations. It handles the HTTP/SDK details, while our `AiGateway` handles:
- Provider selection from database
- Fallback logic
- Cost tracking
- Logging
- Context building

This gives us the flexibility you asked for while avoiding reinventing HTTP clients.

---

## Questions Before Implementation

1. **Domain folder migration**: Should I move existing services to `app/Domains/LeadIntelligence/` now, or keep them in `app/Services/` until Phase 7 is complete?

2. **Admin UI**: Should services/employees/prompts have full admin CRUD pages, or start with seeders/database entries only?

3. **Laravel AI**: Should I evaluate the package in detail first (read docs, test capabilities), or proceed with custom provider implementations?

4. **Voice provider**: Do you have Retell API access ready, or should I build the abstraction layer first with mock implementations?

5. **Lead page tabs**: Should the new tabs be added to the existing `Show.vue`, or should we create a tabbed layout component?

6. **Permissions**: New permissions needed — `ai.manage`, `voice.call`, `services.manage`? Or more granular?

---

## Proposed Implementation Order

```
Phase 7.1a — Database: services table + migration
Phase 7.1b — Model + Service: Service.php, ServiceCatalogService
Phase 7.1c — Admin UI: /admin/services CRUD
Phase 7.1d — Tests: ServiceCatalogTest

[Approval checkpoint]

Phase 7.2a — Database: ai_providers, ai_models, ai_employees, prompt_templates, ai_logs
Phase 7.2b — Contracts: AiProviderInterface, DTOs
Phase 7.2c — Gateway: AiGateway, ProviderSelector, PromptBuilder
Phase 7.2d — Providers: OpenAiProvider (with Laravel AI)
Phase 7.2e — Admin UI: Basic AI config pages
Phase 7.2f — Tests

[Approval checkpoint]

Phase 7.3a — Database: voice_providers, voice_calls
Phase 7.3b — Contracts: VoiceProviderInterface
Phase 7.3c — Providers: RetellProvider (or mock)
Phase 7.3d — VoiceManager + webhook controller
Phase 7.3e — Tests

[Approval checkpoint]

Phase 7.4a — Lead page: Add voice call button + tabs
Phase 7.4b — Job: InitiateVoiceCall
Phase 7.4c — End-to-end flow
Phase 7.4d — Tests

[Approval checkpoint]
```

---

## Summary

I understand:
- This is an **extension**, not a rewrite
- Laravel owns all logic; providers are execution engines
- Domain-driven structure for maintainability
- Phased development with approval gates
- First target: Voice Sales Agent on a reusable AI platform

Ready to start **Phase 7.1a** (services migration) once you approve the plan and answer the questions above.

[REDACTED]

---

## User

- keep them in app/Services/ until Phase 7 is completed
- Admin UI: full crud pages but use modals for small forms
- use laravel ai pkg
- i will get the api and add in db because it will also has a module to update the provider details
- add new tabs in exisiting lead details page
- should work permission based.
- also add livekit to alternate retell 

now start the 
Phase 7.1a — Database: services table + migration
Phase 7.1b — Model + Service: Service.php, ServiceCatalogService
Phase 7.1c — Admin UI: /admin/services CRUD
Phase 7.1d — Tests: ServiceCatalogTest

---

## Assistant

Phase 7.1 is complete. Here's what was built:

## Phase 7.1 — Business Knowledge Module

### Database (`services` table)
- Fields: name, slug, description, features, benefits, deliverables, pricing_notes, faqs, objections, cross_sell_ids, upsell_ids, tags, status, version, audit columns
- Soft deletes, UUID, unique slug

### Backend
| File | Purpose |
|------|---------|
| `app/Domains/BusinessKnowledge/Models/Service.php` | Eloquent model with auto-slug |
| `app/Domains/BusinessKnowledge/Enums/ServiceStatus.php` | draft / active / archived |
| `app/Domains/BusinessKnowledge/DataTransferObjects/ServiceKnowledgeItem.php` | AI-ready DTO |
| `app/Services/ServiceCatalogService.php` | CRUD, pagination, `activeKnowledgeForAi()` |
| `app/Http/Controllers/Admin/ServiceController.php` | Thin controller |
| Form requests + `ServiceResource` | Validation & API shape |

### Permissions
- `services.view`, `services.create`, `services.update`, `services.delete`
- Assigned to `agent` role; super admin bypass unchanged

### Admin UI
- **`/admin/services`** — searchable index with status/tag filters, create modal
- **`/admin/services/{id}`** — detail page with edit modal + delete confirmation
- Sidebar link added (permission-gated)

### Tests
- **11 new tests** in `ServiceCatalogTest.php`
- **145/145 total tests passing**

---

### To use locally
```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder   # if permissions not yet seeded
npm run dev   # or use existing build
```

Then log in as `agent@example.com` / `password` and open **Services** in the sidebar.

---

### AI integration hook (ready for Phase 7.2)
```php
app(ServiceCatalogService::class)->activeKnowledgeForAi();
// Returns ServiceKnowledgeItem[] for active services — no hardcoded prompts
```

Existing Lead Intelligence modules are untouched. Ready for **Phase 7.2 (AI Foundation)** when you approve.

[REDACTED]

---

## User

- create a seeder to seed the services as you know already
- share the next plan

---

## Assistant

## Service seeder added

**File:** `database/seeders/ServiceSeeder.php`  
**Registered in:** `DatabaseSeeder` (runs after users)

### Seeded catalog (8 services)

| Service | Slug | Status |
|---------|------|--------|
| Website Build & Launch | `website-build` | Active |
| Website Audit & CRO | `website-audit` | Active |
| Google Business Profile Optimization | `google-business-profile` | Active |
| Reviews Growth System | `reviews-growth` | Active |
| Reputation Repair & Feedback Workflow | `reputation-repair` | Active |
| Local SEO Retainer | `local-seo-retainer` | Active |
| Landing Page Sprint | `landing-page-sprint` | Active |
| Social Content Pack | `social-content-pack` | Draft |

Each record includes features, benefits, deliverables, pricing notes, FAQs, objections, tags, and cross-sell/upsell links aligned with your existing lead pitch types (website build, audit, GBP, reviews, reputation).

### Run it

```bash
# Full seed (roles, users, services)
php artisan db:seed

# Services only (needs superadmin user)
php artisan db:seed --class=ServiceSeeder
```

Re-running is safe — uses `updateOrCreate` by slug.

---

## Next plan — Phase 7.2: AI Foundation

Goal: reusable AI platform that any employee type can use. No Retell/LiveKit calls yet — only text/LLM infrastructure.

### 7.2a — Database & config

| Table | Purpose |
|-------|---------|
| `ai_providers` | OpenAI, Anthropic, Gemini, OpenRouter, Grok — API keys (encrypted), priority, limits, retry, timeout, status |
| `ai_models` | Per-provider models, capabilities (voice/vision/tools), max tokens, pricing |
| `ai_employees` | Name, role, prompts, knowledge sources, allowed actions/tools, provider/model, fallback, temperature, working hours |
| `prompt_templates` | Reusable templates with variables, categories, tags |
| `prompt_versions` | Revision history + approval |
| `knowledge_bases` | Company info, policies, portfolio (beyond services) |
| `ai_logs` | Every gateway call — tokens, cost, latency, payloads |

**Config:** `config/ai.php` for defaults only (never secrets). Business rules stay in DB + services.

### 7.2b — Domain layer (`app/Domains/AI/`)

```
Contracts/
  AiProviderInterface.php
Services/
  AiGateway.php          ← ONLY entry point for LLM calls
  ProviderSelector.php   ← DB-driven primary + fallback
  PromptBuilder.php      ← Injects services via ServiceCatalogService
  ContextBuilder.php     ← Lead + employee + knowledge assembly
  CostTracker.php
Providers/
  LaravelAiOpenAiProvider.php   ← wraps Laravel AI package
  LaravelAiAnthropicProvider.php
  ...
DataTransferObjects/
  AiRequest.php, AiResponse.php, PromptContext.php
Events/Listeners/
  AiRequestSent, AiResponseReceived → LogAiInteraction
```

**Rule:** Business modules never call OpenAI/etc. directly — only `AiGateway::send()`.

### 7.2c — Laravel AI package

```bash
composer require laravel/ai
php artisan ai:install
```

Use Laravel AI for HTTP/SDK, streaming, tools, structured JSON. Our gateway owns selection, fallback, logging, and cost tracking from DB provider records.

### 7.2d — Admin UI (modal-based CRUD)

| Route | Permission |
|-------|------------|
| `/admin/ai/providers` | `ai.providers.manage` |
| `/admin/ai/models` | `ai.models.manage` |
| `/admin/ai/employees` | `ai.employees.manage` |
| `/admin/ai/prompts` | `ai.prompts.manage` |
| `/admin/ai/logs` | `ai.logs.view` |

Provider API keys edited in admin (encrypted at rest), not `.env`.

### 7.2e — Knowledge wiring

`ContextBuilder` loads:
- Active services → `ServiceCatalogService::activeKnowledgeForAi()`
- Selected `knowledge_bases` rows
- Lead context (when calling from a lead)
- Employee system/behavior prompts

No hardcoded product copy in prompts.

### 7.2f — Tests

- Provider selection + fallback
- Prompt building includes seeded services
- Gateway logs cost/tokens
- Permission gates on admin routes
- Encrypted API key round-trip

---

## After 7.2 — Phase 7.3: Voice platform

| Provider | Role |
|----------|------|
| **Retell** | Primary voice provider |
| **LiveKit** | Alternate (as you requested) |
| **Vapi** | Secondary fallback |

```
app/Domains/Voice/
  Contracts/VoiceProviderInterface.php
  Providers/RetellProvider.php, LiveKitProvider.php, VapiProvider.php
  Services/VoiceManager.php   ← DB-driven selection (not config)
  Models/voice_providers, voice_calls, call_transcripts
```

Voice providers stored in DB like AI providers — keys, priority, webhook secrets, status.

---

## Phase 7.4 — Voice Sales Agent (on lead detail)

- **Start AI Voice Call** button → `InitiateVoiceCall` job → `VoiceManager`
- New tabs on lead show: AI Activities, Voice Calls, Timeline, Costs
- Webhooks store transcript, summary, recording URL, outcome, meeting booked

---

## Suggested approval order

1. **Phase 7.2a** — migrations + enums  
2. **Phase 7.2b–c** — gateway + Laravel AI  
3. **Phase 7.2d** — admin UI  
4. **Phase 7.2e–f** — context wiring + tests  

Say **“start 7.2a”** when you want to proceed with the AI Foundation migrations.

[REDACTED]

---

## User

my services are from basic website to top notch websites, ecommerce stores, ai automations, CRM, web applciation mobile application and so on other servies related to it.

---

## Assistant

Updated `ServiceSeeder` to match your real agency/software portfolio. Re-run with:

```bash
php artisan db:seed --class=ServiceSeeder
```

Old marketing-only entries (GBP, reviews, local SEO, etc.) are **archived** automatically if they exist from a previous seed.

## New service catalog (15 active services)

### Websites (basic → premium)
| Service | Slug |
|---------|------|
| Basic Website | `basic-website` |
| Business Website | `business-website` |
| Premium Website | `premium-website` |
| Landing Page | `landing-page` |
| Website Redesign & Modernization | `website-redesign` |

### Commerce & apps
| Service | Slug |
|---------|------|
| E-commerce Store | `ecommerce-store` |
| Web Application | `web-application` |
| Mobile Application | `mobile-application` |

### CRM & intelligence
| Service | Slug |
|---------|------|
| Custom CRM Development | `crm-development` |
| Lead Intelligence CRM | `lead-intelligence-crm` |

### AI
| Service | Slug |
|---------|------|
| AI Automation & Workflows | `ai-automation` |
| AI Workforce Platform | `ai-workforce-platform` |

### Supporting
| Service | Slug |
|---------|------|
| UI/UX Design | `ui-ux-design` |
| API & System Integrations | `api-integrations` |
| Website Maintenance & Support | `website-maintenance` |

Each record includes features, benefits, deliverables, pricing notes, FAQs, objections, tags, and **cross-sell / upsell** links (e.g. Basic → Business → Premium → Web App → AI Workforce).

---

**Upsell ladder example:**
```
Basic Website → Business Website → Premium Website
                              ↓
                    E-commerce / Web App → Mobile App
                              ↓
              CRM / Lead Intelligence CRM → AI Automation → AI Workforce
```

You can edit any detail in **Admin → Services** or tell me what to adjust (pricing ranges, tech stack names, new services). When ready, say **“start 7.2a”** for the AI Foundation phase.

[REDACTED]

---

## User

- start Phase 7.2a — migrations + enums

---

## Assistant

Phase **7.2a** is complete — migrations and enums for the AI Foundation are in place.

## Enums (`app/Domains/AI/Enums/`)

| Enum | Values |
|------|--------|
| `AiProviderStatus` | active, disabled, degraded |
| `AiModelStatus` | active, disabled |
| `AiEmployeeStatus` | active, training, disabled |
| `AiEmployeeRole` | sales, voice_sales, email, whatsapp, support, appointment_setter, proposal, collection, general |
| `PromptTemplateStatus` | draft, active, archived |
| `PromptTemplateCategory` | system, behavior, task, objection_handling, outreach, summarization, tool |
| `PromptApprovalStatus` | pending, approved, rejected |
| `KnowledgeBaseCategory` | company, portfolio, case_study, policy, faq, services, pricing |
| `KnowledgeBaseStatus` | draft, active, archived |
| `AiRequestType` | chat, completion, tool_call, voice, structured |
| `AiLogStatus` | success, error, timeout, rate_limited |

## Migrations (7 tables)

| Table | Purpose |
|-------|---------|
| `ai_providers` | Provider config — slug, encrypted-ready `api_key`, priority, limits, retry, timeout, status |
| `ai_models` | Models per provider — capabilities JSON, pricing, max tokens |
| `prompt_templates` | Reusable prompts — category, variables, tags, versioning entry point |
| `prompt_versions` | Revision history + approval workflow |
| `knowledge_bases` | Company/portfolio/policies/FAQs beyond services catalog |
| `ai_employees` | AI employee definitions — prompts, knowledge sources, provider/model + fallbacks, voice_id (provider FK comes in 7.3) |
| `ai_logs` | Gateway audit trail — tokens, cost, latency, payloads, lead link |

**Migration order:** providers → models → prompts → versions → knowledge → employees → logs

## Tests
- `tests/Feature/AiFoundationMigrationTest.php` — 3 tests (tables, columns, enums)
- **148/148** total tests passing

## Apply locally
```bash
php artisan migrate
```

---

### Deferred to later phases (by design)
- **Models** → Phase 7.2b  
- **`voice_provider_id`** on employees → Phase 7.3 (Voice platform)  
- **Admin UI / permissions** → Phase 7.2d  
- **`config/ai.php`** → Phase 7.2b with gateway defaults  

---

### Next: Phase 7.2b — Domain layer + Laravel AI
- Eloquent models with enum casts  
- DTOs (`AiRequest`, `AiResponse`, `PromptContext`)  
- `AiGateway`, `ProviderSelector`, `PromptBuilder`, `ContextBuilder`, `CostTracker`  
- Laravel AI package install + provider adapters  
- Wire `ServiceCatalogService` into `ContextBuilder`  

Say **“start 7.2b”** when you want to proceed.

[REDACTED]

---

## User

start Next: Phase

---

## Assistant

Phase **7.2b** is complete — the AI Foundation domain layer is built on Laravel AI.

## What was added

### Package & config
- **`laravel/ai` v0.8.1** installed
- **`config/ai.php`** published (Laravel AI providers)
- **`config/ai_platform.php`** — app rules: provider driver map, knowledge sources, logging limits

### Models (`app/Domains/AI/Models/`)
| Model | Notes |
|-------|--------|
| `AiProvider` | Encrypted `api_key`, selectable scope |
| `AiModel` | Capabilities JSON, pricing fields |
| `AiEmployee` | Prompts, knowledge sources, provider/model + fallbacks |
| `PromptTemplate` / `PromptVersion` | Prompt library + approval |
| `KnowledgeBase` | Company/portfolio/policy content |
| `AiLog` | Full audit trail per request |

### DTOs & contracts
- `AiRequest`, `AiResponse`, `PromptContext`, `ProviderSelection`
- `AiTextGeneratorInterface` → implemented by `LaravelAiConnector`
- `PromptableContextInterface` → implemented by `PromptBuilder`

### Services (`app/Domains/AI/Services/`)
| Service | Role |
|---------|------|
| **`AiGateway`** | **Only entry point** for LLM calls — retry, fallback, logging, events |
| `LaravelAiConnector` | Runs Laravel AI with DB-stored API keys (runtime config) |
| `ProviderSelector` | Primary + fallback from `ai_employees` |
| `PromptBuilder` | Builds instructions from employee + context |
| `ContextBuilder` | Loads **services** via `ServiceCatalogService` + knowledge bases + lead |
| `CostTracker` | Token cost calculation + `ai_logs` persistence |

### Events
- `AiRequestSent` / `AiResponseReceived` → `LogAiInteraction` listener

### Usage (from any future module)
```php
$context = app(ContextBuilder::class)->build(
    employee: $employee,
    lead: $lead,
    userMessage: 'What should I pitch?',
);

$response = app(AiGateway::class)->send($employee, $context);
// $response->content, $response->log, $response->costUsd
```

### Tests
- **`tests/Feature/AiGatewayTest.php`** — 7 tests (context, prompts, selector, cost, gateway, fallback)
- **155/155** total tests passing

---

## Architecture flow

```
Business module
    ↓
AiGateway::send()
    ↓
ProviderSelector → PromptBuilder → ContextBuilder (ServiceCatalogService)
    ↓
LaravelAiConnector → Laravel AI SDK → OpenAI/Anthropic/etc.
    ↓
CostTracker → ai_logs + AiResponseReceived event
```

**Rule enforced:** No module calls OpenAI/Laravel AI directly — only `AiGateway`.

---

## Next: Phase 7.2c / 7.2d — Admin UI

| Phase | Deliverable |
|-------|-------------|
| **7.2d** | Admin CRUD (modals): AI Providers, Models, Employees, Prompts, Knowledge, Logs viewer |
| **7.2d** | Permissions: `ai.providers.manage`, `ai.employees.manage`, etc. |
| **7.2d** | Seed default provider rows (keys entered in admin, not `.env`) |

Then **7.3** Voice platform (Retell + LiveKit + Vapi).

Say **“start 7.2d”** when you want the admin UI for AI configuration.

[REDACTED]

---

## User

Briefly inform the user about the task result and perform any follow-up actions (if needed). If there's no follow-ups needed, don't explicitly say that.

---

## Assistant

**Laravel AI install:** `composer require laravel/ai` finished successfully. The package is in the project (`laravel/ai` v0.8.1), and `config/ai.php` is published.

That install is already wired into **Phase 7.2b**: `LaravelAiConnector` uses it behind `AiGateway`, and the full suite is green (**155/155** tests).

**Next step when you’re ready:** Phase **7.2d** — admin UI for providers, models, employees, prompts, knowledge, and logs (API keys managed in the database via admin, not `.env`). Say **“start 7.2d”** to proceed.

[REDACTED]

---

## User

start Next: Phase 7.2c / 7.2d

---

## Assistant

Implementing Phase 7.2c/7.2d: permissions, seeders, and admin CRUD UI. Exploring existing admin patterns first.

[REDACTED]

---

## User

If the available MCP tools do not fully support what the user asked you to do, complete the work you can with the current tool set. In your work summary, include what you were unable to do with MCP and why. Do not use browser automation to work around missing or unavailable MCP tools unless the user explicitly asks you to use the browser.

---

## User

start Next: Phase 7.2c / 7.2d

---

## Assistant

**Phase 7.2c / 7.2d is complete.** Admin CRUD for the full AI platform is in place.

### Backend
- **6 controllers** under `app/Http/Controllers/Admin/` — Providers, Models, Employees, Prompts, Knowledge, Logs (read-only)
- **Form requests** + **API resources** — API keys never exposed; `has_api_key` flag only
- **Routes** at `/admin/ai/*` with permission middleware

### Frontend
- **5 form modals** + **12 Index/Show pages** under `resources/js/Pages/Admin/AI/`
- **Sidebar** “AI Platform” section in `AdminLayout.vue` (permission-gated)
- **`npm run build`** — assets compiled

### Tests & seeders
- **`tests/Feature/AiAdminTest.php`** — 8 tests (permissions, CRUD, API key masking, logs read-only)
- **`AiProviderSeeder`** — already wired in `DatabaseSeeder`
- **163/163 tests passing**

### Admin URLs
| Module | URL |
|--------|-----|
| Providers | `/admin/ai/providers` |
| Models | `/admin/ai/models` |
| Employees | `/admin/ai/employees` |
| Prompts | `/admin/ai/prompts` |
| Knowledge | `/admin/ai/knowledge` |
| Logs | `/admin/ai/logs` |

### Usage
1. Seed providers: `php artisan db:seed --class=AiProviderSeeder`
2. Open **AI Platform → Providers**, add API keys, set status to **Active**
3. Configure employees (e.g. “Alex — Voice Sales Agent” from seeder)

**Next up: Phase 7.3** — Voice platform (Retell, LiveKit, Vapi). Say the word when you want to start.

[REDACTED]

---

## User

start next

---

## Assistant

**Phase 7.3 — Voice Platform is complete.**

### Foundation
- **`config/voice_platform.php`** — provider drivers (retell, livekit, vapi), eligible employee roles
- **Enums** — `VoiceProviderStatus`, `VoiceCallStatus`, `VoiceCallDirection`
- **Migrations** — `voice_providers`, `voice_calls`
- **Models** — `VoiceProvider` (encrypted keys/secrets), `VoiceCall`

### Domain layer (`app/Domains/Voice/`)
- **`VoiceProviderInterface`** — single contract for all voice backends
- **Connectors** — `RetellVoiceConnector` (primary), `LiveKitVoiceConnector`, `VapiVoiceConnector`
- **`VoiceGateway`** — only entry point for outbound calls (provider fallback, retry, persistence)
- **`VoiceContextBuilder`** — injects lead/employee/service context as dynamic variables
- **Webhook** — `POST /api/voice/webhooks/{slug}` updates call status/transcript

### Admin
| URL | Purpose |
|-----|---------|
| `/admin/voice/providers` | CRUD for Retell/LiveKit/Vapi credentials |
| `/admin/voice/calls` | Read-only call history |

Sidebar links added under **Voice**. Permissions: `voice.providers.*`, `voice.calls.view`.

### Seeder
`VoiceProviderSeeder` seeds retell, livekit, vapi as **disabled** (no keys). Run:

```bash
php artisan db:seed --class=VoiceProviderSeeder
```

### Usage pattern (for Phase 7.4)
```php
$call = app(VoiceGateway::class)->initiateOutbound($employee, $lead, $user);
```

Configure a voice provider in admin (API key + metadata like `default_from_number`, `agent_id`), set status to **Active**, then wire the lead detail “Start AI Voice Call” button.

### Tests
**174/174 passing** (+11 voice tests). Frontend built.

**Next: Phase 7.4** — “Start AI Voice Call” on lead detail, plus tabs (AI Activities, Voice Calls, Timeline, Costs). Say when to start.

[REDACTED]

---

## User

start Next: Phase 7.4

---

## Assistant

**Phase 7.4 is complete.** Lead detail now has AI workforce integration with voice calling.

### Backend
- **`LeadWorkforcePanelService`** — aggregates AI logs, voice calls, timeline, and costs for a lead
- **`LeadVoiceCallController`** — `POST /leads/{lead}/voice-calls` initiates calls via `VoiceGateway`
- **`StartLeadVoiceCallRequest`** — validates optional `ai_employee_id`
- **Permission** — `voice.calls.create` (added to agent role)
- **Config** — `default_voice_employee_name` → `Alex — Voice Sales Agent`

### Lead Show UI
New **AI Workforce** panel on the lead detail page with:
- **Start AI Voice Call** button (employee picker when multiple agents exist)
- Blockers shown when calling isn’t possible (no phone, no provider, etc.)
- Tabs:
  - **AI Activities** — `AiLog` entries
  - **Voice Calls** — call history + status
  - **Timeline** — merged chronological events
  - **Costs** — AI + voice spend with breakdowns

Existing lead intelligence sections are unchanged; the workforce panel sits above pitch recommendations.

### Flow
1. Configure an active voice provider in **Voice Providers** admin
2. Ensure a voice-capable AI employee exists (e.g. Alex from seeder)
3. Open a lead with a phone number → click **Start AI Voice Call**
4. Track the call in the **Voice Calls** / **Timeline** tabs; webhooks update status automatically

### Tests
**178/178 passing** (+4 in `LeadWorkforceTest.php`). Frontend built.

Lead pipeline (ingest, scoring, verification, pitch) was not modified — this layer only reads lead data and writes workforce records.

[REDACTED]

---

## User

- ok now do the in depth QA of all steps implemented of Ai Employees feature. make sure each and everything should works perfect. 
- update the @.cursor rules/skills of this Ai employees
- create automation to export the chat after the commited already i had done in some other projects you can check this to implemnet it. 
@d:\herd\Agent-Press\scripts @d:\herd\Agent-Press\docs\cursor-chat-export.json

---

