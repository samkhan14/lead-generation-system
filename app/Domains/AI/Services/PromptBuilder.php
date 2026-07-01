<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\PromptableContextInterface;
use App\Domains\AI\DataTransferObjects\PromptContext;
use App\Domains\BusinessKnowledge\Services\ServiceKnowledgeFormatter;

class PromptBuilder implements PromptableContextInterface
{
    public function __construct(
        private ServiceKnowledgeFormatter $serviceKnowledgeFormatter,
    ) {}

    public function buildInstructions(PromptContext $context): string
    {
        $sections = [];

        if ($employee = $context->employee) {
            if (filled($employee->system_prompt)) {
                $sections[] = trim($employee->system_prompt);
            }

            if (filled($employee->behavior_prompt)) {
                $sections[] = "Behavior:\n".trim($employee->behavior_prompt);
            }

            $sections[] = 'Role: '.$employee->role->label();
        }

        if ($servicesBlock = $this->serviceKnowledgeFormatter->formatCatalogForPrompt($context->services)) {
            $sections[] = $servicesBlock;
        }

        if ($knowledgeBlock = $this->formatKnowledgeArticles($context)) {
            $sections[] = $knowledgeBlock;
        }

        if ($leadBlock = $this->formatLead($context)) {
            $sections[] = $leadBlock;
        }

        $sections[] = 'Use only the business knowledge above when discussing services, pricing, or deliverables. Do not invent offerings.';

        return trim(implode("\n\n", array_filter($sections)));
    }

    public function buildUserMessage(PromptContext $context): string
    {
        return trim((string) $context->userMessage);
    }

    private function formatKnowledgeArticles(PromptContext $context): ?string
    {
        if ($context->knowledgeArticles === []) {
            return null;
        }

        $lines = ['Additional knowledge:'];

        foreach ($context->knowledgeArticles as $article) {
            $lines[] = '- '.($article['name'] ?? 'Article').': '.($article['content'] ?? '');
        }

        return implode("\n", $lines);
    }

    private function formatLead(PromptContext $context): ?string
    {
        $lead = $context->lead;

        if ($lead === null) {
            return null;
        }

        $lines = [
            'Current lead context:',
            '- Name: '.$lead->full_name,
        ];

        if (filled($lead->company)) {
            $lines[] = '- Company: '.$lead->company;
        }

        if (filled($lead->email)) {
            $lines[] = '- Email: '.$lead->email;
        }

        if (filled($lead->phone)) {
            $lines[] = '- Phone: '.$lead->phone;
        }

        if (filled($lead->website)) {
            $lines[] = '- Website: '.$lead->website;
        }

        if (filled($lead->source)) {
            $lines[] = '- Source: '.$lead->source;
        }

        if (filled($lead->notes)) {
            $lines[] = '- Notes: '.$lead->notes;
        }

        return implode("\n", $lines);
    }
}
