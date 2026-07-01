<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Voice Platform (application layer)
    |--------------------------------------------------------------------------
    |
    | Business rules for outbound/inbound voice. Provider credentials live in
    | the database — not here.
    |
    */

    'provider_drivers' => [
        'retell' => 'retell',
        'livekit' => 'livekit',
        'vapi' => 'vapi',
    ],

    'provider_defaults' => [
        'retell' => [
            'api_base_url' => 'https://api.retellai.com',
        ],
        'livekit' => [
            'api_base_url' => null,
        ],
        'vapi' => [
            'api_base_url' => 'https://api.vapi.ai',
        ],
    ],

    /**
     * AI employee roles allowed to place voice calls.
     *
     * @var array<int, string>
     */
    'eligible_employee_roles' => [
        'voice_sales',
        'sales',
        'appointment_setter',
    ],

    'log_webhook_payloads' => env('VOICE_LOG_WEBHOOK_PAYLOADS', true),

    'log_payload_max_chars' => 12000,

];
