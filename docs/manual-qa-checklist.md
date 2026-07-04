# Manual QA Checklist

End-to-end **user-level** verification for Lead Generation and AI Workforce.  
Automated tests: `php artisan test` (220+ tests).

---

## 0. Before you start

### Start services

| Service        | Command                                                       | Required for                                      |
| -------------- | ------------------------------------------------------------- | ------------------------------------------------- |
| Laravel (Herd) | Site running at your `.test` URL                              | Everything                                        |
| Queue worker   | `php artisan queue:work --queue=voice,default --tries=3`    | Scrape jobs, lead verification, **voice calls**   |
| SRP service    | `npm run dev` in `srp-service` folder                         | Live scraping + website analysis                  |

> Voice calls are **async**: the UI queues a job on the `voice` queue. Without the worker, calls stay **pending** and never dial Retell.

### Login credentials

| Email                    | Password   | Role                             |
| ------------------------ | ---------- | -------------------------------- |
| `agent@example.com`      | `password` | Agent (all permissions)          |
| `superadmin@example.com` | `password` | Super admin (bypasses all gates) |

### Fresh seed (recommended for full walkthrough)

```powershell
cd d:\herd\testapp
php artisan migrate:fresh --seed
npm run build
```

**Expected after seed:**

- 15 business services with rich knowledge fields
- 10 knowledge base articles (company overview, voice guidelines, pricing, etc.)
- AI employees (Alex — Voice Sales Agent), prompt templates, voice providers (Retell, LiveKit, Vapi)
- Sample leads (if any seeders create them)

---

## 1. Keys & credentials matrix

Everything below is **ready in the app** except items marked **YOU ADD**.

### Lead generation

| Item                      | Env / config                          | Required?           | Without it                                                                 |
| ------------------------- | ------------------------------------- | ------------------- | -------------------------------------------------------------------------- |
| SRP service URL           | `SRP_SERVICE_URL=http://localhost:3100` | Yes for scraping  | Scrape jobs fail immediately                                               |
| Ingest token              | `INGEST_API_TOKEN` (Laravel + SRP)    | Yes for scraping    | Ingest/callbacks return 401                                                |
| Google Places fallback    | `GOOGLE_PLACES_API_KEY` (SRP)         | No                  | Playwright-only; fallback if <3 results fails silently                     |
| Foursquare channel        | `FOURSQUARE_API_KEY` (SRP)            | Only for Foursquare | Foursquare jobs return 0 results                                           |
| Reddit OAuth              | Reddit vars in SRP `.env`             | Only for Reddit     | Channel disabled in CRM by default                                         |
| Contact verification gate | `LEAD_*` in `config/lead_quality.php` | Optional            | Directory leads without required contact fields rejected at ingest         |

### AI workforce

| Item                          | Where to add                                                  | Required?            | Without it                                                                    |
| ----------------------------- | ------------------------------------------------------------- | -------------------- | ----------------------------------------------------------------------------- |
| OpenAI (or other LLM) API key | Admin → AI → Providers → Edit OpenAI → paste key              | For text AI calls    | Provider **Active** but **API key: Not set**; `AiGateway` fails on send       |
| Retell API key                | Admin → Voice → Providers → Retell → Edit                     | For live voice calls | Provider **Active**, key **Not set**; bulk + single call UI hidden/blocked    |
| Retell from number            | Retell provider metadata `default_from_number`                | For voice calls      | Call fails: "requires a from_number"                                          |
| Retell agent ID               | Alex employee `voice_id` OR Retell metadata `agent_id`        | For voice calls      | Call may start without override agent (Retell default)                        |
| Voice webhook                 | Retell dashboard → `POST {APP_URL}/api/voice/webhooks/retell` | For status updates   | Calls stay queued; manual sync only                                           |

**Expected UI when keys are missing (correct behavior):**

- Admin provider pages: Status = **active**, API key = **Not set** (red).
- Leads index: **no checkbox column**, no bulk call bar.
- Lead detail → AI Workforce: blocker **"No active voice provider with API credentials."**
- **Start AI Voice Call** button hidden until Retell API key is saved.

---

## 2. Full user walkthrough — Lead generation

### 2.1 Login & dashboard

1. Open your app URL (e.g. `http://testapp.test`)
2. Login as `agent@example.com` / `password`
3. Land on **Dashboard**

**Expected:** Sidebar shows Leads, Scraper, Services, AI Platform, Voice Platform. No 403 or blank pages.

---

### 2.2 Manual lead create

