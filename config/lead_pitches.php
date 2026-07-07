<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pitch types (used for filtering + recommendations)
    |--------------------------------------------------------------------------
    */
    'types' => [
        'website_build' => [
            'label' => 'Website build',
            'service' => 'Website or landing page build',
            'priority' => 'high',
            'reason' => 'No website was found, but the business is active enough to appear on Google Maps.',
            'opener' => 'I noticed your business is visible on Google, but I could not find a website. We can build a simple conversion-focused site so customers can view services and contact you directly.',
        ],
        'website_audit' => [
            'label' => 'Website audit',
            'service' => 'Website audit and conversion optimization',
            'priority' => 'medium',
            'reason' => 'A website exists, so the best first pitch is improving speed, trust, conversion, and local search visibility.',
            'opener' => 'I found your website from your Google listing. We can audit it for speed, mobile experience, local SEO, and conversion gaps.',
        ],
        'reviews_growth' => [
            'label' => 'Reviews growth',
            'service' => 'Google reviews and reputation growth',
            'priority' => 'high',
            'reason' => 'Review count is low, which leaves room to build trust and win more local searches.',
            'opener' => 'Your Google profile is visible, but the review count can be stronger. We can help create a simple review growth system after customer visits.',
        ],
        'reputation_repair' => [
            'label' => 'Reputation repair',
            'service' => 'Reputation repair and customer feedback workflow',
            'priority' => 'high',
            'reason' => 'Google rating is below 4.0, so reputation improvement can directly affect customer trust.',
            'opener' => 'I saw your Google rating and there may be a chance to improve customer trust with a review response and feedback workflow.',
        ],
        'gbp_optimization' => [
            'label' => 'Google Business Profile',
            'service' => 'Google Business Profile optimization',
            'priority' => 'medium',
            'reason' => 'The business was discovered from Google Maps, so profile ranking and completeness are directly relevant.',
            'opener' => 'Since customers already find you on Google Maps, we can optimize your business profile to improve calls, direction requests, and local ranking.',
        ],
        'phone_outreach' => [
            'label' => 'Phone outreach',
            'service' => 'Phone-first outreach and lead capture setup',
            'priority' => 'medium',
            'reason' => 'A phone number is available but no public email was found, so outreach should start by call.',
            'opener' => 'I found your phone contact but not a public email. We can help set up a simple inquiry flow so calls and leads are easier to track.',
        ],
        'reddit_outreach' => [
            'label' => 'Reddit reply outreach',
            'service' => 'Helpful Reddit reply, then direct follow-up',
            'priority' => 'high',
            'reason' => 'This lead is an active Reddit post showing real intent — a genuine, helpful public reply converts far better than a cold pitch.',
            'opener' => 'Reply in-thread with a specific, useful answer to their question first (no hard sell), then offer to share a quick example or DM the details.',
        ],

        /*
        |----------------------------------------------------------------------
        | Digital marketing agency pitches (Phase 8A)
        |----------------------------------------------------------------------
        | Matching lives in App\Services\LeadPitchService::matchesType() and the
        | SQL mirror in App\Support\LeadQueryFilters::applyPitchType(). Source
        | groups below are the single source of truth for both.
        */
        'local_seo' => [
            'label' => 'Local SEO',
            'service' => 'Local SEO & Google Business Profile',
            'service_slug' => 'local-seo',
            'priority' => 'high',
            'reason' => 'This is a local business with a website but discovered through a directory — a strong fit for local SEO and map-pack visibility.',
            'opener' => 'Customers in your area are searching for what you do. We can optimize your Google Business Profile and local presence so you show up in the map pack and win more nearby calls and visits.',
        ],
        'seo_growth' => [
            'label' => 'SEO / organic growth',
            'service' => 'Search Engine Optimization (SEO)',
            'service_slug' => 'search-engine-optimization',
            'priority' => 'high',
            'reason' => 'This lead was found on a B2B/agency directory, where buyers actively invest in organic search growth.',
            'opener' => 'We help businesses like yours turn organic search into a steady, lower-cost lead channel — technical fixes, content, and authority building tied to real pipeline, not vanity rankings.',
        ],
        'google_ads' => [
            'label' => 'Google Ads / PPC',
            'service' => 'Google Ads & PPC Management',
            'service_slug' => 'google-ads-ppc',
            'priority' => 'medium',
            'reason' => 'The business is reachable (phone + website), making it a good candidate for immediate, trackable paid-search leads.',
            'opener' => 'If you want leads now, we can run profitable Google Ads with proper conversion tracking so every dollar maps to a call or form — not just clicks.',
        ],
        'social_media_growth' => [
            'label' => 'Social media marketing',
            'service' => 'Social Media Marketing & Management',
            'service_slug' => 'social-media-marketing',
            'priority' => 'medium',
            'reason' => 'A local business with a thin review/engagement footprint has clear room to build awareness and trust on social.',
            'opener' => 'Your online presence has room to grow. We can build a consistent social presence — content and community — that keeps your brand top-of-mind and feeds repeat customers.',
        ],
        'content_marketing' => [
            'label' => 'Content marketing',
            'service' => 'Content Marketing & Strategy',
            'service_slug' => 'content-marketing',
            'priority' => 'low',
            'reason' => 'B2B/agency leads with a website benefit from content that ranks, educates buyers, and compounds into pipeline.',
            'opener' => 'We can turn your expertise into content that ranks and converts — articles, lead magnets, and email mapped to how your buyers actually research and decide.',
        ],
    ],

    'reviews_growth_threshold' => 20,
    'reputation_rating_threshold' => 4.0,

    /*
    |--------------------------------------------------------------------------
    | Source groups (single source of truth for marketing pitch matching)
    |--------------------------------------------------------------------------
    | local_sources  = directory/listing sources for local businesses.
    | agency_sources = B2B/agency directories whose leads buy marketing services.
    */
    'local_sources' => [
        'google_maps', 'yelp', 'openstreetmap', 'bing_places',
        'hotfrog', 'yellow_pages', 'manta', 'foursquare',
    ],

    'agency_sources' => [
        'the_manifest', 'goodfirms', 'designrush', 'upcity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing pitch routing (Phase 8B)
    |--------------------------------------------------------------------------
    */
    'marketing_pitch_types' => [
        'local_seo',
        'seo_growth',
        'google_ads',
        'social_media_growth',
        'content_marketing',
        // Legacy local pitches that Maya handles (same buyer, marketing services)
        'reviews_growth',
        'reputation_repair',
        'gbp_optimization',
    ],

    'preferred_employee' => [
        'marketing' => 'Maya — SEO & Marketing Specialist',
        'default' => 'Alex — Voice Sales Agent',
    ],

];
