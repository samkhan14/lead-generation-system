<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Enums\LeadActivityType;
use App\Domains\Crm\Models\LeadActivity;
use App\Models\Lead;
use App\Models\User;

class LeadActivityService
{
    public function log(
        Lead $lead,
        LeadActivityType $type,
        ?string $subject,
        ?string $body,
        ?User $user = null,
        ?array $metadata = null,
    ): LeadActivity {
        return LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $user?->id,
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    public function logStatusChange(
        Lead $lead,
        string $from,
        string $to,
        User $user,
        ?string $lostReason = null,
    ): LeadActivity {
        $pipeline = app(LeadPipelineService::class);

        return $this->log(
            lead: $lead,
            type: LeadActivityType::StatusChange,
            subject: 'Pipeline stage updated',
            body: sprintf(
                'Stage changed from %s to %s.',
                $pipeline->stageLabel($from),
                $pipeline->stageLabel($to),
            ),
            user: $user,
            metadata: array_filter([
                'from' => $from,
                'to' => $to,
                'lost_reason' => $lostReason,
            ]),
        );
    }

    public function logAssignment(Lead $lead, ?int $fromId, ?int $toId, User $actor): LeadActivity
    {
        $fromName = $fromId ? User::query()->find($fromId)?->name : null;
        $toName = $toId ? User::query()->find($toId)?->name : null;

        $body = match (true) {
            $fromId === null && $toId !== null => "Assigned to {$toName}.",
            $fromId !== null && $toId === null => "Unassigned from {$fromName}.",
            default => "Reassigned from {$fromName} to {$toName}.",
        };

        return $this->log(
            lead: $lead,
            type: LeadActivityType::Assignment,
            subject: 'Lead assignment updated',
            body: $body,
            user: $actor,
            metadata: [
                'from_user_id' => $fromId,
                'to_user_id' => $toId,
            ],
        );
    }

    public function logManual(
        Lead $lead,
        LeadActivityType $type,
        string $subject,
        ?string $body,
        User $user,
    ): LeadActivity {
        if (! in_array($type, [
            LeadActivityType::Note,
            LeadActivityType::Call,
            LeadActivityType::Email,
            LeadActivityType::Meeting,
        ], true)) {
            throw new \InvalidArgumentException('Invalid manual activity type.');
        }

        return $this->log($lead, $type, $subject, $body, $user);
    }
}
