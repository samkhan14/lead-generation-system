<?php

namespace Database\Seeders;

use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');

        $definitions = [
            // ── Websites (entry → premium) ──────────────────────────────────
            [
                'slug' => 'basic-website',
                'name' => 'Basic Website',
                'description' => 'An affordable starter website for small businesses that need a clean online presence — homepage, about, services, and contact — without custom engineering.',
                'features' => [
                    'Up to 5 pages',
                    'Mobile-responsive layout',
                    'Contact form with email notifications',
                    'Basic on-page SEO',
                    'SSL and launch support',
                ],
                'benefits' => [
                    'Professional presence without enterprise budget',
                    'Live quickly with a clear upgrade path',
                    'Own your domain and content from day one',
                ],
                'deliverables' => [
                    'Homepage, about, services, contact pages',
                    'Contact form setup',
                    'Launch checklist',
                ],
                'pricing_notes' => 'Typically $800–$2,000 depending on pages and content support. Ideal first step before a full business site.',
                'faqs' => [
                    ['question' => 'Can we upgrade later?', 'answer' => 'Yes — most clients move to Business or Premium Website as they grow.'],
                    ['question' => 'Do you provide hosting?', 'answer' => 'We can recommend and set up hosting, or deploy to your existing provider.'],
                ],
                'objections' => [
                    ['objection' => 'We only need something simple.', 'response' => 'That is exactly what this package is for — no over-engineering.'],
                    ['objection' => 'We might need e-commerce later.', 'response' => 'We build with upgrade paths to our E-commerce Store package.'],
                ],
                'tags' => ['web', 'starter', 'small-business'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['website-maintenance', 'landing-page'],
                'upsell_slugs' => ['business-website', 'premium-website'],
            ],
            [
                'slug' => 'business-website',
                'name' => 'Business Website',
                'description' => 'A full business website with structured service pages, trust sections, lead capture, analytics, and room to scale — the standard package for growing companies.',
                'features' => [
                    'Custom design aligned to your brand',
                    'Multiple service and location pages',
                    'Lead forms and CRM/email integrations',
                    'Blog or resources section (optional)',
                    'Analytics, Search Console, and speed optimization',
                ],
                'benefits' => [
                    'Convert visitors into qualified leads',
                    'Scale content as services expand',
                    'Strong foundation for SEO and paid ads',
                ],
                'deliverables' => [
                    'Discovery workshop and sitemap',
                    'Designed page templates',
                    'CMS or editable content handoff',
                    'Training and documentation',
                ],
                'pricing_notes' => 'Typically $3,000–$8,000 based on page count, copy, and integrations.',
                'faqs' => [
                    ['question' => 'Which CMS do you use?', 'answer' => 'WordPress, Laravel, or headless — we match the stack to your needs.'],
                ],
                'objections' => [
                    ['objection' => 'Basic website is enough for now.', 'response' => 'Business Website adds lead capture, SEO structure, and pages that support sales — not just presence.'],
                ],
                'tags' => ['web', 'business', 'lead-generation'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['website-maintenance', 'ui-ux-design'],
                'upsell_slugs' => ['premium-website', 'ecommerce-store'],
            ],
            [
                'slug' => 'premium-website',
                'name' => 'Premium Website',
                'description' => 'Top-tier custom websites with advanced design, animations, performance tuning, and conversion optimization — for brands that need to stand out and win high-value clients.',
                'features' => [
                    'Fully custom UI/UX design',
                    'Advanced animations and interactions',
                    'Core Web Vitals and performance optimization',
                    'A/B-ready landing sections',
                    'Multi-language or multi-location support (optional)',
                    'Headless or modern stack options',
                ],
                'benefits' => [
                    'Premium brand perception',
                    'Faster, more engaging user experience',
                    'Built to support enterprise marketing and sales',
                ],
                'deliverables' => [
                    'Design system and component library',
                    'Fully built and tested site',
                    'Performance audit report',
                    'Post-launch optimization sprint',
                ],
                'pricing_notes' => 'Typically $10,000–$35,000+ depending on scope, integrations, and content volume.',
                'faqs' => [
                    ['question' => 'How is this different from Business Website?', 'answer' => 'Premium includes custom design systems, advanced interactions, and performance/conversion work — not template-level builds.'],
                ],
                'objections' => [
                    ['objection' => 'That budget feels high.', 'response' => 'We can phase design and launch, or start with key pages and expand.'],
                ],
                'tags' => ['web', 'premium', 'custom-design', 'performance'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['ui-ux-design', 'website-maintenance'],
                'upsell_slugs' => ['web-application', 'ai-automation'],
            ],
            [
                'slug' => 'landing-page',
                'name' => 'Landing Page',
                'description' => 'A focused single-page build for campaigns, product launches, or offers — fast turnaround with conversion-first layout.',
                'features' => [
                    'Single high-converting page',
                    'Mobile-first design',
                    'Form or click-to-call CTA',
                    'Campaign tracking setup',
                ],
                'benefits' => ['Live in days', 'Lower entry cost', 'Credit toward full website packages'],
                'deliverables' => ['One landing page', 'Copy structure workshop', 'Launch checklist'],
                'pricing_notes' => '$900–$2,500. Credit toward Business or Premium Website within 90 days.',
                'faqs' => [],
                'objections' => [
                    ['objection' => 'We need a full site.', 'response' => 'Landing page gets you live now; we can run full build in parallel.'],
                ],
                'tags' => ['web', 'landing-page', 'campaigns'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['basic-website'],
                'upsell_slugs' => ['business-website', 'premium-website'],
            ],
            [
                'slug' => 'website-redesign',
                'name' => 'Website Redesign & Modernization',
                'description' => 'Modernize outdated websites — new design, improved speed, mobile fixes, and conversion improvements while preserving SEO equity where possible.',
                'features' => [
                    'UX and content audit',
                    'Design refresh or full redesign',
                    'Migration and redirect planning',
                    'Speed and mobile optimization',
                ],
                'benefits' => [
                    'Fix credibility and performance issues',
                    'Improve conversions without starting from zero',
                    'Protect search rankings during migration',
                ],
                'deliverables' => ['Audit report', 'Redesign and rebuild', 'Redirect map', 'Post-launch monitoring'],
                'pricing_notes' => '$4,000–$20,000 depending on site size and technical debt.',
                'faqs' => [
                    ['question' => 'Will we lose SEO rankings?', 'answer' => 'We plan redirects and preserve URL structure where possible.'],
                ],
                'objections' => [
                    ['objection' => 'Our current site works.', 'response' => 'We benchmark speed, mobile UX, and conversion — most legacy sites leak leads silently.'],
                ],
                'tags' => ['web', 'redesign', 'migration'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['website-maintenance', 'ui-ux-design'],
                'upsell_slugs' => ['premium-website', 'web-application'],
            ],

            // ── E-commerce ───────────────────────────────────────────────────
            [
                'slug' => 'ecommerce-store',
                'name' => 'E-commerce Store',
                'description' => 'Online stores for product sales — Shopify, WooCommerce, or custom Laravel/React commerce with payments, inventory, shipping, and admin dashboards.',
                'features' => [
                    'Product catalog and categories',
                    'Cart, checkout, and payment gateways',
                    'Order management and customer accounts',
                    'Shipping and tax configuration',
                    'Admin dashboard for products and orders',
                ],
                'benefits' => [
                    'Sell 24/7 with automated checkout',
                    'Own customer data and repeat purchase flows',
                    'Scale from dozens to thousands of SKUs',
                ],
                'deliverables' => [
                    'Store setup and theme/custom build',
                    'Payment and shipping integration',
                    'Admin training',
                    'Launch and QA checklist',
                ],
                'pricing_notes' => '$5,000–$25,000+ for custom builds; Shopify/WooCommerce from $4,000 depending on catalog size and integrations.',
                'faqs' => [
                    ['question' => 'Shopify or custom?', 'answer' => 'Shopify for speed to market; custom when you need unique workflows, B2B pricing, or deep integrations.'],
                    ['question' => 'Do you handle product uploads?', 'answer' => 'Yes — bulk import and category setup available as add-on.'],
                ],
                'objections' => [
                    ['objection' => 'We sell on marketplaces already.', 'response' => 'Your own store keeps margins and customer relationships — marketplaces can run alongside.'],
                ],
                'tags' => ['ecommerce', 'shopify', 'woocommerce', 'payments'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['website-maintenance', 'api-integrations'],
                'upsell_slugs' => ['web-application', 'ai-automation'],
            ],

            // ── Applications ─────────────────────────────────────────────────
            [
                'slug' => 'web-application',
                'name' => 'Web Application',
                'description' => 'Custom web apps — SaaS platforms, client portals, internal tools, dashboards, and multi-tenant systems built with Laravel, Vue/React, and scalable architecture.',
                'features' => [
                    'Custom business logic and workflows',
                    'Role-based access and authentication',
                    'API-first architecture',
                    'Admin panels and reporting',
                    'Queue, notification, and integration layers',
                ],
                'benefits' => [
                    'Software tailored to how you operate',
                    'Replace spreadsheets and manual processes',
                    'Foundation for mobile apps and AI layers',
                ],
                'deliverables' => [
                    'Requirements and architecture document',
                    'MVP or phased release plan',
                    'Deployed application with documentation',
                    'Ongoing support options',
                ],
                'pricing_notes' => 'Project-based from $15,000; complex SaaS $50,000+. Discovery sprint quoted separately.',
                'faqs' => [
                    ['question' => 'Do you build MVPs?', 'answer' => 'Yes — we phase features so you validate before full build.'],
                ],
                'objections' => [
                    ['objection' => 'Off-the-shelf software is cheaper.', 'response' => 'Custom apps win when workflows, integrations, or scale do not fit boxed products.'],
                ],
                'tags' => ['web-app', 'saas', 'laravel', 'custom-software'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['api-integrations', 'ui-ux-design'],
                'upsell_slugs' => ['mobile-application', 'crm-development', 'ai-automation'],
            ],
            [
                'slug' => 'mobile-application',
                'name' => 'Mobile Application',
                'description' => 'Native and cross-platform mobile apps for iOS and Android — customer apps, field tools, and companions to your web platform.',
                'features' => [
                    'React Native, Flutter, or native builds',
                    'Push notifications',
                    'Offline-capable flows (where needed)',
                    'App Store and Play Store submission support',
                    'API integration with existing systems',
                ],
                'benefits' => [
                    'Reach users on the device they use daily',
                    'Extend web products to mobile',
                    'Branded experience outside the browser',
                ],
                'deliverables' => [
                    'UI/UX for mobile',
                    'Built and tested app',
                    'Store listing assets guidance',
                    'Release and update process',
                ],
                'pricing_notes' => '$20,000–$80,000+ depending on platforms, features, and backend complexity.',
                'faqs' => [
                    ['question' => 'Do we need a web app first?', 'answer' => 'Often yes for admin and API — we can build both in one program.'],
                ],
                'objections' => [
                    ['objection' => 'A responsive website is enough.', 'response' => 'Apps win for retention, push alerts, and field workflows — we advise based on use case.'],
                ],
                'tags' => ['mobile', 'ios', 'android', 'react-native'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['web-application', 'api-integrations'],
                'upsell_slugs' => ['ai-automation', 'ai-workforce-platform'],
            ],

            // ── CRM & business systems ───────────────────────────────────────
            [
                'slug' => 'crm-development',
                'name' => 'Custom CRM Development',
                'description' => 'CRM systems built around your sales process — lead management, pipelines, assignments, reporting, and integrations instead of forcing your team into generic tools.',
                'features' => [
                    'Lead and contact management',
                    'Pipeline stages and automation rules',
                    'Role-based permissions',
                    'Activity timeline and notes',
                    'Integrations (email, telephony, ads, scrapers)',
                ],
                'benefits' => [
                    'CRM that matches your workflow',
                    'No per-seat SaaS limits for custom deployments',
                    'Connect lead sources and AI employees in one system',
                ],
                'deliverables' => [
                    'Process mapping workshop',
                    'Custom CRM build or extension',
                    'Data migration plan',
                    'Team training',
                ],
                'pricing_notes' => '$12,000–$60,000+ depending on modules, integrations, and AI layers.',
                'faqs' => [
                    ['question' => 'Can you extend our existing CRM?', 'answer' => 'Yes — or we build on Laravel with modules you own outright.'],
                ],
                'objections' => [
                    ['objection' => 'HubSpot/Salesforce already exist.', 'response' => 'Custom CRM wins when lead sources, scoring, or AI workflows need deep control.'],
                ],
                'tags' => ['crm', 'sales', 'pipeline', 'custom-software'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['lead-intelligence-crm', 'api-integrations'],
                'upsell_slugs' => ['ai-automation', 'ai-workforce-platform'],
            ],
            [
                'slug' => 'lead-intelligence-crm',
                'name' => 'Lead Intelligence CRM',
                'description' => 'Our lead intelligence platform — multi-source lead capture, scoring, verification, pitch recommendations, and scraper pipelines for agencies and sales teams.',
                'features' => [
                    'Google Maps, directories, and social lead ingestion',
                    'Automated scoring and deduplication',
                    'Lead verification and website analysis',
                    'Pitch recommendations per lead',
                    'Role-based team access',
                ],
                'benefits' => [
                    'Qualified leads instead of raw lists',
                    'One system from discovery to outreach',
                    'Ready for AI voice and email employees',
                ],
                'deliverables' => [
                    'Platform setup and configuration',
                    'Scraper and source configuration',
                    'Team onboarding',
                    'Optional custom integrations',
                ],
                'pricing_notes' => 'Setup from $5,000; hosting and maintenance on retainer. Custom modules quoted separately.',
                'faqs' => [
                    ['question' => 'Is this the same as custom CRM?', 'answer' => 'Lead Intelligence CRM is our productized platform; Custom CRM is built entirely to your spec.'],
                ],
                'objections' => [
                    ['objection' => 'We already buy lead lists.', 'response' => 'We score, verify, and dedupe — so your team works hot leads, not spreadsheets.'],
                ],
                'tags' => ['crm', 'lead-intelligence', 'scraper', 'product'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['ai-workforce-platform', 'ai-automation'],
                'upsell_slugs' => ['ai-workforce-platform', 'crm-development'],
            ],

            // ── AI & automation ──────────────────────────────────────────────
            [
                'slug' => 'ai-automation',
                'name' => 'AI Automation & Workflows',
                'description' => 'Practical AI automations — document processing, lead routing, email drafts, data enrichment, chatbots, and workflow bots connected to your CRM, website, or internal tools.',
                'features' => [
                    'Workflow design and implementation',
                    'LLM integrations with guardrails',
                    'CRM and webhook automations',
                    'Human-in-the-loop approval steps',
                    'Logging, monitoring, and cost controls',
                ],
                'benefits' => [
                    'Reduce repetitive manual work',
                    'Faster response times on leads and support',
                    'Scale operations without linear headcount',
                ],
                'deliverables' => [
                    'Automation audit and roadmap',
                    'Built workflows with documentation',
                    'Monitoring dashboard',
                    'Runbook for your team',
                ],
                'pricing_notes' => '$3,000–$25,000 per automation program; ongoing model/API costs billed separately or pass-through.',
                'faqs' => [
                    ['question' => 'Which AI providers do you use?', 'answer' => 'OpenAI, Anthropic, Gemini, and others — selected per task and budget.'],
                ],
                'objections' => [
                    ['objection' => 'AI is hype for our business.', 'response' => 'We start with one measurable workflow — lead follow-up, intake, or support — not experiments.'],
                ],
                'tags' => ['ai', 'automation', 'workflows', 'llm'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['crm-development', 'api-integrations'],
                'upsell_slugs' => ['ai-workforce-platform'],
            ],
            [
                'slug' => 'ai-workforce-platform',
                'name' => 'AI Workforce Platform',
                'description' => 'Deploy AI employees — voice sales agents, email agents, appointment setters, and support bots — with prompts, memory, provider management, and full activity logging on top of your CRM.',
                'features' => [
                    'Configurable AI employees with roles and permissions',
                    'Voice outbound/inbound (Retell, LiveKit, and others)',
                    'Conversation transcripts and outcome tracking',
                    'Service catalog and knowledge base integration',
                    'Cost and performance logging per employee',
                ],
                'benefits' => [
                    'AI that behaves like trained staff — not generic chatbots',
                    'Works from qualified CRM leads',
                    'Scales outreach without losing control',
                ],
                'deliverables' => [
                    'AI employee configuration',
                    'Voice and/or text channel setup',
                    'CRM integration and webhooks',
                    'Playbooks and escalation rules',
                ],
                'pricing_notes' => 'Platform setup from $8,000; per-employee configuration and usage (voice minutes, tokens) additional.',
                'faqs' => [
                    ['question' => 'Does it replace our sales team?', 'answer' => 'It handles first touch, qualification, and booking — humans close complex deals.'],
                ],
                'objections' => [
                    ['objection' => 'We are not ready for AI calling.', 'response' => 'We can start with email or chat employees, then add voice when you are comfortable.'],
                ],
                'tags' => ['ai', 'voice', 'sales-agent', 'workforce'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['lead-intelligence-crm', 'ai-automation'],
                'upsell_slugs' => ['crm-development'],
            ],

            // ── Supporting services ──────────────────────────────────────────
            [
                'slug' => 'ui-ux-design',
                'name' => 'UI/UX Design',
                'description' => 'User research, wireframes, and high-fidelity design for websites, web apps, and mobile products — standalone or as part of a build project.',
                'features' => [
                    'User flows and wireframes',
                    'Visual design and design systems',
                    'Prototype for stakeholder review',
                    'Developer-ready handoff',
                ],
                'benefits' => ['Reduce rebuild cost', 'Higher conversion before code starts', 'Consistent brand across products'],
                'deliverables' => ['Figma files', 'Component specs', 'Usability notes'],
                'pricing_notes' => '$2,000–$15,000 depending on screens and research depth.',
                'faqs' => [],
                'objections' => [
                    ['objection' => 'Our developer can design it.', 'response' => 'Dedicated UX prevents costly rework and improves conversion measurably.'],
                ],
                'tags' => ['design', 'ui', 'ux', 'figma'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['premium-website', 'web-application'],
                'upsell_slugs' => ['premium-website', 'web-application', 'mobile-application'],
            ],
            [
                'slug' => 'api-integrations',
                'name' => 'API & System Integrations',
                'description' => 'Connect your website, CRM, e-commerce, payment, marketing, and third-party tools — Zapier replacements, custom APIs, webhooks, and sync jobs.',
                'features' => [
                    'REST and webhook integrations',
                    'Payment, email, SMS, and ads platform hooks',
                    'Data sync and error handling',
                    'Documentation and monitoring',
                ],
                'benefits' => [
                    'Eliminate manual data entry',
                    'Reliable systems that work together',
                    'Foundation for AI and automation layers',
                ],
                'deliverables' => ['Integration spec', 'Built connectors', 'Monitoring and alerts'],
                'pricing_notes' => '$1,500–$12,000 per integration scope; retainers for ongoing sync maintenance.',
                'faqs' => [],
                'objections' => [
                    ['objection' => 'Zapier is easier.', 'response' => 'Custom integrations handle volume, security, and logic Zapier cannot.'],
                ],
                'tags' => ['integrations', 'api', 'webhooks', 'automation'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => ['ai-automation'],
                'upsell_slugs' => ['web-application', 'crm-development'],
            ],
            [
                'slug' => 'website-maintenance',
                'name' => 'Website Maintenance & Support',
                'description' => 'Ongoing updates, security patches, backups, uptime monitoring, and small content changes for websites, stores, and applications we build or inherit.',
                'features' => [
                    'Security and dependency updates',
                    'Backups and uptime monitoring',
                    'Small content and bug fixes',
                    'Priority support channel',
                ],
                'benefits' => [
                    'Avoid downtime and hacked sites',
                    'Free your team from technical chores',
                    'Keep performance and SSL current',
                ],
                'deliverables' => ['Monthly maintenance report', 'Change log', 'Incident response'],
                'pricing_notes' => '$150–$800/month depending on stack, traffic, and SLA.',
                'faqs' => [
                    ['question' => 'Do you maintain sites you did not build?', 'answer' => 'Yes — after a technical audit and onboarding sprint.'],
                ],
                'objections' => [
                    ['objection' => 'We only call when something breaks.', 'response' => 'Preventive maintenance is cheaper than emergency fixes and SEO damage.'],
                ],
                'tags' => ['maintenance', 'support', 'hosting'],
                'status' => ServiceStatus::Active,
                'cross_sell_slugs' => [],
                'upsell_slugs' => ['website-redesign', 'premium-website'],
            ],
        ];

        $activeSlugs = collect($definitions)->pluck('slug')->all();

        foreach ($definitions as $definition) {
            $payload = $definition;
            unset($payload['cross_sell_slugs'], $payload['upsell_slugs']);

            Service::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    ...$payload,
                    'cross_sell_ids' => [],
                    'upsell_ids' => [],
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ],
            );
        }

        $servicesBySlug = Service::query()->pluck('id', 'slug');

        foreach ($definitions as $definition) {
            $crossSellIds = collect($definition['cross_sell_slugs'] ?? [])
                ->map(fn (string $slug) => $servicesBySlug[$slug] ?? null)
                ->filter()
                ->values()
                ->all();

            $upsellIds = collect($definition['upsell_slugs'] ?? [])
                ->map(fn (string $slug) => $servicesBySlug[$slug] ?? null)
                ->filter()
                ->values()
                ->all();

            Service::query()
                ->where('slug', $definition['slug'])
                ->update([
                    'cross_sell_ids' => $crossSellIds,
                    'upsell_ids' => $upsellIds,
                ]);
        }

        Service::query()
            ->whereNotIn('slug', $activeSlugs)
            ->where('status', '!=', ServiceStatus::Archived)
            ->update(['status' => ServiceStatus::Archived]);
    }
}