1. **Leads** → **New Lead**
2. Fill: Company, First name, **Email**, **Phone**, Website (optional), Source = Manual
3. Submit

**Expected:**

- Redirect to lead detail
- Score card: Intent / Opportunity / Authenticity + temperature (HOT/WARM/COLD)
- "What to pitch" section visible

**Negative:** Omit email or phone → validation error.

---

### 2.3 Leads list & filters

1. Go to **Leads**
2. Use temperature tabs (All / HOT / WARM / COLD)
3. Apply filters: search, country, source, pitch type, sort, per page

**Expected:** Created lead appears; filters narrow results; pagination works; no console errors.

---

### 2.4 Scraper — Google Maps

1. Ensure SRP + queue worker running
2. **Scraper** → Lead source: **Google Maps**
3. Keyword: `dentist`, Country: `United States`, City: `New York`, Max results: `5`
4. Click **Run**

**Expected:**

- Job: **pending** → **running** → **completed** (or failed with clear error)
- Progress polls ~every 3s
- Completion popup: found / created / duplicate / failed counts
- Job detail shows run logs
- New leads under **Leads** with source `google_maps`

**Without SRP:** Job **failed** (connection error).  
**Without queue worker:** Job stays **pending**.

---

### 2.5 Scraper — other channels

Repeat 2.4 for channels you use:

| Channel                                       | Notes                                        |
| --------------------------------------------- | -------------------------------------------- |
| Yelp, Bing, OSM, Hotfrog, Yellow Pages, Manta | Location required; Playwright in SRP         |
| Foursquare                                    | Needs `FOURSQUARE_API_KEY` in SRP            |
| The Manifest, GoodFirms, DesignRush, UpCity   | Agency directories                           |
| Reddit                                        | Enable in `config/scraper.php` + Reddit OAuth |

**Expected:** Same job lifecycle; correct `source` on each lead.

---

### 2.6 Lead detail — verification & website analysis

1. Open a lead with a website
2. Wait for queue or click **Re-verify** if shown

**Expected:**

- Verification badge updates (pending → partial / fully verified)
- Website analysis panel may show tech stack, revamp score (needs SRP `/analyze-website`)

---

### 2.7 API ingest (optional)

```powershell
curl -X POST "http://testapp.test/api/leads/ingest" `
  -H "Authorization: Bearer YOUR_INGEST_TOKEN" `
  -H "Content-Type: application/json" `
  -d '{"source":"api","first_name":"Test","last_name":"User","email":"test@acme.com","phone":"+14155550100","company":"Acme Inc","website":"https://acme.com"}'
