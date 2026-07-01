<?php

namespace App\Http\Resources\Admin;

use App\Domains\BusinessKnowledge\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'detailed_description' => $this->detailed_description,
            'target_audience' => $this->target_audience ?? [],
            'ideal_customer_profile' => $this->ideal_customer_profile,
            'problems_solved' => $this->problems_solved ?? [],
            'features' => $this->features ?? [],
            'benefits' => $this->benefits ?? [],
            'deliverables' => $this->deliverables ?? [],
            'typical_timeline' => $this->typical_timeline,
            'complexity_level' => $this->complexity_level?->value ?? $this->complexity_level,
            'pricing_notes' => $this->pricing_notes,
            'faqs' => $this->faqs ?? [],
            'objections' => $this->objections ?? [],
            'discovery_questions' => $this->discovery_questions ?? [],
            'quotation_requirements' => $this->quotation_requirements ?? [],
            'cross_sell_ids' => $this->cross_sell_ids ?? [],
            'upsell_ids' => $this->upsell_ids ?? [],
            'related_service_ids' => $this->related_service_ids ?? [],
            'tags' => $this->tags ?? [],
            'technologies' => $this->technologies ?? [],
            'sort_order' => $this->sort_order ?? 0,
            'status' => $this->status?->value ?? $this->status,
            'version' => $this->version,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater?->id,
                'name' => $this->updater?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
