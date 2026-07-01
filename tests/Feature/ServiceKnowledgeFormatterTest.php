<?php

use App\Domains\BusinessKnowledge\DataTransferObjects\ServiceKnowledgeItem;
use App\Domains\BusinessKnowledge\Services\ServiceKnowledgeFormatter;
use App\Domains\Voice\Services\VoiceContextBuilder;
use App\Models\Lead;

test('service knowledge formatter includes full sales playbook for text agents', function () {
    $service = new ServiceKnowledgeItem(
        id: 1,
        uuid: 'uuid-1',
        name: 'Laravel Development',
        slug: 'laravel-development',
        shortDescription: 'Custom Laravel backends.',
        description: 'Custom Laravel backends.',
        detailedDescription: 'We build production Laravel systems with queues and APIs.',
        targetAudience: ['SaaS founders'],
        idealCustomerProfile: 'Growing startup with technical debt in PHP.',
        problemsSolved: ['Slow legacy admin', 'Missing API layer'],
        features: ['REST APIs', 'Queued jobs'],
        benefits: ['Scalable architecture'],
        deliverables: ['Deployed application'],
        typicalTimeline: '6–10 weeks',
        complexityLevel: 'complex',
        pricingNotes: 'Scoped after discovery; no fixed packages.',
        faqs: [['question' => 'Do you work with existing code?', 'answer' => 'Yes, after a technical audit.']],
        objections: [['objection' => 'We have developers.', 'response' => 'We augment or lead architecture.']],
        discoveryQuestions: ['What problem are you solving?', 'Is this MVP or production?'],
        quotationRequirements: ['Current stack overview', 'User count estimate'],
        tags: ['saas'],
        technologies: ['Laravel', 'PHP'],
        crossSells: [],
        upsells: [],
        relatedServices: [],
        version: 1,
        sortOrder: 50,
    );

    $formatted = app(ServiceKnowledgeFormatter::class)->formatCatalogForPrompt([$service]);

    expect($formatted)
        ->toContain('Laravel Development')
        ->toContain('Ideal customer:')
        ->toContain('Discovery questions to ask:')
        ->toContain('Required before quoting:')
        ->toContain('Objection handling')
        ->toContain('We have developers.')
        ->toContain('Typical timeline: 6–10 weeks')
        ->toContain('Technologies: Laravel; PHP');
});

test('voice context builder passes discovery questions and objection responses', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    $employee = \App\Domains\AI\Models\AiEmployee::factory()->create([
        'knowledge_sources' => ['services'],
    ]);

    $lead = createLead(['phone' => '+14155550199']);

    $variables = app(VoiceContextBuilder::class)->dynamicVariables($employee, $lead);

    expect($variables)
        ->toHaveKey('services_summary')
        ->toHaveKey('services_catalog_brief')
        ->toHaveKey('discovery_questions')
        ->toHaveKey('objection_responses')
        ->and($variables['discovery_questions'])->toContain('?');
});

test('prompt builder injects discovery questions and faqs from service catalog', function () {
    \App\Domains\BusinessKnowledge\Models\Service::query()->create([
        'name' => 'API Integrations',
        'slug' => 'api-integrations-test',
        'short_description' => 'Connect your systems.',
        'description' => 'Connect your systems.',
        'detailed_description' => 'We integrate CRMs, payment gateways, and internal tools.',
        'ideal_customer_profile' => 'Ops teams juggling manual exports.',
        'discovery_questions' => ['Which systems need to talk to each other?'],
        'quotation_requirements' => ['API documentation links'],
        'faqs' => [['question' => 'Do you sign NDAs?', 'answer' => 'Yes, standard mutual NDA.']],
        'objections' => [['objection' => 'We only need one integration.', 'response' => 'We can start with a single high-impact connection.']],
        'status' => \App\Domains\BusinessKnowledge\Enums\ServiceStatus::Active,
        'version' => 1,
    ]);

    $context = app(\App\Domains\AI\Services\ContextBuilder::class)->build(
        \App\Domains\AI\Models\AiEmployee::factory()->create([
            'system_prompt' => 'You sell software services.',
            'knowledge_sources' => ['services'],
        ]),
    );

    $instructions = app(\App\Domains\AI\Services\PromptBuilder::class)->buildInstructions($context);

    expect($instructions)
        ->toContain('Discovery questions to ask:')
        ->toContain('Which systems need to talk to each other?')
        ->toContain('Required before quoting:')
        ->toContain('Q: Do you sign NDAs?')
        ->toContain('We only need one integration.');
});
