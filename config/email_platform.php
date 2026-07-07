<?php

return [

    'provider_drivers' => [
        'log' => 'log',
        'smtp' => 'smtp',
        'resend' => 'resend',
        'mailgun' => 'mailgun',
    ],

    'provider_defaults' => [
        'log' => [],
        'smtp' => [],
        'resend' => [
            'api_base_url' => 'https://api.resend.com',
        ],
        'mailgun' => [
            'api_base_url' => 'https://api.mailgun.net',
        ],
    ],

    'queue' => env('EMAIL_QUEUE', 'email'),

    'bulk' => [
        'max_recipients_per_campaign' => (int) env('EMAIL_BULK_MAX_RECIPIENTS', 500),
    ],

    'ai_enhancement' => [
        'default_employee_name' => env('EMAIL_AI_EMPLOYEE', 'Maya — SEO & Marketing Specialist'),
        'system_hint' => 'Improve email marketing copy for clarity, persuasion, and deliverability. Keep factual claims. Return JSON with keys: subject, html_body, text_body.',
    ],

];
