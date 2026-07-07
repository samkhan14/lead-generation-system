<?php

return [
    [
        'slug' => 'company-overview',
        'name' => 'Company Overview',
        'category' => 'company',
        'tags' => ['company', 'overview', 'positioning'],
        'content' => <<<'TEXT'
We are a senior full-stack software engineering practice and digital marketing agency focused on scalable business growth — not template websites.

We build custom Laravel applications, SaaS products, CRMs, internal business systems, AI automations, and voice-enabled sales workflows for startups, SMBs, SaaS founders, and growing companies in the United States, Canada, United Kingdom, Australia, and Europe.

We also deliver digital marketing services: SEO, local SEO, Google Ads, social media, content marketing, email automation, CRO, and marketing analytics — with reporting tied to leads and revenue.

Our work combines strong backend engineering (Laravel, PHP, PostgreSQL/MySQL) with modern frontends (Vue.js, React, Inertia.js) and production DevOps (Docker, AWS, CI/CD).

We sell outcomes: faster operations, better lead handling, reliable internal tools, AI-assisted workflows, and measurable marketing ROI that teams can trust in production.
TEXT,
    ],
    [
        'slug' => 'engagement-process',
        'name' => 'Engagement Process',
        'category' => 'policy',
        'tags' => ['process', 'delivery', 'sales'],
        'content' => <<<'TEXT'
Standard engagement flow:

1. Discovery call — understand the business problem, existing systems, integrations, timeline, and constraints.
2. Technical scoping — architecture outline, risks, phased delivery plan, and quotation inputs.
3. Fixed-price or milestone proposal — no surprise billing; scope is documented before build starts.
4. Build sprint — Laravel/Vue implementation with tests, staging environment, and regular check-ins.
5. Launch — deployment, handoff documentation, and optional ongoing support or retainer.

We do not start production work without agreed scope. Small fixes and audits can be scoped separately as a first step.
TEXT,
    ],
    [
        'slug' => 'technical-stack',
        'name' => 'Technical Stack & Expertise',
        'category' => 'company',
        'tags' => ['laravel', 'php', 'vue', 'react', 'aws'],
        'content' => <<<'TEXT'
Core technologies we deliver with:

Backend: Laravel, PHP, REST APIs, queued jobs, event-driven workflows
Databases: MySQL, PostgreSQL
Frontend: Vue.js, React, Inertia.js
AI: OpenAI, Gemini, OpenRouter integrations via Laravel-owned gateways
Automation: n8n, workflow orchestration, webhooks
Infrastructure: Docker, AWS, CI/CD pipelines

We do not claim expertise outside this stack. If a client needs a technology we do not use, we say so clearly and recommend scoping a compatible approach or partner handoff.
TEXT,
    ],
    [
        'slug' => 'pricing-philosophy',
        'name' => 'Pricing Philosophy',
        'category' => 'pricing',
        'tags' => ['pricing', 'sales', 'policy'],
        'content' => <<<'TEXT'
We do not publish fixed rate cards or guaranteed prices on calls.

Pricing depends on scope: integrations, user roles, data migration, AI complexity, timeline, and whether the work is MVP or production-grade.

On sales conversations:
- Explain that accurate pricing requires discovery.
- Reference service-level pricing notes from the catalog (ranges and scoping factors only).
- Never invent a dollar amount not supported by approved business knowledge.
- Offer a discovery call or technical audit as the next step when budget is unclear.

Phased delivery and milestone-based proposals are available when budget is a concern.
TEXT,
    ],
    [
        'slug' => 'discovery-and-quotation-rules',
        'name' => 'Discovery & Quotation Rules',
        'category' => 'policy',
        'tags' => ['discovery', 'quotation', 'sales'],
        'content' => <<<'TEXT'
Before quoting custom software, gather:

- What problem are we solving and who uses the system?
- Existing systems, APIs, and data sources
- Required integrations (CRM, billing, telephony, AI providers)
- Number of users, roles, and permission model
- MVP vs production expectations
- Timeline and launch constraints
- Compliance, hosting, or regional requirements

If information is missing, schedule follow-up rather than guessing scope.

Match the lead to one primary service from the catalog, then mention related services only when genuinely relevant.
TEXT,
    ],
    [
        'slug' => 'voice-sales-guidelines',
        'name' => 'Voice Sales Guidelines',
        'category' => 'policy',
        'tags' => ['voice', 'sales', 'calls'],
        'content' => <<<'TEXT'
Voice call rules for AI sales agents:

- Introduce yourself and the company in one sentence.
- Confirm you are speaking with the right person and that it is an okay time.
- Ask one discovery question at a time; listen before pitching.
- Recommend at most one primary service unless the lead asks for options.
- Use discovery questions, FAQs, and objection responses from the service catalog.
- Never invent pricing, timelines, or deliverables not in approved knowledge.
- If unsure, offer a follow-up email or technical discovery call.
- Keep initial outreach concise; expand only when the lead is engaged.
- End with a clear next step: book a call, send summary, or respectfully close.
TEXT,
    ],
    [
        'slug' => 'ai-automation-delivery',
        'name' => 'AI Automation Delivery Approach',
        'category' => 'services',
        'tags' => ['ai', 'automation', 'delivery'],
        'content' => <<<'TEXT'
Our AI automations are built as production Laravel systems — not demo chatbots.

We implement:
- LLM calls through controlled gateways with logging, cost tracking, and fallbacks
- Structured outputs with validation and human-in-the-loop approvals where needed
- Service catalog and knowledge-base driven prompts (no hardcoded sales scripts)
- Voice agents via Retell and related providers with dynamic variables from CRM context

AI features ship with monitoring, error handling, and the ability to change providers without rewriting business logic.
TEXT,
    ],
    [
        'slug' => 'proposal-writing-guidelines',
        'name' => 'Proposal Writing Guidelines',
        'category' => 'policy',
        'tags' => ['proposal', 'email', 'sales'],
        'content' => <<<'TEXT'
When drafting proposals or follow-up emails:

- Restate the client's problem in their language.
- Recommend one primary service with scope, deliverables, and timeline from the catalog.
- List assumptions and what is out of scope.
- Include pricing notes as ranges or scoping factors — not fabricated exact prices unless pre-approved.
- Mention next step: discovery workshop, audit, or milestone 1 kickoff.
- Tone: professional, direct, senior engineer — not agency fluff.
TEXT,
    ],
    [
        'slug' => 'security-and-data-handling',
        'name' => 'Security & Data Handling',
        'category' => 'policy',
        'tags' => ['security', 'compliance', 'trust'],
        'content' => <<<'TEXT'
We treat client data and credentials seriously:

- API keys and provider credentials are stored in application databases with appropriate access controls — not shared in chat logs.
- We can work under mutual NDA for sensitive integrations.
- Production deployments use environment separation (staging/production), HTTPS, and least-privilege access where applicable.
- AI prompts use business-approved knowledge only; do not paste confidential client data into unapproved third-party tools.

For regulated industries, discuss compliance requirements during discovery before quoting.
TEXT,
    ],
    [
        'slug' => 'supported-clients-and-regions',
        'name' => 'Supported Clients & Regions',
        'category' => 'faq',
        'tags' => ['clients', 'regions', 'icp'],
        'content' => <<<'TEXT'
Best-fit clients:
- Startups and SaaS founders building MVPs or scaling products
- SMBs replacing spreadsheets and manual workflows
- Agencies needing a senior Laravel partner for client builds
- Growing companies modernizing legacy PHP or monolithic systems

Primary regions: United States, Canada, United Kingdom, Australia, and Europe.

We are a strong fit when the client needs custom business software, CRM/workflow systems, AI automation, or voice-enabled sales — not a cheap template website.
TEXT,
    ],
];
