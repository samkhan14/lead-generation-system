<?php

namespace App\Domains\AI\Contracts;

use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Responses\TextResponse;

interface AiTextGeneratorInterface
{
    /**
     * @param  array<int, Message>  $messages
     */
    public function generate(
        AiProvider $provider,
        AiModel $model,
        string $instructions,
        array $messages,
        TextGenerationOptions $options,
        ?int $timeout = null,
    ): TextResponse;
}
