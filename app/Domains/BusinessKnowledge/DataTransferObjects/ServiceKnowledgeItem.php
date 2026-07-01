<?php

namespace App\Domains\BusinessKnowledge\DataTransferObjects;

readonly class ServiceKnowledgeItem
{
    /**
     * @param  array<int, string>  $targetAudience
     * @param  array<int, string>  $problemsSolved
     * @param  array<int, string>  $features
     * @param  array<int, string>  $benefits
     * @param  array<int, string>  $deliverables
     * @param  array<int, array{question: string, answer: string}>  $faqs
     * @param  array<int, array{objection: string, response: string}>  $objections
     * @param  array<int, string>  $discoveryQuestions
     * @param  array<int, string>  $quotationRequirements
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $technologies
     * @param  array<int, array{id: int, name: string, slug: string}>  $crossSells
     * @param  array<int, array{id: int, name: string, slug: string}>  $upsells
     * @param  array<int, array{id: int, name: string, slug: string}>  $relatedServices
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public ?string $detailedDescription,
        public array $targetAudience,
        public ?string $idealCustomerProfile,
        public array $problemsSolved,
        public array $features,
        public array $benefits,
        public array $deliverables,
        public ?string $typicalTimeline,
        public ?string $complexityLevel,
        public ?string $pricingNotes,
        public array $faqs,
        public array $objections,
        public array $discoveryQuestions,
        public array $quotationRequirements,
        public array $tags,
        public array $technologies,
        public array $crossSells,
        public array $upsells,
        public array $relatedServices,
        public int $version,
        public int $sortOrder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->shortDescription,
            'description' => $this->description ?? $this->shortDescription,
            'detailed_description' => $this->detailedDescription,
            'target_audience' => $this->targetAudience,
            'ideal_customer_profile' => $this->idealCustomerProfile,
            'problems_solved' => $this->problemsSolved,
            'features' => $this->features,
            'benefits' => $this->benefits,
            'deliverables' => $this->deliverables,
            'typical_timeline' => $this->typicalTimeline,
            'complexity_level' => $this->complexityLevel,
            'pricing_notes' => $this->pricingNotes,
            'faqs' => $this->faqs,
            'objections' => $this->objections,
            'discovery_questions' => $this->discoveryQuestions,
            'quotation_requirements' => $this->quotationRequirements,
            'tags' => $this->tags,
            'technologies' => $this->technologies,
            'cross_sells' => $this->crossSells,
            'upsells' => $this->upsells,
            'related_services' => $this->relatedServices,
            'version' => $this->version,
            'sort_order' => $this->sortOrder,
        ];
    }
}
