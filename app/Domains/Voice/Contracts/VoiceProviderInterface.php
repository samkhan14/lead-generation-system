<?php

namespace App\Domains\Voice\Contracts;

use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Models\VoiceProvider;

interface VoiceProviderInterface
{
    public function slug(): string;

    public function initiateOutbound(VoiceProvider $provider, VoiceOutboundRequest $request): VoiceCallSession;

    public function fetchCall(VoiceProvider $provider, string $externalCallId): VoiceCallSession;

    public function cancelCall(VoiceProvider $provider, string $externalCallId): void;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(VoiceProvider $provider, array $payload): ?VoiceCallSession;
}
