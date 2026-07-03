<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Models\Lead;
use App\Models\User;
use App\Support\LeadIdentifiers;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class VoiceGateway
{
    public function __construct(
        private VoiceConnectorRegistry $connectors,
        private VoiceProviderSelector $providerSelector,
        private VoiceCallManager $callManager,
        private VoiceContextBuilder $contextBuilder,
    ) {}

    /**
     * Synchronous outbound call — used by smoke tests and direct gateway usage.
     */
    public function initiateOutbound(
        AiEmployee $employee,
        Lead $lead,
        ?User $initiator = null,
        ?string $preferredProviderSlug = null,
    ): VoiceCall {
        $this->assertEmployeeCanCall($employee);

        $request = $this->buildOutboundRequest($employee, $lead);
        $providers = $this->providerSelector->orderedProviders($preferredProviderSlug);
        $session = $this->dialProviders($providers, $request);

        if ($session instanceof VoiceCallSession) {
            return $this->callManager->createQueued(
                provider: $this->resolveProviderForSession($providers, $session),
                session: $session,
                employeeId: $employee->id,
                leadId: $lead->id,
                initiatedBy: $initiator?->id,
                direction: VoiceCallDirection::Outbound,
            );
        }

        return VoiceCall::query()->create([
            'voice_provider_id' => $providers->first()->id,
            'ai_employee_id' => $employee->id,
            'lead_id' => $lead->id,
            'initiated_by' => $initiator?->id,
            'direction' => VoiceCallDirection::Outbound,
            'to_number' => $request->toNumber,
            'status' => VoiceCallStatus::Failed,
            'error_message' => $session,
        ]);
    }

    /**
     * Execute a pending queued voice call record (async job path).
     */
    public function executePending(VoiceCall $call): VoiceCall
    {
        $call->loadMissing(['employee', 'lead', 'provider']);

        if ($call->employee === null || $call->lead === null) {
            return $this->markFailed($call, 'Voice call is missing employee or lead context.');
        }

        if ($call->status !== VoiceCallStatus::Pending) {
            return $call;
        }

        try {
            $this->assertEmployeeCanCall($call->employee);
        } catch (RuntimeException $exception) {
            return $this->markFailed($call, $exception->getMessage());
        }

        $request = $this->buildOutboundRequest($call->employee, $call->lead);
        $preferredSlug = $call->provider?->slug;

        try {
            $providers = $this->providerSelector->orderedProviders($preferredSlug);
        } catch (RuntimeException $exception) {
            return $this->markFailed($call, $exception->getMessage());
        }

        $session = $this->dialProviders($providers, $request);

        if ($session instanceof VoiceCallSession) {
            $call->update([
                'voice_provider_id' => $this->resolveProviderForSession($providers, $session)->id,
                'external_call_id' => $session->externalCallId,
                'from_number' => $session->fromNumber,
                'to_number' => $session->toNumber ?? $request->toNumber,
                'status' => $session->status,
                'duration_seconds' => $session->durationSeconds,
                'cost_usd' => $session->costUsd,
                'transcript' => $session->transcript,
                'summary' => $session->summary,
                'recording_url' => $session->recordingUrl,
                'error_message' => $session->errorMessage,
                'metadata' => $session->raw,
                'started_at' => $session->status === VoiceCallStatus::InProgress ? now() : null,
            ]);

            return $call->fresh();
        }

        return $this->markFailed($call, $session);
    }

    public function resolvePhoneForLead(Lead $lead): string
    {
        return $this->resolveLeadPhone($lead);
    }

    public function syncCall(VoiceCall $call): VoiceCall
    {
        if ($call->external_call_id === null) {
            throw new RuntimeException('Voice call has no external identifier to sync.');
        }

        $call->loadMissing('provider');
        $provider = $call->provider;

        if ($provider === null) {
            throw new RuntimeException('Voice call is missing its provider.');
        }

        $connector = $this->connectors->get($provider->slug);
        $session = $connector->fetchCall($provider, $call->external_call_id);

        return $this->callManager->applySession($call, $session);
    }

    public function cancelCall(VoiceCall $call): VoiceCall
    {
        if ($call->external_call_id === null || $call->isTerminal()) {
            return $call;
        }

        $call->loadMissing('provider');
        $provider = $call->provider;

        if ($provider !== null) {
            $this->connectors->get($provider->slug)->cancelCall($provider, $call->external_call_id);
        }

        $call->update([
            'status' => VoiceCallStatus::Cancelled,
            'ended_at' => now(),
        ]);

        return $call->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(VoiceProvider $provider, array $payload): ?VoiceCall
    {
        $session = $this->connectors->get($provider->slug)->parseWebhook($provider, $payload);

        if ($session === null) {
            return null;
        }

        $call = VoiceCall::query()
            ->where('voice_provider_id', $provider->id)
            ->where('external_call_id', $session->externalCallId)
            ->first();

        if ($call === null) {
            return null;
        }

        return $this->callManager->applySession($call, $session);
    }

    private function buildOutboundRequest(AiEmployee $employee, Lead $lead): VoiceOutboundRequest
    {
        return new VoiceOutboundRequest(
            employee: $employee,
            lead: $lead,
            toNumber: $this->resolveLeadPhone($lead),
            agentId: $employee->voice_id,
            dynamicVariables: $this->contextBuilder->dynamicVariables($employee, $lead),
            metadata: [
                'lead_uuid' => $lead->uuid,
                'employee_uuid' => $employee->uuid,
            ],
        );
    }

    /**
     * @param  Collection<int, VoiceProvider>  $providers
     * @return VoiceCallSession|string error message
     */
    private function dialProviders(Collection $providers, VoiceOutboundRequest $request): VoiceCallSession|string
    {
        $lastError = null;

        foreach ($providers as $provider) {
            $attempts = max(1, $provider->retry_count + 1);

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $connector = $this->connectors->get($provider->slug);
                    $outboundRequest = $this->withProviderDefaults($request, $provider);

                    return $connector->initiateOutbound($provider, $outboundRequest);
                } catch (Throwable $exception) {
                    $lastError = $exception->getMessage();
                }
            }
        }

        return $lastError ?? 'All voice providers failed.';
    }

    /**
     * @param  Collection<int, VoiceProvider>  $providers
     */
    private function resolveProviderForSession(Collection $providers, VoiceCallSession $session): VoiceProvider
    {
        return $providers->first() ?? throw new RuntimeException('No voice provider available.');
    }

    private function markFailed(VoiceCall $call, string $message): VoiceCall
    {
        $call->update([
            'status' => VoiceCallStatus::Failed,
            'error_message' => $message,
            'ended_at' => now(),
        ]);

        return $call->fresh();
    }

    private function assertEmployeeCanCall(AiEmployee $employee): void
    {
        $eligibleRoles = config('voice_platform.eligible_employee_roles', []);

        $role = $employee->role?->value ?? $employee->role;

        if (! in_array($role, $eligibleRoles, true)) {
            throw new RuntimeException("AI employee [{$employee->name}] is not configured for voice calls.");
        }
    }

    private function resolveLeadPhone(Lead $lead): string
    {
        $raw = trim((string) $lead->phone);

        if ($raw !== '' && str_starts_with($raw, '+')) {
            return $raw;
        }

        $digits = LeadIdentifiers::normalizePhone($lead->phone);

        if ($digits === null || $digits === '') {
            throw new RuntimeException('Lead does not have a callable phone number.');
        }

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        return '+'.$digits;
    }

    private function withProviderDefaults(VoiceOutboundRequest $request, VoiceProvider $provider): VoiceOutboundRequest
    {
        return new VoiceOutboundRequest(
            employee: $request->employee,
            lead: $request->lead,
            toNumber: $request->toNumber,
            fromNumber: $request->fromNumber ?? $provider->defaultFromNumber(),
            agentId: $request->agentId ?? $provider->defaultAgentId(),
            dynamicVariables: $request->dynamicVariables,
            metadata: $request->metadata,
        );
    }
}
