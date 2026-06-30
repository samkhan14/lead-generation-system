<?php

namespace Tests\Support\AI;

use App\Domains\AI\Contracts\AiTextGeneratorInterface;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\TextResponse;
use Throwable;

class FakeAiTextGenerator implements AiTextGeneratorInterface
{
    /** @var array<int, TextResponse|Throwable> */
    private array $queue = [];

    public function push(TextResponse|Throwable $item): self
    {
        $this->queue[] = $item;

        return $this;
    }

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
    ): TextResponse {
        $next = array_shift($this->queue);

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next ?? new TextResponse(
            'Suggested pitch: start with a website audit offer.',
            new Usage(120, 45),
            new Meta($provider->slug, $model->slug),
        );
    }
}
