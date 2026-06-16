<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reddit connector defaults (single source of truth)
    |--------------------------------------------------------------------------
    | These business rules are owned by the CRM and passed to the SRP service in
    | the job payload. The scraper stays a pure execution engine — change Reddit
    | targeting/intent here, not in the Node service.
    */

    // Default subreddit packs used when the job does not specify any.
    'default_subreddits' => [
        'smallbusiness',
        'Entrepreneur',
        'webdev',
        'SEO',
        'digital_marketing',
    ],

    /*
    | Per-country subreddit packs. When a job does not override subreddits (via the
    | Industry field), ProcessScrapeJob merges the country pack with the defaults so
    | jobs target locally-relevant communities. Keys are lower-cased country names.
    */
    'country_subreddits' => [
        'pakistan' => ['pakistan', 'karachi', 'lahore', 'islamabad'],
        'united states' => ['smallbusiness', 'sweatystartup', 'Entrepreneur'],
        'united kingdom' => ['SmallBusinessUK', 'UKPersonalFinance'],
        'india' => ['india', 'IndianStartups', 'smallbusiness'],
        'canada' => ['canadasmallbusiness', 'Entrepreneur'],
        'australia' => ['AusFinance', 'smallbusiness'],
        'united arab emirates' => ['dubai', 'UAE'],
    ],

    // Reddit "t" window. Combined with sort=new this keeps results realtime.
    'time_filter' => env('REDDIT_TIME_FILTER', 'week'),

    // Hard freshness guard — posts older than this are dropped (no stale data).
    'max_age_days' => (int) env('REDDIT_MAX_AGE_DAYS', 14),

    // Default re-run cadence for watch templates (overridable per job).
    'watch_interval_hours' => (int) env('REDDIT_WATCH_INTERVAL_HOURS', 12),

    // Ignore very short / low-signal posts.
    'min_post_length' => 20,

    // Flairs that indicate job seekers or noise (handled by the job-hunting
    // product separately), not service buyers.
    'exclude_flairs' => ['for hire', 'hiring', 'meta'],

    // Drop posts where the author is SELLING/OFFERING services (competitors,
    // freelancers, job seekers) instead of buying them. We only want buyers.
    'exclude_keywords' => [
        '[for hire]', 'for hire', '[hiring]', '[hire me]', 'hire me',
        'i offer', 'i provide', 'offering', 'i build', 'i can build', 'i will build',
        'i am a developer', "i'm a developer", 'freelance developer',
        'my portfolio', 'my services', 'dm me', 'message me for',
        'fiverr.com', 'upwork.com',
    ],

    /*
    | Intent classification. Order matters — the first matching kind wins, so the
    | highest-intent kinds are listed first. intent_level feeds lead scoring.
    */
    'lead_kinds' => [
        'service_request' => [
            'intent_level' => 'high',
            'keywords' => [
                // Generic buying intent
                'looking for', 'need a', 'need an', 'who can build', 'recommend',
                'looking to hire', 'want to hire', 'hire a', 'hire someone',
                'build me', 'can someone build', 'need help building',
                // Website / app
                'need a website', 'need a developer', 'redesign my website',
                'revamp my website', 'need a landing page', 'need an app',
                // SEO / marketing
                'need seo', 'need marketing', 'help with seo', 'need a logo',
                // Reviews / Google Business Profile
                'need reviews', 'manage my reviews', 'set up google business',
                'google business profile',
            ],
        ],
        'problem_post' => [
            'intent_level' => 'medium',
            'keywords' => [
                'no website', 'not getting', 'struggling', 'how do i get',
                'need more customers', 'no leads', 'losing customers',
                'not ranking', 'low on google', "can't be found", 'cant be found',
                'bad reviews', 'negative reviews', 'not showing on google',
                'no online presence',
            ],
        ],
        'feedback_request' => [
            'intent_level' => 'medium',
            'keywords' => ['roast my', 'feedback on', 'review my', 'rate my', 'critique'],
        ],
        'local_recommendation' => [
            'intent_level' => 'medium',
            'keywords' => ['recommendation', 'any good', 'suggestions for', 'best '],
        ],
    ],
];
