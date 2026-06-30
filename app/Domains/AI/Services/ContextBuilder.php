<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\DataTransferObjects\PromptContext;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\KnowledgeBase;
use App\Models\Lead;
use App\Services\ServiceCatalogService;

class ContextBuilder
{
    public function __construct(
        private ServiceCatalogService $serviceCatalog,
    ) {}

    public function build(
        ?AiEmployee $employee = null,
        ?Lead $lead = null,
        ?string $userMessage = null,
        array $metadata = [],
    ): PromptContext {
        $sources = $this->resolveKnowledgeSources($employee);
        $includesLead = in_array('lead', $sources, true);

        return new PromptContext(
            employee: $employee,
            lead: ($includesLead ? $lead : null),
            userMessage: $userMessage,
            services: in_array('services', $sources, true)
                ? $this->serviceCatalog->activeKnowledgeForAi()
                : [],
            knowledgeArticles: in_array('knowledge_bases', $sources, true)
                ? $this->loadKnowledgeArticles()
                : [],
            metadata: $metadata,
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveKnowledgeSources(?AiEmployee $employee): array
    {
        $defaults = config('ai_platform.knowledge_sources', ['services', 'knowledge_bases', 'lead']);
        $configured = $employee?->knowledge_sources;

        if (! is_array($configured) || $configured === []) {
            return $defaults;
        }

        return array_values(array_unique($configured));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadKnowledgeArticles(): array
    {
        return KnowledgeBase::query()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get(['name', 'slug', 'category', 'content'])
            ->map(fn (KnowledgeBase $entry) => [
                'name' => $entry->name,
                'slug' => $entry->slug,
                'category' => $entry->category?->value ?? $entry->category,
                'content' => $entry->content,
            ])
            ->all();
    }
}
