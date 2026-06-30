<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AiTextGeneratorInterface;
use App\Domains\AI\DataTransferObjects\AiRequest;
use App\Domains\AI\DataTransferObjects\AiResponse;
use App\Domains\AI\DataTransferObjects\PromptContext;
use App\Domains\AI\DataTransferObjects\ProviderSelection;
use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Events\AiRequestSent;
use App\Domains\AI\Events\AiResponseReceived;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\TextResponse;
use RuntimeException;
use Throwable;

class AiGateway
{
    public function __construct(
        private AiTextGeneratorInterface $textGenerator,
        private ProviderSelector $providerSelector,
        private PromptBuilder $promptBuilder,
        private CostTracker $costTracker,
    ) {}

    public function send(
        AiEmployee $employee,
        PromptContext $context,
        AiRequestType $type = AiRequestType::Chat,
    ): AiResponse {
        $userMessage = $this->promptBuilder->buildUserMessage($context);

        if ($userMessage === '') {
            return AiResponse::failed('User message is required.');
        }

        $selections = $this->providerSelector->orderedSelections($employee);
        $lastError = null;

        foreach ($selections as $selection) {
            $request = new AiRequest($employee, $context, $type);
            Event::dispatch(new AiRequestSent($request, $selection));

            try {
                $response = $this->attemptSelection($employee, $context, $selection, $type, $userMessage);
                Event::dispatch(new AiResponseReceived($response, $response->log));

                return $response;
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();
                $failedLog = $this->logFailure($employee, $selection, $type, $context, $lastError);
                Event::dispatch(new AiResponseReceived(
                    AiResponse::failed($lastError, $failedLog),
                    $failedLog,
                ));
            }
        }

        return AiResponse::failed($lastError ?? 'All AI providers failed.');
    }

    private function attemptSelection(
        AiEmployee $employee,
        PromptContext $context,
        ProviderSelection $selection,
        AiRequestType $type,
        string $userMessage,
    ): AiResponse {
        $instructions = $this->promptBuilder->buildInstructions($context);
        $messages = [new UserMessage($userMessage)];
        $options = new TextGenerationOptions(
            maxTokens: $this->resolveMaxTokens($employee, $selection),
            temperature: (float) $employee->temperature,
        );

        $startedAt = microtime(true);
        $textResponse = $this->generateWithRetries(
            $selection,
            $instructions,
            $messages,
            $options,
        );
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $promptTokens = $textResponse->usage->promptTokens;
        $completionTokens = $textResponse->usage->completionTokens;
        $totalTokens = $promptTokens + $completionTokens;
        $costUsd = $this->costTracker->calculate($selection->model, $promptTokens, $completionTokens);

        $response = new AiResponse(
            success: true,
            content: $textResponse->text,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            costUsd: $costUsd,
            latencyMs: $latencyMs,
            providerSlug: $selection->provider->slug,
            modelSlug: $selection->model->slug,
            usedFallback: $selection->isFallback,
        );

        $requestPayload = [
            'lead_id' => $context->lead?->id,
            'instructions_preview' => mb_substr($instructions, 0, 500),
            'user_message' => $userMessage,
            'used_fallback' => $selection->isFallback,
        ];

        $log = $this->costTracker->log(
            employee: $employee,
            selection: $selection,
            requestType: $type,
            response: $response,
            latencyMs: $latencyMs,
            requestPayload: $requestPayload,
            responsePayload: [
                'text_preview' => mb_substr($textResponse->text, 0, 1000),
                'provider' => $textResponse->meta->provider,
                'model' => $textResponse->meta->model,
            ],
        );

        return new AiResponse(
            success: true,
            content: $textResponse->text,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            costUsd: $costUsd,
            latencyMs: $latencyMs,
            providerSlug: $selection->provider->slug,
            modelSlug: $selection->model->slug,
            log: $log,
            usedFallback: $selection->isFallback,
        );
    }

    /**
     * @param  array<int, UserMessage>  $messages
     */
    private function generateWithRetries(
        ProviderSelection $selection,
        string $instructions,
        array $messages,
        TextGenerationOptions $options,
    ): TextResponse {
        $maxAttempts = max(1, (int) $selection->provider->retry_count + 1);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->textGenerator->generate(
                    $selection->provider,
                    $selection->model,
                    $instructions,
                    $messages,
                    $options,
                );
            } catch (Throwable $exception) {
                $lastException = $exception;

                if ($attempt < $maxAttempts) {
                    usleep(100_000 * $attempt);
                }
            }
        }

        throw new RuntimeException($lastException?->getMessage() ?? 'AI generation failed.');
    }

    private function resolveMaxTokens(AiEmployee $employee, ProviderSelection $selection): ?int
    {
        $limits = array_filter([
            $employee->context_window,
            $selection->model->max_tokens,
        ]);

        return $limits === [] ? null : min($limits);
    }

    private function logFailure(
        AiEmployee $employee,
        ProviderSelection $selection,
        AiRequestType $type,
        PromptContext $context,
        string $errorMessage,
    ): AiLog {
        $failedResponse = new AiResponse(
            success: false,
            content: null,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            costUsd: 0,
            latencyMs: 0,
            providerSlug: $selection->provider->slug,
            modelSlug: $selection->model->slug,
            error: $errorMessage,
            usedFallback: $selection->isFallback,
        );

        return $this->costTracker->log(
            employee: $employee,
            selection: $selection,
            requestType: $type,
            response: $failedResponse,
            latencyMs: 0,
            requestPayload: [
                'lead_id' => $context->lead?->id,
                'user_message' => $context->userMessage,
            ],
            responsePayload: null,
            errorMessage: $errorMessage,
        );
    }
}
