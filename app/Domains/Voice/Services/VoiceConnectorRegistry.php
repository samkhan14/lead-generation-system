<?php

namespace App\Domains\Voice\Services;

use App\Domains\Voice\Contracts\VoiceProviderInterface;
use App\Domains\Voice\Services\Connectors\LiveKitVoiceConnector;
use App\Domains\Voice\Services\Connectors\RetellVoiceConnector;
use App\Domains\Voice\Services\Connectors\VapiVoiceConnector;
use InvalidArgumentException;

class VoiceConnectorRegistry
{
    /** @var array<string, VoiceProviderInterface> */
    private array $connectors;

    public function __construct(
        ?RetellVoiceConnector $retell = null,
        ?LiveKitVoiceConnector $livekit = null,
        ?VapiVoiceConnector $vapi = null,
    ) {
        $this->connectors = collect([
            $retell ?? new RetellVoiceConnector,
            $livekit ?? new LiveKitVoiceConnector,
            $vapi ?? new VapiVoiceConnector,
        ])->keyBy(fn (VoiceProviderInterface $connector) => $connector->slug())->all();
    }

    public function get(string $slug): VoiceProviderInterface
    {
        $driver = config('voice_platform.provider_drivers.'.$slug, $slug);

        if (! isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No voice connector registered for provider [{$slug}].");
        }

        return $this->connectors[$driver];
    }
}
