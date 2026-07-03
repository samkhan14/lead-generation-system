# Manual QA Checklist

Use this after login to verify **Lead Generation** and **AI Workforce** end-to-end.  
Automated tests: `php artisan test` (208+ tests).

---

## 0. Before you start

### Start services


| Service        | Command                                  | Required for                     |
| -------------- | ---------------------------------------- | -------------------------------- |
| Laravel (Herd) | Site already running at your `.test` URL | Everything                       |
| Queue worker   | `php artisan queue:work`                 | Scrape jobs, lead verification   |
| SRP service    | `npm run dev` in `srp-service` folder    | Live scraping + website analysis |


### Login credentials


| Email                    | Password   | Role                             |
| ------------------------ | ---------- | -------------------------------- |
| `agent@example.com`      | `password` | Agent (all permissions)          |
| `superadmin@example.com` | `password` | Super admin (bypasses all gates) |


### Fresh seed (optional)

```powershell
cd d:\herd\testapp
php artisan migrate:fresh --seed
```

---

## 1. Keys & credentials matrix

Everything below is **ready in the app** except the items marked **YOU ADD**.

### Lead generation


| Item                      | Env / config                                                                    | Required?           | Without it                                                                                     |
| ------------------------- | ------------------------------------------------------------------------------- | ------------------- | ---------------------------------------------------------------------------------------------- |
| SRP service URL           | `SRP_SERVICE_URL=http://localhost:3100`                                         | Yes for scraping    | Scrape jobs fail immediately                                                                   |
| Ingest token              | `INGEST_API_TOKEN` (Laravel + SRP `.env`)                                       | Yes for scraping    | Ingest/callbacks return 401                                                                    |
| Google Places fallback    | `GOOGLE_PLACES_API_KEY` (SRP `.env`)                                            | No                  | Playwright-only; fallback if <3 results fails silently                                         |
| Foursquare channel        | `FOURSQUARE_API_KEY` (SRP `.env`)                                               | Only for Foursquare | Foursquare jobs return 0 results                                                               |
| Reddit OAuth              | `REDDIT_CLIENT_ID`, `REDDIT_SECRET`, `REDDIT_USERNAME`, `REDDIT_PASSWORD` (SRP) | Only for Reddit     | Channel disabled in CRM by default                                                             |
| Contact verification gate | `LEAD_`* in `config/lead_quality.php`                                           | Optional            | If `require_email` + `require_phone` true, directory leads without both are rejected at ingest |


### AI workforce


| Item                          | Where to add                                                  | Required?            | Without it                                                                    |
| ----------------------------- | ------------------------------------------------------------- | -------------------- | ----------------------------------------------------------------------------- |
| OpenAI (or other LLM) API key | Admin → AI → Providers → Edit OpenAI → paste key              | For text AI calls    | Provider shows **Active** but **API key: Not set**; `AiGateway` fails on send |
| Retell API key                | Admin → Voice → Providers → Retell → Edit                     | For live voice calls | Provider **Active**, key **Not set**; lead panel shows blocker                |
| Retell from number            | Retell provider metadata `default_from_number`                | For voice calls      | Call fails: "requires a from_number"                                          |
| Retell agent ID               | Alex employee `voice_id` OR Retell metadata `agent_id`        | For voice calls      | Call may start without override agent (Retell default)                        |
| Voice webhook                 | Retell dashboard → `POST {APP_URL}/api/voice/webhooks/retell` | For status updates   | Calls stay queued; manual sync only                                           |


**Expected UI when keys are missing (this is correct):**

- Admin provider pages: Status = **active**, API key = **Not set** (red).
- Lead detail → AI Workforce panel: blocker **"No active voice provider with API credentials."**
- Start Voice Call button: **hidden** until Retell API key is saved.

---

## 2. Lead generation — manual steps

### 2.1 Dashboard

1. Login as `agent@example.com`
2. Go to `/dashboard`

**Expected:** Dashboard loads, sidebar shows Leads, Scraper, Services, AI Platform, Voice Platform.

---

### 2.2 Manual lead create

1. Go to **Leads** → **Create lead**
2. Fill: Company, First name, **Email**, **Phone**, Website (optional), Source = Manual
3. Submit

**Expected:**

- Redirect to lead detail page
- Score card shows Intent / Opportunity / Authenticity + temperature (HOT/WARM/COLD)
- "What to pitch" section visible

**If email or phone missing:** Validation error (required fields).

---

### 2.3 Leads list & filters

1. Go to **Leads**
2. Filter by source, temperature, search

**Expected:** Created lead appears; filters narrow results; no errors.

---

### 2.4 Scraper — Google Maps (primary channel)

1. Ensure SRP + queue worker running
2. Go to **Scraper**
3. Lead source: **Google Maps**
4. Keyword: `dentist`, Country: `United States`, City: `New York`, Max results: `5`
5. Click Run

**Expected:**

- Job appears in history with status **pending** → **running**
- Progress polls every ~3s
- On completion: popup with found / created / duplicate / failed counts
- Job detail page shows run logs (info/success/warning lines)
- New leads appear under **Leads** with source `google_maps`

**Without SRP running:** Job moves to **failed** with error about connection.

**Without queue worker:** Job stays **pending**.

---

### 2.5 Scraper — other directory channels

Repeat 2.4 for each channel you use:


