<?php

namespace App\Http\Resources\Admin;

use App\Domains\AI\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiLog */
class AiLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ai_employee_id' => $this->ai_employee_id,
            'ai_provider_id' => $this->ai_provider_id,
            'ai_model_id' => $this->ai_model_id,
            'lead_id' => $this->lead_id,
            'request_type' => $this->request_type?->value ?? $this->request_type,
            'prompt_tokens' => $this->prompt_tokens,
            'completion_tokens' => $this->completion_tokens,
            'total_tokens' => $this->total_tokens,
            'cost_usd' => $this->cost_usd,
            'latency_ms' => $this->latency_ms,
            'status' => $this->status?->value ?? $this->status,
            'error_message' => $this->error_message,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'name' => $this->employee?->name,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'slug' => $this->provider?->slug,
            ]),
            'model' => $this->whenLoaded('model', fn () => [
                'id' => $this->model?->id,
                'name' => $this->model?->name,
                'slug' => $this->model?->slug,
            ]),
            'lead' => $this->whenLoaded('lead', fn () => [
                'id' => $this->lead?->id,
                'full_name' => $this->lead?->full_name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
