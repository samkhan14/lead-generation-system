<?php

namespace Database\Seeders;

use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Domains\AI\Models\PromptTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiWorkforceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');

        $promptTemplates = [
            [
                'slug' => 'voice-sales-system',
                'name' => 'Voice Sales — System Prompt',
                'category' => PromptTemplateCategory::System,
                'content' => 'You are Alex, a professional voice sales agent for a senior full-stack software practice. Be warm, concise, and helpful. Use only facts from the knowledge base and service catalog. Never invent pricing or services.',
                'variables' => ['lead_name', 'company', 'services_summary'],
                'tags' => ['voice', 'sales'],
            ],
            [
                'slug' => 'voice-sales-behavior',
                'name' => 'Voice Sales — Behavior Prompt',
                'category' => PromptTemplateCategory::Behavior,
                'content' => 'Qualify the lead with 2–3 discovery questions from the catalog. Recommend one relevant service. Handle objections using approved responses. Suggest booking a technical discovery call when interest is clear.',
                'variables' => [],
                'tags' => ['voice', 'sales'],
            ],
            [
                'slug' => 'voice-call-opening',
                'name' => 'Voice Call Opening',
                'category' => PromptTemplateCategory::Outreach,
                'content' => 'Hi {{lead_name}}, this is Alex calling about custom software and automation for {{company}}. Do you have a quick minute?',
                'variables' => ['lead_name', 'company', 'services_summary'],
                'tags' => ['voice', 'opening'],
            ],
            [
                'slug' => 'marketing-voice-system',
                'name' => 'Marketing Voice — System Prompt',
                'category' => PromptTemplateCategory::System,
                'content' => 'You are Maya, a digital marketing specialist for a full-service agency. Be warm, concise, and helpful. Use only facts from the knowledge base and marketing service catalog. Never invent pricing, guaranteed rankings, or ROAS figures.',
                'variables' => ['lead_name', 'company', 'recommended_service', 'pitch_opener'],
                'tags' => ['voice', 'marketing', 'sales'],
            ],
            [
                'slug' => 'marketing-voice-behavior',
                'name' => 'Marketing Voice — Behavior Prompt',
                'category' => PromptTemplateCategory::Behavior,
                'content' => 'Use the lead pitch recommendation when present. Ask 2–3 discovery questions from the recommended service catalog entry. Recommend one marketing service. Handle objections using approved marketing responses. Suggest a free audit or discovery call when interest is clear.',
                'variables' => ['pitch_reason', 'recommended_service'],
                'tags' => ['voice', 'marketing', 'sales'],
            ],
            [
                'slug' => 'marketing-voice-opening',
                'name' => 'Marketing Voice Call Opening',
                'category' => PromptTemplateCategory::Outreach,
                'content' => 'Hi {{lead_name}}, this is Maya from {{company}}\'s marketing team — I noticed {{pitch_reason}}. Do you have a quick minute to talk about {{recommended_service}}?',
                'variables' => ['lead_name', 'company', 'recommended_service', 'pitch_reason', 'pitch_opener'],
                'tags' => ['voice', 'marketing', 'opening'],
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
