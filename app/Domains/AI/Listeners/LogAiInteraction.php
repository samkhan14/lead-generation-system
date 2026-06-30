<?php

namespace App\Domains\AI\Listeners;

use App\Domains\AI\Events\AiResponseReceived;
use Illuminate\Support\Facades\Log;

class LogAiInteraction
{
    public function handle(AiResponseReceived $event): void
    {
        $log = $event->log;

        if ($log === null) {
            return;
        }

        Log::info('AI interaction completed', [
            'log_uuid' => $log->uuid,
            'employee_id' => $log->ai_employee_id,
            'provider_id' => $log->ai_provider_id,
            'model_id' => $log->ai_model_id,
            'status' => $log->status?->value ?? $log->status,
            'total_tokens' => $log->total_tokens,
            'cost_usd' => $log->cost_usd,
            'latency_ms' => $log->latency_ms,
        ]);
    }
}
