<?php

namespace App\Domains\BusinessKnowledge\DataTransferObjects;

readonly class ServiceKnowledgeItem
{
    /**
     * @param  array<int, string>  $features
     * @param  array<int, string>  $benefits
     * @param  array<int, string>  $deliverables
     * @param  array<int, array{question: string, answer: string}>  $faqs
     * @param  array<int, array{objection: string, response: string}>  $objections
     * @param  array<int, string>  $tags
     * @param  array<int, array{id: int, name: string, slug: string}>  $crossSells
     * @param  array<int, array{id: int, name: string, slug: string}>  $upsells
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $description,
        public array $features,
        public array $benefits,
        public array $deliverables,
        public ?string $pricingNotes,
        public array $faqs,
        public array $objections,
        public array $tags,
        public array $crossSells,
        public array $upsells,
        public int $version,
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
            'description' => $this->description,
            'features' => $this->features,
            'benefits' => $this->benefits,
            'deliverables' => $this->deliverables,
            'pricing_notes' => $this->pricingNotes,
            'faqs' => $this->faqs,
            'objections' => $this->objections,
            'tags' => $this->tags,
            'cross_sells' => $this->crossSells,
            'upsells' => $this->upsells,
            'version' => $this->version,
        ];
    }
}
