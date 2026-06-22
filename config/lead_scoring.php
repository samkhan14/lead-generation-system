<?php

return [

    'version' => 'v6',

    'directory_sources' => ['google_maps', 'yelp', 'openstreetmap', 'bing_places'],

    'weights' => [
        'intent' => 0.25,
        'opportunity' => 0.45,
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
            'google_maps' => 4,
            'yelp' => 4,
            'openstreetmap' => 4,
            'bing_places' => 4,
            'reddit' => 12,
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
        'google_maps' => [
            'no_website_points' => 28,
            'website_points' => 8,
            'phone_points' => 12,
            'no_email_points' => 6,
            'proven_demand_no_website_points' => [
                300 => 14,
                100 => 11,
                50 => 8,
                20 => 5,
            ],
            'low_review_count_points' => 16,
            'low_review_count_threshold' => 20,
            'low_rating_points' => 18,
            'low_rating_threshold' => 4.0,
        ],
        'reddit' => [
            'lead_kind_points' => [
                'service_request' => 35,
                'problem_post' => 28,
                'feedback_request' => 28,
                'local_recommendation' => 24,
            ],
            'no_website_points' => 18,
            'website_audit_points' => 8,
            'engagement_points' => 6,
            'engagement_comment_threshold' => 3,
        ],
    ],

    'authenticity' => [
        'phone_points' => 25,
        'min_phone_digits' => 10,
        'corporate_email_points' => 25,
        'website_points' => 20,
        'name_points' => 15,
        'trusted_source_points' => 15,
        'trusted_sources' => ['manual', 'api', 'google_maps', 'yelp', 'openstreetmap', 'bing_places', 'reddit'],
        'google_maps' => [
            'place_id_points' => 12,
            'address_points' => 10,
            'rating_points' => [
                '4.8' => 14,
                '4.5' => 11,
                '4.0' => 8,
                '3.5' => 5,
            ],
            'review_count_points' => [
                300 => 15,
                100 => 12,
                50 => 9,
                10 => 5,
            ],
        ],
        'reddit' => [
            'author_points' => 10,
            'engagement_points' => 8,
            'upvote_threshold' => 3,
        ],
        'free_email_domains' => [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'aol.com', 'proton.me', 'protonmail.com',
        ],
        'generic_names' => ['test', 'unknown', 'na', 'n/a', 'none', 'admin'],
        'free_email_no_company_penalty' => 15,
        'generic_name_penalty' => 20,
    ],

];
