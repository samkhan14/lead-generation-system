<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AiTextGeneratorInterface;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laravel\Ai\Ai;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Responses\TextResponse;

class LaravelAiConnector implements AiTextGeneratorInterface
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
    ): TextResponse {
        $runtimeName = $this->registerRuntimeProvider($provider);
        $textProvider = Ai::textProvider($runtimeName);

        return $textProvider->textGateway()->generateText(
            provider: $textProvider,
            model: $model->slug,
            instructions: $instructions,
            messages: $messages,
            tools: [],
            schema: null,
            options: $options,
            timeout: $timeout ?? $provider->timeout_seconds,
        );
    }

    public function driverFor(AiProvider $provider): string
    {
        $driver = config('ai_platform.provider_drivers.'.$provider->slug);

        if (! is_string($driver) || $driver === '') {
            throw new InvalidArgumentException("No Laravel AI driver mapped for provider [{$provider->slug}].");
        }

        return $driver;
    }

    private function registerRuntimeProvider(AiProvider $provider): string
    {
        $prefix = (string) config('ai_platform.runtime_provider_prefix', 'db_');
        $runtimeName = $prefix.$provider->id;

        $config = array_filter([
            'driver' => $this->driverFor($provider),
            'key' => $provider->api_key,
            'url' => $provider->api_base_url,
        ], fn ($value) => $value !== null && $value !== '');

        Config::set('ai.providers.'.$runtimeName, $config);

        return $runtimeName;
    }
}