```

**Expected:** JSON `{ "status": "created", "lead_id": ... }` or `duplicate`.

---

## 3. Full user walkthrough — AI Workforce admin

### 3.1 Services catalog (Business Knowledge)

1. **Services** (`/admin/services`)

**Expected:** 15 active services (Laravel dev, SEO, AI Workforce, etc.) with ICP, FAQs, objections, pricing notes on show/edit pages.

---

### 3.2 Knowledge base

1. **AI Platform → Knowledge**

**Expected:** 10 active articles from seed (company overview, voice sales guidelines, pricing philosophy, engagement process, objections, etc.). Content readable on show pages.

---

### 3.3 AI providers & models

1. **AI Platform → Providers** — OpenAI, Anthropic, Gemini, OpenRouter, Grok (active, keys not set)
2. Edit **OpenAI** → paste API key → Save

**Expected:** `has_api_key` = **Configured**; raw key never shown again.

3. **AI Platform → Models** — GPT-4o, GPT-4o Mini, Claude, etc., all **active**

---

### 3.4 AI employees & prompts

1. **AI Platform → Employees** → open **Alex — Voice Sales Agent**

**Expected:**

- Status: **active**, Role: Voice Sales
- Knowledge sources: services, knowledge_bases, lead
- Provider/model linked (OpenAI / GPT-4o Mini)
- Voice ID: empty until you set Retell agent ID

2. **AI Platform → Prompts** — voice sales system, behavior, opening templates (active)

---

### 3.5 Voice providers

1. **Voice Platform → Providers**

**Expected:** Retell (priority 10), LiveKit (20), Vapi (30) — active, keys not set.

2. Edit **Retell** → add API key, metadata:
   - `default_from_number`: e.g. `+14155550100`
   - `agent_id`: your Retell agent ID (optional if set on Alex)

---

## 4. Voice calls — single lead (lead detail)

1. Open a lead **with phone number**
2. Scroll to **AI Workforce** panel

**Before Retell key:**

- Blocker: **No active voice provider with API credentials.**
- **Start AI Voice Call** hidden

**After Retell key + from number:**

- Blocker gone; **Start AI Voice Call** visible
- Employee picker if multiple voice agents

3. Click **Start AI Voice Call**

**Expected:**

- Redirect back to lead detail
- Flash: voice call started
- **Voice Calls** tab: new row — status flow **pending** → **queued** → ringing → in_progress → completed (with queue worker + Retell)
- **Timeline** merges AI + voice events
- **Costs** tab updates when call completes

**Without phone:** Blocker **Lead has no phone number.**

**Duplicate call:** Starting another call for same lead+employee while one is active → validation error.

---

## 5. Voice calls — bulk (leads index)

> Requires `voice.calls.create` permission + usable Retell provider (same as single call).

### 5.1 UI elements

1. Go to **Leads**
2. With Retell configured, confirm:
   - Checkbox column on the left
   - Select-all checkbox in header
   - Rows without phone: checkbox disabled (unless already selected)

3. Select 2–3 leads **with phone numbers**

**Expected:**

- Violet bulk bar: “N leads selected (max 50 per batch)”
- Buttons: **Clear**, **Start bulk AI voice calls**

4. Click **Start bulk AI voice calls**

**Expected modal:**

- Selected / callable / skipped (no phone) counts
- AI employee picker (if multiple voice agents)
- **Queue N calls** button

5. Confirm queue

**Expected:**

- Redirect back to leads index
- Green flash: “Queued **N** voice call(s). Skipped **M** …” if any skipped
- With queue worker: calls appear under **Voice Platform → Calls** and on each lead’s Voice Calls tab

### 5.2 Bulk edge cases

| Scenario                         | Expected                                              |
| -------------------------------- | ----------------------------------------------------- |
| Mix of leads with/without phone  | Callable queued; no-phone skipped (shown in modal)    |
| Lead already has active call     | Skipped with reason in session result                 |
| No Retell key                    | No checkboxes / bulk bar; POST returns blocker error  |
| Select >50 leads                 | UI caps selection at 50; server validates max         |
| No queue worker                  | Calls stay **pending** in admin + lead panel          |

---

## 6. Developer smoke test (CLI)

With Retell configured:

```powershell
php artisan voice:smoke-test --employee=1
php artisan voice:smoke-test 1 --employee=1 --call
```

**Expected:** Prints resolved lead, employee, provider; with `--call` queues one outbound call (needs queue worker for live dial).

---

## 7. Voice & AI admin read-only views

1. **Voice Platform → Calls** — all calls, detail shows status, numbers, duration, cost, summary
2. **AI Platform → Logs** — read-only (empty until AI gateway sends requests)

---

## 8. Permissions spot-check

Login as user **without** agent role:

| Route                              | Expected      |
| ---------------------------------- | ------------- |
| `/admin/ai/providers`              | 403 Forbidden |
| `/leads/{id}/voice-calls` POST     | 403 Forbidden |
| `/leads/voice-calls/bulk` POST     | 403 Forbidden |
| `/scraper`                         | 403 Forbidden |

Agent and super_admin: full access including bulk voice UI.

---

## 9. Automated QA (developer)

```powershell
cd d:\herd\testapp
php artisan test
npm run build
```

**Expected:** All tests pass; frontend builds without errors.

Targeted suites:

```powershell
php artisan test tests/Feature/BulkVoiceCallTest.php
php artisan test tests/Feature/InitiateVoiceCallTest.php
php artisan test tests/Feature/LeadWorkforceTest.php
php artisan test tests/Feature/PlatformReadinessTest.php
php artisan test tests/Feature/AiAdminTest.php
php artisan test tests/Feature/VoiceGatewayTest.php
php artisan test tests/Feature/VoiceAdminTest.php
```

---

## 10. What you still add (summary)

| Area       | You add later                                                     |
| ---------- | ----------------------------------------------------------------- |
| LLM        | API key on at least one AI provider (OpenAI recommended)          |
| Voice      | Retell API key + from number + agent ID on Alex                   |
| Scraping   | `INGEST_API_TOKEN` sync between Laravel & SRP                     |
| Optional   | Google Places, Foursquare, Reddit OAuth keys                      |
| Production | Retell webhook URL, queue worker as daemon (`voice,default`), scheduler |

Everything else — UI, seed data, permissions, pipeline, admin CRUD, single + bulk voice, async queue — is **ready to test**.
