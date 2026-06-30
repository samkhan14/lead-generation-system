<?php

namespace App\Domains\AI\DataTransferObjects;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\BusinessKnowledge\DataTransferObjects\ServiceKnowledgeItem;
use App\Models\Lead;

readonly class PromptContext
{
    /**
     * @param  array<int, ServiceKnowledgeItem>  $services
     * @param  array<int, array<string, mixed>>  $knowledgeArticles
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?AiEmployee $employee,
        public ?Lead $lead,
        public ?string $userMessage,
        public array $services = [],
        public array $knowledgeArticles = [],
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee?->id,
            'lead_id' => $this->lead?->id,
            'user_message' => $this->userMessage,
            'services' => array_map(fn (ServiceKnowledgeItem $item) => $item->toArray(), $this->services),
            'knowledge_articles' => $this->knowledgeArticles,
            'metadata' => $this->metadata,
        ];
    }
}
