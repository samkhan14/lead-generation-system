<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Jobs\InitiateVoiceCall;
use App\Domains\Voice\Models\VoiceCall;
use App\Models\Lead;
use App\Models\User;

class VoiceCallDispatcher
{
    public function __construct(
        private VoiceCallConcurrencyGuard $concurrencyGuard,
        private VoiceProviderSelector $providerSelector,
        private VoiceCallManager $callManager,
        private VoiceGateway $voiceGateway,
    ) {}

    public function queueOutbound(
        AiEmployee $employee,
        Lead $lead,
        ?User $initiator = null,
    ): VoiceCall {
        $this->concurrencyGuard->assertCanQueue($employee, $lead);

        $toNumber = $this->voiceGateway->resolvePhoneForLead($lead);
        $provider = $this->providerSelector->orderedProviders()->first();

        $call = $this->callManager->createPending(
            provider: $provider,
            toNumber: $toNumber,
            employeeId: $employee->id,
            leadId: $lead->id,
            initiatedBy: $initiator?->id,
        );

        InitiateVoiceCall::dispatch($call);

        return $call;
    }

    public function queueBulkOutbound(
        AiEmployee $employee,
        iterable $leads,
        ?User $initiator = null,
    ): int {
        $queued = 0;

        foreach ($leads as $lead) {
            if (! $lead instanceof Lead) {
                continue;
            }

            try {
                $this->queueOutbound($employee, $lead, $initiator);
                $queued++;
            } catch (\Illuminate\Validation\ValidationException) {
                continue;
            }
        }

        return $queued;
    }
}
