<?php

namespace App\Http\Resources\Admin;

use App\Domains\AI\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeBase */
class KnowledgeBaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category?->value ?? $this->category,
            'content' => $this->content,
            'metadata' => $this->metadata ?? [],
            'tags' => $this->tags ?? [],
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
