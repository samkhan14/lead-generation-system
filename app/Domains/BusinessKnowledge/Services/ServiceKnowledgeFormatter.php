<?php

namespace App\Domains\BusinessKnowledge\Services;

use App\Domains\BusinessKnowledge\DataTransferObjects\ServiceKnowledgeItem;

class ServiceKnowledgeFormatter
{
    /**
     * @param  array<int, ServiceKnowledgeItem>  $services
     */
    public function formatCatalogForPrompt(array $services): ?string
    {
        if ($services === []) {
            return null;
        }

        $lines = [
            'Company services catalog (use only this data — do not invent offerings, prices, or deliverables):',
            '',
        ];

        foreach ($services as $service) {
            $lines[] = $this->formatServiceForPrompt($service);
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Retell/Vapi dynamic variables must be string key-value pairs with practical size limits.
     *
     * @param  array<int, ServiceKnowledgeItem>  $services
     * @return array<string, string>
     */
    public function formatCatalogForVoice(array $services): array
    {
        if ($services === []) {
            return [];
        }

        $maxChars = (int) config('ai_platform.service_knowledge.voice_max_catalog_chars', 4500);

        $names = collect($services)->pluck('name')->implode(', ');

        $briefLines = collect($services)
            ->take((int) config('ai_platform.service_knowledge.voice_max_services', 8))
            ->map(function (ServiceKnowledgeItem $service) {
                $summary = $service->shortDescription ?? $service->description ?? '';

                return $summary !== ''
                    ? "- {$service->name}: {$summary}"
                    : "- {$service->name}";
            });

        $discovery = collect($services)
            ->flatMap(fn (ServiceKnowledgeItem $service) => $service->discoveryQuestions)
            ->unique()
            ->take((int) config('ai_platform.service_knowledge.voice_max_discovery_questions', 8))
            ->values();

        $objections = collect($services)
            ->flatMap(fn (ServiceKnowledgeItem $service) => $service->objections)
            ->take((int) config('ai_platform.service_knowledge.voice_max_objections', 5))
            ->map(fn (array $item) => "\"{$item['objection']}\" → {$item['response']}");

        $faqs = collect($services)
            ->flatMap(fn (ServiceKnowledgeItem $service) => $service->faqs)
            ->take((int) config('ai_platform.service_knowledge.voice_max_faqs', 5))
            ->map(fn (array $item) => "Q: {$item['question']} A: {$item['answer']}");

        $catalogBrief = $this->truncate($briefLines->implode("\n"), (int) ($maxChars * 0.45));

        return array_filter([
            'services_summary' => $names,
            'services_catalog_brief' => $catalogBrief,
            'discovery_questions' => $discovery->implode(' | '),
            'objection_responses' => $objections->implode(' | '),
            'faq_highlights' => $faqs->implode(' | '),
            'quotation_requirements' => collect($services)
                ->flatMap(fn (ServiceKnowledgeItem $service) => $service->quotationRequirements)
                ->unique()
                ->take(6)
                ->implode(' | '),
        ], fn (string $value) => trim($value) !== '');
    }

    public function formatServiceForPrompt(ServiceKnowledgeItem $service): string
    {
        $lines = ["## {$service->name} ({$service->slug})"];

        $summary = $service->shortDescription ?? $service->description;
        if (filled($summary)) {
            $lines[] = "Summary: {$summary}";
        }

        if (config('ai_platform.service_knowledge.include_detailed_description', true)
            && filled($service->detailedDescription)) {
            $lines[] = "Details: {$service->detailedDescription}";
        }

        if ($service->targetAudience !== []) {
            $lines[] = 'Target audience: '.$this->joinLimited($service->targetAudience);
        }

        if (filled($service->idealCustomerProfile)) {
            $lines[] = "Ideal customer: {$service->idealCustomerProfile}";
        }

        if ($service->problemsSolved !== []) {
            $lines[] = 'Problems solved: '.$this->joinLimited(
                $service->problemsSolved,
                (int) config('ai_platform.service_knowledge.max_problems_per_service', 6),
            );
        }

        if ($service->features !== []) {
            $lines[] = 'Features: '.$this->joinLimited($service->features, 8);
        }

        if ($service->benefits !== []) {
            $lines[] = 'Benefits: '.$this->joinLimited($service->benefits, 6);
        }

        if ($service->deliverables !== []) {
            $lines[] = 'Deliverables: '.$this->joinLimited($service->deliverables, 8);
        }

        if (filled($service->typicalTimeline)) {
            $lines[] = "Typical timeline: {$service->typicalTimeline}";
        }

        if (filled($service->complexityLevel)) {
            $lines[] = "Complexity: {$service->complexityLevel}";
        }

        if ($service->technologies !== []) {
            $lines[] = 'Technologies: '.$this->joinLimited($service->technologies);
        }

        if ($service->tags !== []) {
            $lines[] = 'Industries/tags: '.$this->joinLimited($service->tags);
        }

        if ($service->pricingNotes) {
            $lines[] = "Pricing notes: {$service->pricingNotes}";
        }

        if ($service->discoveryQuestions !== []) {
            $lines[] = 'Discovery questions to ask:';
            foreach ($this->limitList($service->discoveryQuestions, 'max_discovery_questions_per_service', 7) as $question) {
                $lines[] = "- {$question}";
            }
        }

        if ($service->quotationRequirements !== []) {
            $lines[] = 'Required before quoting:';
            foreach ($this->limitList($service->quotationRequirements, 'max_quotation_requirements_per_service', 6) as $requirement) {
                $lines[] = "- {$requirement}";
            }
        }

        if ($service->faqs !== []) {
            $lines[] = 'FAQs:';
            foreach ($this->limitList($service->faqs, 'max_faqs_per_service', 5) as $faq) {
                $lines[] = "- Q: {$faq['question']} A: {$faq['answer']}";
            }
        }

        if ($service->objections !== []) {
            $lines[] = 'Objection handling (use these responses):';
            foreach ($this->limitList($service->objections, 'max_objections_per_service', 5) as $objection) {
                $lines[] = "- \"{$objection['objection']}\" → {$objection['response']}";
            }
        }

        $related = collect(array_merge($service->crossSells, $service->upsells, $service->relatedServices))
            ->unique('id')
            ->pluck('name')
            ->filter()
            ->values();

        if ($related->isNotEmpty()) {
            $lines[] = 'Related offerings: '.$related->implode(', ');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $items
     */
    private function joinLimited(array $items, ?int $limit = null): string
    {
        $items = array_values(array_filter($items, fn ($item) => trim((string) $item) !== ''));

        if ($limit !== null) {
            $items = array_slice($items, 0, $limit);
        }

        return implode('; ', $items);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function limitList(array $items, string $configKey, int $default): array
    {
        $limit = (int) config("ai_platform.service_knowledge.{$configKey}", $default);

        return array_slice($items, 0, max(1, $limit));
    }

    private function truncate(string $value, int $maxChars): string
    {
        if (strlen($value) <= $maxChars) {
            return $value;
        }

        return rtrim(substr($value, 0, $maxChars - 3)).'...';
    }
}
