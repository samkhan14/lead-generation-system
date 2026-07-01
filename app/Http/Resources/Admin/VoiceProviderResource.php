<?php

namespace App\Http\Resources\Admin;

use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VoiceProvider */
class VoiceProviderResource extends JsonResource
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
            'has_webhook_secret' => filled($this->webhook_secret),
            'api_base_url' => $this->api_base_url,
            'priority' => $this->priority,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_count' => $this->retry_count,
            'status' => $this->status?->value ?? $this->status,
            'metadata' => $this->metadata ?? [],
            'calls_count' => $this->whenCounted('calls'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
