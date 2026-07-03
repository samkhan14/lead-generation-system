<?php

namespace Database\Seeders;

use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Domains\AI\Models\KnowledgeBase;
use App\Domains\AI\Models\PromptTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiWorkforceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');

        $knowledgeEntries = [
            [
                'slug' => 'company-overview',
                'name' => 'Company Overview',
                'category' => KnowledgeBaseCategory::Company,
                'content' => <<<'TEXT'
We are a digital agency offering web development, mobile apps, SEO, and AI automation.
We help small and mid-size businesses grow online with modern websites, CRM integrations, and AI workforce tools.
Our process: discovery call → scoped proposal → build → launch → ongoing support.
TEXT,
                'tags' => ['company', 'overview', 'sales'],
            ],
            [
                'slug' => 'voice-sales-guidelines',
                'name' => 'Voice Sales Guidelines',
                'category' => KnowledgeBaseCategory::Policy,
                'content' => <<<'TEXT'
Always introduce yourself and the company clearly.
Ask one discovery question at a time. Never invent pricing — refer to the service catalog or offer a follow-up call.
If the lead is not interested, thank them politely and log the outcome.
Do not discuss competitors negatively. Keep calls under 5 minutes unless the lead is engaged.
TEXT,
                'tags' => ['voice', 'sales', 'policy'],
            ],
            [
                'slug' => 'common-objections',
                'name' => 'Common Sales Objections',
                'category' => KnowledgeBaseCategory::Faq,
                'content' => <<<'TEXT'
"We don't have budget" → Offer a phased approach or a smaller starter package; suggest a free discovery call.
"We already have a vendor" → Ask what is working and what is not; position us as a specialist for gaps (AI, automation, revamp).
"Send me an email" → Confirm email, summarize one relevant service, and set expectation for follow-up within 24 hours.
"We need to think about it" → Ask what information would help them decide; offer a short demo or case study.
TEXT,
                'tags' => ['objections', 'sales'],
            ],
        ];

        foreach ($knowledgeEntries as $entry) {
            KnowledgeBase::query()->updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    ...$entry,
                    'status' => KnowledgeBaseStatus::Active,
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ],
            );
        }

        $promptTemplates = [
            [
                'slug' => 'voice-sales-system',
                'name' => 'Voice Sales — System Prompt',
                'category' => PromptTemplateCategory::System,
                'content' => 'You are Alex, a professional voice sales agent for a digital agency. Be warm, concise, and helpful. Use only facts from the knowledge base and service catalog.',
                'variables' => ['lead_name', 'company', 'service_name'],
                'tags' => ['voice', 'sales'],
            ],
            [
                'slug' => 'voice-sales-behavior',
                'name' => 'Voice Sales — Behavior Prompt',
                'category' => PromptTemplateCategory::Behavior,
                'content' => 'Qualify the lead with 2–3 discovery questions. Recommend one relevant service. Handle objections calmly. Suggest booking a call when interest is clear.',
                'variables' => [],
                'tags' => ['voice', 'sales'],
            ],
            [
                'slug' => 'voice-call-opening',
                'name' => 'Voice Call Opening',
                'category' => PromptTemplateCategory::Outreach,
                'content' => 'Hi {{lead_name}}, this is Alex from {{company_name}}. I noticed you might be interested in {{service_name}}. Do you have a quick minute to chat?',
                'variables' => ['lead_name', 'company_name', 'service_name'],
                'tags' => ['voice', 'opening'],
            ],
        ];

        foreach ($promptTemplates as $template) {
            PromptTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    ...$template,
                    'status' => PromptTemplateStatus::Active,
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ],
            );
        }
    }
}
