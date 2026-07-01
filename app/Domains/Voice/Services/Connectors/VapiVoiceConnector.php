<?php

namespace App\Domains\Voice\Services\Connectors;

use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Models\VoiceProvider;
use RuntimeException;

class VapiVoiceConnector extends AbstractVoiceConnector
{
    public function slug(): string
    {
        return 'vapi';
    }

    public function initiateOutbound(VoiceProvider $provider, VoiceOutboundRequest $request): VoiceCallSession
    {
        $phoneNumberId = $provider->phoneNumberId();

        if ($phoneNumberId === null) {
            throw new RuntimeException('Vapi outbound call requires phone_number_id in provider metadata.');
        }

        $assistantId = $request->agentId ?? $request->employee->voice_id ?? $provider->defaultAgentId();

        if ($assistantId === null) {
            throw new RuntimeException('Vapi outbound call requires an assistant id.');
        }

        $payload = [
            'assistantId' => $assistantId,
            'phoneNumberId' => $phoneNumberId,
            'customer' => [
                'number' => $request->toNumber,
                'name' => $request->lead->full_name ?: null,
            ],
        ];

        if ($request->metadata !== []) {
            $payload['metadata'] = $request->metadata;
        }

        $data = $this->postJson($provider, '/call', $payload);

        return $this->sessionFromPayload($data);
    }

    public function fetchCall(VoiceProvider $provider, string $externalCallId): VoiceCallSession
    {
        $data = $this->getJson($provider, '/call/'.urlencode($externalCallId));

        return $this->sessionFromPayload($data, $externalCallId);
    }

    public function cancelCall(VoiceProvider $provider, string $externalCallId): void
    {
        $this->deleteRequest($provider, '/call/'.urlencode($externalCallId));
    }

    public function parseWebhook(VoiceProvider $provider, array $payload): ?VoiceCallSession
    {
        $call = $payload['call'] ?? $payload['message']['call'] ?? $payload;

        if (! is_array($call)) {
            return null;
        }

        $callId = $call['id'] ?? null;

        if (! is_string($callId) || $callId === '') {
            return null;
        }

        return $this->sessionFromPayload($call, $callId);
    }
}
