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

    /*
    |--------------------------------------------------------------------------
    | Service knowledge (Business Knowledge Base → AI employees)
    |--------------------------------------------------------------------------
    |
    | Controls how much service catalog data is injected into text and voice
    | agent prompts. Tune limits to balance context quality vs token cost.
    |
    */
    'service_knowledge' => [
        'include_detailed_description' => true,
        'max_faqs_per_service' => 5,
        'max_objections_per_service' => 5,
        'max_discovery_questions_per_service' => 7,
        'max_quotation_requirements_per_service' => 6,
        'max_problems_per_service' => 6,
        'voice_max_services' => 8,
        'voice_max_discovery_questions' => 8,
        'voice_max_objections' => 5,
        'voice_max_faqs' => 5,
        'voice_max_catalog_chars' => 4500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Department-based service catalog filtering (AI / voice context)
    |--------------------------------------------------------------------------
    |
    | Limits which services appear in an employee's prompt. Tags must match
    | entries in database/seeders/data/business_services/*.php.
    |
    */
    'department_service_filters' => [
        'Marketing' => [
            'require_any_tag' => ['digital-marketing'],
        ],
        'Sales' => [
            'exclude_tags' => ['digital-marketing'],
        ],
    ],

];
