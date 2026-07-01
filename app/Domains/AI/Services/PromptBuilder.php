<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\PromptableContextInterface;
use App\Domains\AI\DataTransferObjects\PromptContext;

class PromptBuilder implements PromptableContextInterface
{
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

        if ($servicesBlock = $this->formatServices($context)) {
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

    private function formatServices(PromptContext $context): ?string
    {
        if ($context->services === []) {
            return null;
        }

        $lines = ['Company services catalog (use only this data — do not invent offerings):'];

        foreach ($context->services as $service) {
            $summary = $service->shortDescription ?? $service->description ?? '';
            $lines[] = "### {$service->name}";
            if ($summary !== '') {
                $lines[] = $summary;
            }
            if (filled($service->idealCustomerProfile)) {
                $lines[] = 'Ideal customer: '.$service->idealCustomerProfile;
            }
            if ($service->problemsSolved !== []) {
                $lines[] = 'Problems solved: '.implode('; ', array_slice($service->problemsSolved, 0, 4));
            }
            if ($service->features !== []) {
                $lines[] = 'Features: '.implode('; ', array_slice($service->features, 0, 6));
            }
            if ($service->benefits !== []) {
                $lines[] = 'Benefits: '.implode('; ', array_slice($service->benefits, 0, 4));
            }
            if (filled($service->typicalTimeline)) {
                $lines[] = 'Typical timeline: '.$service->typicalTimeline;
            }
            if ($service->pricingNotes) {
                $lines[] = 'Pricing notes: '.$service->pricingNotes;
            }
            if ($service->objections !== []) {
                $lines[] = 'Common objections (with approved responses):';
                foreach (array_slice($service->objections, 0, 3) as $objection) {
                    $lines[] = "- \"{$objection['objection']}\" → {$objection['response']}";
                }
            }
            if ($service->crossSells !== [] || $service->upsells !== []) {
                $related = collect(array_merge($service->crossSells, $service->upsells))
                    ->pluck('name')
                    ->unique()
                    ->implode(', ');
                if ($related !== '') {
                    $lines[] = 'Related offerings: '.$related;
                }
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
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
