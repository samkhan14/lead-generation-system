<?php

namespace App\Http\Resources\Admin;

use App\Domains\Email\Models\EmailSend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmailSend */
class EmailSendResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email_campaign_id' => $this->email_campaign_id,
            'email_provider_id' => $this->email_provider_id,
            'lead_id' => $this->lead_id,
            'to_email' => $this->to_email,
            'to_name' => $this->to_name,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'reply_to' => $this->reply_to,
            'subject' => $this->subject,
            'html_body' => $this->html_body,
            'text_body' => $this->text_body,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'is_test' => $this->is_test,
            'provider_message_id' => $this->provider_message_id,
            'error_message' => $this->error_message,
            'queued_at' => $this->queued_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'campaign' => $this->whenLoaded('campaign', fn () => [
                'id' => $this->campaign?->id,
                'name' => $this->campaign?->name,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => EmailProviderResource::make($this->provider)),
        ];
    }
}
