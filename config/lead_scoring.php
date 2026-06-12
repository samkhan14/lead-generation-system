<?php

return [

    'version' => 'v2',

    'weights' => [
        'intent' => 0.35,
        'opportunity' => 0.35,
        'authenticity' => 0.30,
    ],

    'intent' => [
        'keywords' => [
            'interested',
            'quote',
            'pricing',
            'demo',
            'urgent',
            'ready',
            'buy',
            'need',
            'looking for',
            'contact me',
            'call back',
            'asap',
        ],
        'keyword_points' => 8,
        'keyword_cap' => 40,
        'metadata_levels' => [
            'high' => 30,
            'medium' => 20,
            'low' => 10,
        ],
        'source' => [
            'api' => 15,
            'scraper' => 10,
            'import' => 8,
            'manual' => 5,
        ],
        'recent_contact_points' => 15,
        'recent_contact_days' => 14,
    ],

    'opportunity' => [
        'company_points' => 20,
        'decision_maker_points' => 25,
        'decision_maker_titles' => [
            'ceo', 'cto', 'cfo', 'coo', 'founder', 'owner', 'director', 'vp', 'vice president', 'head', 'manager', 'president',
        ],
        'full_contact_bundle_points' => 25,
        'corporate_email_points' => 15,
        'domain_match_points' => 15,
    ],

    'authenticity' => [
        'phone_points' => 25,
        'min_phone_digits' => 10,
        'corporate_email_points' => 25,
        'website_points' => 20,
        'name_points' => 15,
        'trusted_source_points' => 15,
        'trusted_sources' => ['manual', 'api'],
        'free_email_domains' => [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'aol.com', 'proton.me', 'protonmail.com',
        ],
        'generic_names' => ['test', 'unknown', 'na', 'n/a', 'none', 'admin'],
        'free_email_no_company_penalty' => 15,
        'generic_name_penalty' => 20,
    ],

];
