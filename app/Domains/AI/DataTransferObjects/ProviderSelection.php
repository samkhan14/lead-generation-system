<?php

namespace App\Domains\AI\DataTransferObjects;

use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;

readonly class ProviderSelection
{
    public function __construct(
        public AiProvider $provider,
        public AiModel $model,
        public bool $isFallback = false,
    ) {}
}
