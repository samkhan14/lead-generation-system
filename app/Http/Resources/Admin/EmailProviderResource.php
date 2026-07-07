<?php

namespace App\Http\Resources\Admin;

use App\Domains\Email\Models\EmailProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmailProvider */
class EmailProviderResource extends JsonResource
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
            'api_base_url' => $this->api_base_url,
            'priority' => $this->priority,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_count' => $this->retry_count,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'has_api_key' => filled($this->api_key),
            'is_usable' => $this->isUsable(),
            'metadata' => $this->metadata ?? [],
            'default_from_email' => $this->defaultFromEmail(),
            'default_from_name' => $this->defaultFromName(),
            'reply_to' => $this->defaultReplyTo(),
            'sends_count' => $this->whenCounted('sends'),
            'campaigns_count' => $this->whenCounted('campaigns'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
