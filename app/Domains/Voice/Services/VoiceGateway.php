<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Services\ContextBuilder;
use App\Domains\Voice\DataTransferObjects\VoiceOutboundRequest;
use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Models\Lead;
use App\Models\User;
use App\Support\LeadIdentifiers;
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

    public function initiateOutbound(
        AiEmployee $employee,
        Lead $lead,
        ?User $initiator = null,
        ?string $preferredProviderSlug = null,
    ): VoiceCall {
        $this->assertEmployeeCanCall($employee);

        $toNumber = $this->resolveLeadPhone($lead);
        $dynamicVariables = $this->contextBuilder->dynamicVariables($employee, $lead);
        $request = new VoiceOutboundRequest(
            employee: $employee,
            lead: $lead,
            toNumber: $toNumber,
            agentId: $employee->voice_id,
            dynamicVariables: $dynamicVariables,
            metadata: [
                'lead_uuid' => $lead->uuid,
                'employee_uuid' => $employee->uuid,
            ],
        );

        $providers = $this->providerSelector->orderedProviders($preferredProviderSlug);
        $lastError = null;

        foreach ($providers as $provider) {
            $attempts = max(1, $provider->retry_count + 1);

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $connector = $this->connectors->get($provider->slug);
                    $outboundRequest = $this->withProviderDefaults($request, $provider);
                    $session = $connector->initiateOutbound($provider, $outboundRequest);

                    return $this->callManager->createQueued(
                        provider: $provider,
                        session: $session,
                        employeeId: $employee->id,
                        leadId: $lead->id,
                        initiatedBy: $initiator?->id,
                        direction: VoiceCallDirection::Outbound,
                    );
                } catch (Throwable $exception) {
                    $lastError = $exception->getMessage();
                }
            }
        }

        $failedCall = VoiceCall::query()->create([
            'voice_provider_id' => $providers->first()->id,
            'ai_employee_id' => $employee->id,
            'lead_id' => $lead->id,
            'initiated_by' => $initiator?->id,
            'direction' => VoiceCallDirection::Outbound,
            'to_number' => $toNumber,
            'status' => VoiceCallStatus::Failed,
            'error_message' => $lastError ?? 'All voice providers failed.',
        ]);

        return $failedCall;
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
