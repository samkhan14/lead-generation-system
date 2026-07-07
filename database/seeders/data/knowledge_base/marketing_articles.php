<?php

return [
    [
        'slug' => 'digital-marketing-agency-overview',
        'name' => 'Digital Marketing Agency Overview',
        'category' => 'company',
        'tags' => ['marketing', 'agency', 'overview'],
        'content' => <<<'TEXT'
We also operate as a digital marketing agency for service businesses, local brands, and B2B companies in the United States, Canada, United Kingdom, Australia, and Europe.

Core marketing services (see service catalog for full detail):
- Search Engine Optimization (SEO) — organic traffic and leads that compound over time
- Local SEO & Google Business Profile — map pack visibility, citations, and review growth
- Google Ads & PPC — immediate, trackable paid-search leads with conversion tracking
- Social Media Marketing — content, community, and paid social on the right platforms
- Content Marketing — SEO-driven articles, lead magnets, and nurture content
- Email Marketing & Automation — lifecycle flows and segmented campaigns
- Conversion Rate Optimization (CRO) — turn existing traffic into more leads and sales
- Marketing Analytics & Reporting — GA4, attribution, and dashboards tied to revenue

We sell measurable outcomes — rankings, leads, cost-per-lead, and ROAS — not vanity metrics. Marketing retainers are scoped by competition, channels, and content volume; exact pricing requires discovery.
TEXT,
    ],
    [
        'slug' => 'marketing-sales-guidelines',
        'name' => 'Marketing Voice Sales Guidelines',
        'category' => 'policy',
        'tags' => ['marketing', 'voice', 'sales', 'calls'],
        'content' => <<<'TEXT'
Voice call rules for marketing sales agents (Maya):

- Introduce yourself as a digital marketing specialist — not a software developer.
- Confirm you are speaking with the right person and that it is an okay time.
- Use the lead pitch recommendation (service, reason, opener) when present — it is based on real lead signals.
- Ask one discovery question at a time from the recommended service catalog entry.
- Recommend at most one primary marketing service unless the lead asks for options.
- Tie recommendations to the lead's situation: no website → local SEO or web is out of scope for Maya (refer Alex); weak reviews → local SEO or social; agency directory lead → SEO or content; has website + phone → Google Ads or CRO.
- Never invent pricing, guaranteed rankings, ROAS, or timelines not in approved knowledge.
- For "SEO takes too long" — acknowledge and offer Google Ads for immediate leads while SEO compounds.
- For "we tried SEO before" — reference audit-first approach and lead/revenue reporting, not vanity rankings.
- End with a clear next step: free audit, discovery call, or send a one-page summary.
TEXT,
    ],
    [
        'slug' => 'marketing-pitch-selection',
        'name' => 'Marketing Pitch Selection Rules',
        'category' => 'policy',
        'tags' => ['marketing', 'pitches', 'sales'],
        'content' => <<<'TEXT'
When a lead pitch recommendation is provided in context, treat it as the primary outreach angle:

| Pitch type | When it applies | Primary service |
|------------|-----------------|-----------------|
| local_seo | Local directory source + has website | Local SEO & Google Business Profile |
| seo_growth | B2B/agency directory (Manifest, GoodFirms, DesignRush, UpCity) | Search Engine Optimization |
| google_ads | Has website and phone | Google Ads & PPC Management |
| reviews_growth | Local source + review count below threshold | Local SEO & Google Business Profile |
| reputation_repair | Rating below threshold | Local SEO & Google Business Profile |
| gbp_optimization | Google Maps source | Local SEO & Google Business Profile |
| social_media_growth | Local source + low review count | Social Media Marketing |
| content_marketing | Agency directory + has website | Content Marketing & Strategy |

If multiple pitches match, use the highest-priority one already ranked in the recommendation list. Use the provided opener as a starting point — adapt naturally to the conversation.

Non-marketing pitches (website build, software, automation) are handled by Alex — do not pitch custom development unless the lead asks; offer to connect them with the technical team.
TEXT,
    ],
    [
        'slug' => 'marketing-common-objections',
        'name' => 'Marketing Common Objections',
        'category' => 'faq',
        'tags' => ['marketing', 'objections', 'sales'],
        'content' => <<<'TEXT'
Approved responses for common marketing objections (supplement service-level objections in the catalog):

"We tried SEO and it didn't work."
→ Most failed SEO is unfocused with no revenue tie-in. We start with an audit, prioritize money keywords, and report against leads — not just rankings.

"Google Ads is too expensive."
→ We fix conversion tracking and account structure first so spend maps to cost-per-lead. We can model expected CPL during discovery before scaling.

"We don't have budget for marketing right now."
→ A phased approach works: start with one channel (often local SEO or a focused PPC test), prove ROI, then expand. One-time audits are also available.

"We do marketing in-house."
→ We can complement your team with specialized execution (technical SEO, paid search structure, content production) or provide an audit with a prioritized roadmap they can implement.

"How long until we see results?"
→ PPC can produce leads within days once tracking is live. SEO and content compound over 3–6 months. We report monthly so you see progress early.
TEXT,
    ],
    [
        'slug' => 'marketing-audit-and-discovery',
        'name' => 'Marketing Audit & Discovery Rules',
        'category' => 'policy',
        'tags' => ['marketing', 'discovery', 'audit'],
        'content' => <<<'TEXT'
Before quoting any marketing retainer, gather:

- Primary business goal: more leads, lower ad cost, local visibility, or brand awareness
- Current channels in use (organic, ads, social, email) and monthly spend if any
- Website URL and access to Google Analytics / Search Console if available
- Target locations and services/products to promote
- Main competitors the lead cares about
- CRM or how leads are tracked today
- Content capacity — can they approve articles, or do we produce everything?

Offer a marketing audit or discovery call as the default next step when information is missing. Match one primary service from the catalog; mention cross-sells (e.g., SEO + content, PPC + CRO) only when genuinely relevant.

Never quote a fixed monthly retainer dollar amount on a first call — reference pricing notes from the service catalog (scoped by competition, channels, and volume).
TEXT,
    ],
];
