<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Platform (application layer)
    |--------------------------------------------------------------------------
    |
    | Business rules for the AI Workforce platform. Provider API keys and
    | models are stored in the database — not here.
    |
    */

    'runtime_provider_prefix' => 'db_',

    'provider_drivers' => [
        'openai' => 'openai',
        'anthropic' => 'anthropic',
        'gemini' => 'gemini',
        'openrouter' => 'openrouter',
        'grok' => 'xai',
        'xai' => 'xai',
    ],

    'knowledge_sources' => [
        'services',
        'knowledge_bases',
        'lead',
    ],

    'default_context_window' => 8192,

    'default_temperature' => 0.7,

    'log_payloads' => env('AI_LOG_PAYLOADS', true),

    'log_payload_max_chars' => 12000,

];
