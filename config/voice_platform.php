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

    'default_voice_employee_name' => 'Alex — Voice Sales Agent',

    'queue' => env('VOICE_QUEUE', 'voice'),

    'concurrency' => [
        /** Block a second outbound call for the same lead + employee while one is active. */
        'block_duplicate_lead_employee' => true,

        /** Max simultaneous active calls per AI employee (Retell supports bulk; raise for campaigns). */
        'max_active_calls_per_employee' => (int) env('VOICE_MAX_ACTIVE_CALLS_PER_EMPLOYEE', 50),
    ],

];
