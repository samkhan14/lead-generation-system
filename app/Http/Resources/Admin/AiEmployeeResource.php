<?php

namespace App\Http\Resources\Admin;

use App\Domains\AI\Models\AiEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiEmployee */
class AiEmployeeResource extends JsonResource
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
            'role' => $this->role?->value ?? $this->role,
            'role_label' => $this->role?->label(),
            'department' => $this->department,
            'description' => $this->description,
            'system_prompt' => $this->system_prompt,
            'behavior_prompt' => $this->behavior_prompt,
            'knowledge_sources' => $this->knowledge_sources ?? [],
            'allowed_actions' => $this->allowed_actions ?? [],
            'allowed_tools' => $this->allowed_tools ?? [],
            'memory_enabled' => $this->memory_enabled,
            'context_window' => $this->context_window,
            'temperature' => $this->temperature,
            'ai_provider_id' => $this->ai_provider_id,
            'ai_model_id' => $this->ai_model_id,
            'fallback_provider_id' => $this->fallback_provider_id,
            'fallback_model_id' => $this->fallback_model_id,
            'voice_id' => $this->voice_id,
            'language' => $this->language,
            'working_hours' => $this->working_hours,
            'status' => $this->status?->value ?? $this->status,
            'is_operational' => $this->isOperational(),
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
            'fallback_provider' => $this->whenLoaded('fallbackProvider', fn () => [
                'id' => $this->fallbackProvider?->id,
                'name' => $this->fallbackProvider?->name,
            ]),
            'fallback_model' => $this->whenLoaded('fallbackModel', fn () => [
                'id' => $this->fallbackModel?->id,
                'name' => $this->fallbackModel?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