| Channel                                       | Notes                                                                        |
| --------------------------------------------- | ---------------------------------------------------------------------------- |
| Yelp, Bing, OSM, Hotfrog, Yellow Pages, Manta | Location required; Playwright in SRP                                         |
| Foursquare                                    | Needs `FOURSQUARE_API_KEY` in SRP                                            |
| The Manifest, GoodFirms, DesignRush, UpCity   | Agency directories; location required                                        |
| Reddit                                        | Disabled in CRM config; enable in `config/scraper.php` + Reddit OAuth in SRP |


**Expected per channel:** Same job lifecycle as Google Maps; leads tagged with correct `source` and metadata dedupe key.

---

### 2.6 Lead detail — verification

1. Open a lead with a website
2. Wait for queue (or click **Re-verify** if shown)

**Expected:**

- Verification badge updates (pending → partial / fully verified)
- Website analysis panel may show tech stack, revamp score (requires SRP `/analyze-website`)

**Without SRP:** Verification layers that need SRP stay pending or skip gracefully.

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

## 3. AI workforce — manual steps

### 3.1 Services catalog (Business Knowledge)

1. Go to **Services** (`/admin/services`)

**Expected:** Multiple active services (Laravel dev, SEO, AI Workforce, etc.) with rich fields (ICP, FAQs, objections).

---

### 3.2 AI providers

1. Go to **AI Platform → Providers**

**Expected:**


| Provider                                    | Status | API key |
| ------------------------------------------- | ------ | ------- |
| OpenAI, Anthropic, Gemini, OpenRouter, Grok | active | Not set |


1. Open **OpenAI** detail → Edit → paste key → Save

**Expected:** `has_api_key` shows **Configured**; raw key never shown again.

---

### 3.3 AI models

1. Go to **AI Platform → Models**

**Expected:** Models listed per provider (GPT-4o, GPT-4o Mini, Claude, etc.), status **active**.

---

### 3.4 AI employees

1. Go to **AI Platform → Employees**
2. Open **Alex — Voice Sales Agent**

**Expected:**

- Status: **active**
- Role: Voice Sales
- Knowledge sources: services, knowledge_bases, lead
- Provider/model: OpenAI / GPT-4o Mini (linked)
- Voice ID: empty until you set Retell agent ID

---

### 3.5 Prompt templates & knowledge

1. **AI Platform → Prompts** — 3 active templates (voice sales system, behavior, opening)
2. **AI Platform → Knowledge** — 3 active entries (company overview, voice guidelines, objections)

**Expected:** All status **active**; content visible on show pages.

---

### 3.6 Voice providers

1. Go to **Voice Platform → Providers**

**Expected:**


| Provider  | Status | API key | Priority |
| --------- | ------ | ------- | -------- |
| Retell AI | active | Not set | 10       |
| LiveKit   | active | Not set | 20       |
| Vapi      | active | Not set | 30       |


1. Edit **Retell** → add API key, set metadata:
  - `default_from_number`: e.g. `+14155550100`
  - `agent_id`: your Retell agent ID (optional if set on Alex)

---

### 3.7 Lead detail — AI workforce panel

1. Open a lead **with phone number**
2. Scroll to **AI workforce** section

**Before keys:**

**Expected:**

- Tabs: AI Activities, Voice Calls, Timeline, Costs
- Blocker: **No active voice provider with API credentials.**
- **Start AI Voice Call** button hidden

**After Retell key + from number configured:**

**Expected:**

- Blocker gone
- **Start AI Voice Call** button visible
- Employee picker if multiple voice agents

1. Click **Start AI Voice Call**

**Expected:**

- Redirect back to lead detail
- Flash: "Voice call started..."
- **Voice Calls** tab shows new row (status: queued → ringing → in_progress → completed)
- **Timeline** merges AI + voice events
- **Costs** tab updates when call completes

**Without phone on lead:** Blocker **Lead has no phone number.**

---

### 3.8 Voice calls admin

1. Go to **Voice Platform → Calls**

**Expected:** List of all calls; detail page shows status, numbers, duration, cost, summary.

---

### 3.9 AI logs admin

1. Go to **AI Platform → Logs**

**Expected:** Read-only list (empty until AI gateway sends requests).

---

## 4. Permissions spot-check

Login as a user **without** agent role (create test user with no permissions):


| Route                          | Expected      |
| ------------------------------ | ------------- |
| `/admin/ai/providers`          | 403 Forbidden |
| `/leads/{id}/voice-calls` POST | 403 Forbidden |
| `/scraper`                     | 403 Forbidden |


Agent and super_admin: full access.

---

## 5. Quick automated QA (developer)

```powershell
cd d:\herd\testapp
php artisan test
npm run build
```

**Expected:** All tests pass; frontend builds without errors.

Targeted AI/workforce tests:

```powershell
php artisan test tests/Feature/AiAdminTest.php
php artisan test tests/Feature/AiGatewayTest.php
php artisan test tests/Feature/VoiceGatewayTest.php
php artisan test tests/Feature/VoiceAdminTest.php
php artisan test tests/Feature/LeadWorkforceTest.php
php artisan test tests/Feature/PlatformReadinessTest.php
```

---

## 6. What you still add (summary)


| Area       | You add later                                                     |
| ---------- | ----------------------------------------------------------------- |
| LLM        | API key on at least one AI provider (OpenAI recommended)          |
| Voice      | Retell API key + from number + agent ID                           |
| Scraping   | `INGEST_API_TOKEN` sync between Laravel & SRP                     |
| Optional   | Google Places, Foursquare, Reddit OAuth keys                      |
| Production | Retell webhook URL, queue worker as daemon, scheduler for watches |


Everything else — UI, seed data, permissions, pipeline, admin CRUD, lead panel — is **ready**.