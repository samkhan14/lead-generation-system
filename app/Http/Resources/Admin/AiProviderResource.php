<?php

namespace App\Http\Resources\Admin;

use App\Domains\AI\Models\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiProvider */
class AiProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'has_api_key' => filled($this->api_key),
            'api_base_url' => $this->api_base_url,
            'priority' => $this->priority,
            'rate_limit_rpm' => $this->rate_limit_rpm,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_count' => $this->retry_count,
            'status' => $this->status?->value ?? $this->status,
            'metadata' => $this->metadata ?? [],
            'models_count' => $this->whenCounted('models'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
