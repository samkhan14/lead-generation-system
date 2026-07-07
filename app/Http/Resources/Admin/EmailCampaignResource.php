<?php

namespace App\Http\Resources\Admin;

use App\Domains\Email\Models\EmailCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmailCampaign */
class EmailCampaignResource extends JsonResource
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
            'subject' => $this->subject,
            'html_body' => $this->html_body,
            'text_body' => $this->text_body,
            'from_name' => $this->from_name,
            'from_email' => $this->from_email,
            'reply_to' => $this->reply_to,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'ai_enhanced' => $this->ai_enhanced,
            'email_provider_id' => $this->email_provider_id,
            'provider' => $this->whenLoaded('provider', fn () => EmailProviderResource::make($this->provider)),
            'sends_count' => $this->whenCounted('sends'),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
