<?php

namespace App\Domains\Voice\Services\Connectors;

use App\Domains\Voice\Contracts\VoiceProviderInterface;
use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractVoiceConnector implements VoiceProviderInterface
{
    protected function baseUrl(VoiceProvider $provider): string
    {
        $configured = $provider->api_base_url;
        $default = config('voice_platform.provider_defaults.'.$provider->slug.'.api_base_url');

        $url = $configured ?: $default;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException("Voice provider [{$provider->slug}] is missing api_base_url.");
        }

        return rtrim($url, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postJson(VoiceProvider $provider, string $path, array $payload): array
    {
        $response = Http::timeout($provider->timeout_seconds)
            ->withToken($provider->api_key)
            ->acceptJson()
            ->post($this->baseUrl($provider).$path, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Voice provider [{$provider->slug}] rejected request (HTTP {$response->status()}): {$response->body()}",
            );
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJson(VoiceProvider $provider, string $path): array
    {
        $response = Http::timeout($provider->timeout_seconds)
            ->withToken($provider->api_key)
            ->acceptJson()
            ->get($this->baseUrl($provider).$path);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Voice provider [{$provider->slug}] fetch failed (HTTP {$response->status()}): {$response->body()}",
            );
        }

        return $response->json() ?? [];
    }

    protected function deleteRequest(VoiceProvider $provider, string $path): void
    {
        $response = Http::timeout($provider->timeout_seconds)
            ->withToken($provider->api_key)
            ->acceptJson()
            ->delete($this->baseUrl($provider).$path);

        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException(
                "Voice provider [{$provider->slug}] cancel failed (HTTP {$response->status()}): {$response->body()}",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sessionFromPayload(array $data, ?string $externalCallId = null): VoiceCallSession
    {
        $callId = $externalCallId
            ?? (string) ($data['call_id'] ?? $data['id'] ?? $data['callId'] ?? '');

        if ($callId === '') {
            throw new RuntimeException('Voice provider response did not include a call identifier.');
        }

        return new VoiceCallSession(
            externalCallId: $callId,
            status: $this->mapStatus((string) ($data['call_status'] ?? $data['status'] ?? 'queued')),
            fromNumber: $data['from_number'] ?? $data['fromNumber'] ?? null,
            toNumber: $data['to_number'] ?? $data['toNumber'] ?? data_get($data, 'customer.number'),
            durationSeconds: isset($data['duration_ms'])
                ? (int) round(((int) $data['duration_ms']) / 1000)
                : (isset($data['duration']) ? (int) $data['duration'] : null),
            costUsd: isset($data['cost_usd']) ? (float) $data['cost_usd'] : null,
            transcript: is_string($data['transcript'] ?? null) ? $data['transcript'] : null,
            summary: is_string($data['summary'] ?? $data['call_analysis'] ?? null)
                ? ($data['summary'] ?? $data['call_analysis'])
                : null,
            recordingUrl: $data['recording_url'] ?? $data['recordingUrl'] ?? null,
            errorMessage: is_string($data['error_message'] ?? $data['error'] ?? null)
                ? ($data['error_message'] ?? $data['error'])
                : null,
            raw: $data,
        );
    }

    protected function mapStatus(string $providerStatus): VoiceCallStatus
    {
        return match (strtolower($providerStatus)) {
            'registered', 'queued', 'scheduled', 'pending' => VoiceCallStatus::Queued,
            'ringing', 'dialing' => VoiceCallStatus::Ringing,
            'ongoing', 'in-progress', 'in_progress', 'connected', 'active' => VoiceCallStatus::InProgress,
            'ended', 'completed', 'success', 'successful' => VoiceCallStatus::Completed,
            'failed', 'error' => VoiceCallStatus::Failed,
            'cancelled', 'canceled', 'stopped' => VoiceCallStatus::Cancelled,
            'no_answer', 'no-answer', 'busy', 'voicemail' => VoiceCallStatus::NoAnswer,
            default => VoiceCallStatus::Queued,
        };
    }
}
