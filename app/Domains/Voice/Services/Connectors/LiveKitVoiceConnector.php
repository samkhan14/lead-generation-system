<?php

namespace App\Domains\Voice\Services\Connectors;

use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Models\VoiceProvider;
use RuntimeException;

/**
 * LiveKit telephony varies by deployment (SIP trunk, Cloud). This connector
 * posts to a configurable outbound path stored in provider metadata.
 */
class LiveKitVoiceConnector extends AbstractVoiceConnector
{
    public function slug(): string
    {
        return 'livekit';
    }

    public function initiateOutbound(VoiceProvider $provider, VoiceOutboundRequest $request): VoiceCallSession
    {
        $fromNumber = $request->fromNumber ?? $provider->defaultFromNumber();
        $roomName = data_get($request->metadata, 'room_name', 'call_'.$request->lead->uuid);
        $path = (string) data_get($provider->metadata, 'outbound_path', '/sip/outbound');

        if ($fromNumber === null) {
            throw new RuntimeException('LiveKit outbound call requires a from_number.');
        }

        $payload = [
            'room_name' => $roomName,
            'from_number' => $fromNumber,
            'to_number' => $request->toNumber,
            'participant_identity' => 'lead_'.$request->lead->id,
            'agent_id' => $request->agentId ?? $request->employee->voice_id ?? $provider->defaultAgentId(),
            'metadata' => $request->metadata,
            'dynamic_variables' => $request->dynamicVariables,
        ];

        $data = $this->postJson($provider, $path, array_filter($payload, fn ($value) => $value !== null));

        return $this->sessionFromPayload($data);
    }

    public function fetchCall(VoiceProvider $provider, string $externalCallId): VoiceCallSession
    {
        $path = (string) data_get($provider->metadata, 'fetch_path', '/sip/calls/').urlencode($externalCallId);
        $data = $this->getJson($provider, $path);

        return $this->sessionFromPayload($data, $externalCallId);
    }

    public function cancelCall(VoiceProvider $provider, string $externalCallId): void
    {
        $path = (string) data_get($provider->metadata, 'cancel_path', '/sip/calls/').urlencode($externalCallId);
        $this->deleteRequest($provider, $path);
    }

    public function parseWebhook(VoiceProvider $provider, array $payload): ?VoiceCallSession
    {
        $callId = $payload['call_id'] ?? $payload['participant_id'] ?? $payload['id'] ?? null;

        if (! is_string($callId) || $callId === '') {
            return null;
        }

        return $this->sessionFromPayload($payload, $callId);
    }
}
