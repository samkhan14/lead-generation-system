<?php

namespace App\Domains\Voice\Services\Connectors;

use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Models\VoiceProvider;
use RuntimeException;

class RetellVoiceConnector extends AbstractVoiceConnector
{
    public function slug(): string
    {
        return 'retell';
    }

    public function initiateOutbound(VoiceProvider $provider, VoiceOutboundRequest $request): VoiceCallSession
    {
        $fromNumber = $request->fromNumber ?? $provider->defaultFromNumber();

        if ($fromNumber === null) {
            throw new RuntimeException('Retell outbound call requires a from_number.');
        }

        $payload = [
            'from_number' => $fromNumber,
            'to_number' => $request->toNumber,
        ];

        $agentId = $request->agentId ?? $request->employee->voice_id ?? $provider->defaultAgentId();

        if ($agentId !== null) {
            $payload['override_agent_id'] = $agentId;
        }

        if ($request->dynamicVariables !== []) {
            $payload['retell_llm_dynamic_variables'] = $request->dynamicVariables;
        }

        if ($request->metadata !== []) {
            $payload['metadata'] = $request->metadata;
        }

        $data = $this->postJson($provider, '/v2/create-phone-call', $payload);

        return $this->sessionFromPayload($data);
    }

    public function fetchCall(VoiceProvider $provider, string $externalCallId): VoiceCallSession
    {
        $data = $this->getJson($provider, '/v2/get-call/'.urlencode($externalCallId));

        return $this->sessionFromPayload($data, $externalCallId);
    }

    public function cancelCall(VoiceProvider $provider, string $externalCallId): void
    {
        $this->deleteRequest($provider, '/v2/delete-call/'.urlencode($externalCallId));
    }

    public function parseWebhook(VoiceProvider $provider, array $payload): ?VoiceCallSession
    {
        $call = $payload['call'] ?? $payload;

        if (! is_array($call)) {
            return null;
        }

        $callId = $call['call_id'] ?? null;

        if (! is_string($callId) || $callId === '') {
            return null;
        }

        return $this->sessionFromPayload($call, $callId);
    }
}
