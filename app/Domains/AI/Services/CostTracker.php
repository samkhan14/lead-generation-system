<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\DataTransferObjects\AiResponse;
use App\Domains\AI\DataTransferObjects\ProviderSelection;
use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use App\Domains\AI\Models\AiModel;
use Illuminate\Support\Str;

class CostTracker
{
    public function calculate(AiModel $model, int $promptTokens, int $completionTokens): float
    {
        $inputRate = (float) ($model->input_price_per_1k ?? 0);
        $outputRate = (float) ($model->output_price_per_1k ?? 0);

        $inputCost = ($promptTokens / 1000) * $inputRate;
        $outputCost = ($completionTokens / 1000) * $outputRate;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     */
    public function log(
        AiEmployee $employee,
        ProviderSelection $selection,
        AiRequestType $requestType,
        AiResponse $response,
        int $latencyMs,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
    ): AiLog {
        return AiLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'ai_employee_id' => $employee->id,
            'ai_provider_id' => $selection->provider->id,
            'ai_model_id' => $selection->model->id,
            'lead_id' => $requestPayload['lead_id'] ?? null,
            'request_type' => $requestType,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'total_tokens' => $response->totalTokens,
            'cost_usd' => $response->costUsd,
            'latency_ms' => $latencyMs,
            'status' => $response->success ? AiLogStatus::Success : $this->resolveFailureStatus($errorMessage),
            'error_message' => $errorMessage,
            'request_payload' => $this->truncatePayload($requestPayload),
            'response_payload' => $this->truncatePayload($responsePayload),
        ]);
    }

    private function resolveFailureStatus(?string $errorMessage): AiLogStatus
    {
        $message = strtolower((string) $errorMessage);

        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return AiLogStatus::RateLimited;
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return AiLogStatus::Timeout;
        }

        return AiLogStatus::Error;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function truncatePayload(?array $payload): ?array
    {
        if ($payload === null || ! config('ai_platform.log_payloads', true)) {
            return $payload;
        }

        $encoded = json_encode($payload);
        $max = (int) config('ai_platform.log_payload_max_chars', 12000);

        if ($encoded !== false && strlen($encoded) <= $max) {
            return $payload;
        }

        return [
            'truncated' => true,
            'preview' => Str::limit($encoded ?: '', $max),
        ];
    }
}
