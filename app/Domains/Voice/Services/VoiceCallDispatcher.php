<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\Voice\DataTransferObjects\BulkVoiceCallResult;
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
    ): BulkVoiceCallResult {
        $queued = 0;
        $skipped = 0;
        $queuedUuids = [];
        $skippedDetails = [];

        foreach ($leads as $lead) {
            if (! $lead instanceof Lead) {
                continue;
            }

            if (! filled($lead->phone)) {
                $skipped++;
                $skippedDetails[] = [
                    'lead_id' => $lead->id,
                    'reason' => 'Lead has no phone number.',
                ];

                continue;
            }

            try {
                $call = $this->queueOutbound($employee, $lead, $initiator);
                $queued++;
                $queuedUuids[] = $call->uuid;
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $skipped++;
                $skippedDetails[] = [
                    'lead_id' => $lead->id,
                    'reason' => collect($exception->errors())->flatten()->first()
                        ?? 'Could not queue call.',
                ];
            } catch (\RuntimeException $exception) {
                $skipped++;
                $skippedDetails[] = [
                    'lead_id' => $lead->id,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return new BulkVoiceCallResult(
            queued: $queued,
            skipped: $skipped,
            queuedCallUuids: $queuedUuids,
            skippedDetails: $skippedDetails,
        );
    }
}
