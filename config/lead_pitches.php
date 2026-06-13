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
    ],

    'reviews_growth_threshold' => 20,
    'reputation_rating_threshold' => 4.0,

];
