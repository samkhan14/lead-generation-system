<?php

namespace App\Domains\Voice\Services;

use App\Domains\Voice\DataTransferObjects\VoiceCallSession;
use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;

class VoiceCallManager
{
    public function createPending(
        VoiceProvider $provider,
        string $toNumber,
        ?int $employeeId = null,
        ?int $leadId = null,
        ?int $initiatedBy = null,
        VoiceCallDirection $direction = VoiceCallDirection::Outbound,
    ): VoiceCall {
        return VoiceCall::query()->create([
            'voice_provider_id' => $provider->id,
            'ai_employee_id' => $employeeId,
            'lead_id' => $leadId,
            'initiated_by' => $initiatedBy,
            'direction' => $direction,
            'to_number' => $toNumber,
            'status' => VoiceCallStatus::Pending,
        ]);
    }

    public function createQueued(
        VoiceProvider $provider,
        VoiceCallSession $session,
        ?int $employeeId = null,
        ?int $leadId = null,
        ?int $initiatedBy = null,
        VoiceCallDirection $direction = VoiceCallDirection::Outbound,
    ): VoiceCall {
        return VoiceCall::query()->create([
            'voice_provider_id' => $provider->id,
            'ai_employee_id' => $employeeId,
            'lead_id' => $leadId,
            'initiated_by' => $initiatedBy,
            'external_call_id' => $session->externalCallId,
            'direction' => $direction,
            'from_number' => $session->fromNumber,
            'to_number' => $session->toNumber,
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
    }

    public function applySession(VoiceCall $call, VoiceCallSession $session): VoiceCall
    {
        $updates = array_filter([
            'status' => $session->status,
            'from_number' => $session->fromNumber,
            'to_number' => $session->toNumber,
            'duration_seconds' => $session->durationSeconds,
            'cost_usd' => $session->costUsd,
            'transcript' => $session->transcript,
            'summary' => $session->summary,
            'recording_url' => $session->recordingUrl,
            'error_message' => $session->errorMessage,
        ], fn ($value) => $value !== null);

        if ($session->status === VoiceCallStatus::InProgress && $call->started_at === null) {
            $updates['started_at'] = now();
        }

        if ($session->status->isTerminal() && $call->ended_at === null) {
            $updates['ended_at'] = now();
        }

        if ($session->raw !== null) {
            $updates['metadata'] = array_merge($call->metadata ?? [], ['last_sync' => $session->raw]);
        }

        $call->update($updates);

        return $call->fresh();
    }
}
