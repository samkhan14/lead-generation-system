<?php

namespace App\Http\Resources\Admin;

use App\Domains\AI\Models\AiModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiModel */
class AiModelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ai_provider_id' => $this->ai_provider_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'capabilities' => $this->capabilities ?? [],
            'max_tokens' => $this->max_tokens,
            'input_price_per_1k' => $this->input_price_per_1k,
            'output_price_per_1k' => $this->output_price_per_1k,
            'status' => $this->status?->value ?? $this->status,
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'slug' => $this->provider?->slug,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
