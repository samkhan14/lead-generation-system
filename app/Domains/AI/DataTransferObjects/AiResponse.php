<?php

namespace App\Domains\AI\DataTransferObjects;

use App\Domains\AI\Models\AiLog;

readonly class AiResponse
{
    public function __construct(
        public bool $success,
        public ?string $content,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public float $costUsd,
        public int $latencyMs,
        public ?string $providerSlug,
        public ?string $modelSlug,
        public ?AiLog $log = null,
        public ?string $error = null,
        public bool $usedFallback = false,
    ) {}

    public static function failed(string $error, ?AiLog $log = null): self
    {
        return new self(
            success: false,
            content: null,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            costUsd: 0,
            latencyMs: 0,
            providerSlug: null,
            modelSlug: null,
            log: $log,
            error: $error,
        );
    }
}
