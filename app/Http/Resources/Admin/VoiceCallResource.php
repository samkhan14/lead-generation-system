<?php

namespace App\Http\Resources\Admin;

use App\Domains\Voice\Models\VoiceCall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VoiceCall */
class VoiceCallResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'voice_provider_id' => $this->voice_provider_id,
            'ai_employee_id' => $this->ai_employee_id,
            'lead_id' => $this->lead_id,
            'external_call_id' => $this->external_call_id,
            'direction' => $this->direction?->value ?? $this->direction,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'status' => $this->status?->value ?? $this->status,
            'duration_seconds' => $this->duration_seconds,
            'cost_usd' => $this->cost_usd,
            'transcript' => $this->transcript,
            'summary' => $this->summary,
            'recording_url' => $this->recording_url,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'slug' => $this->provider?->slug,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'name' => $this->employee?->name,
            ]),
            'lead' => $this->whenLoaded('lead', fn () => [
                'id' => $this->lead?->id,
                'full_name' => $this->lead?->full_name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
