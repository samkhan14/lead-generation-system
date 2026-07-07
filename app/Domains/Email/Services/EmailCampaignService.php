<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Enums\EmailCampaignStatus;
use App\Domains\Email\Enums\EmailSendStatus;
use App\Domains\Email\Models\EmailCampaign;
use App\Domains\Email\Models\EmailSend;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class EmailCampaignService
{
    public function __construct(
        private EmailGateway $gateway,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): EmailCampaign
    {
        return EmailCampaign::query()->create([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'html_body' => $data['html_body'],
            'text_body' => $data['text_body'] ?? strip_tags($data['html_body']),
            'from_name' => $data['from_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'status' => EmailCampaignStatus::Draft,
            'email_provider_id' => $data['email_provider_id'] ?? null,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
            'ai_enhanced' => (bool) ($data['ai_enhanced'] ?? false),
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmailCampaign $campaign, array $data, ?User $user = null): EmailCampaign
    {
        if ($campaign->status !== EmailCampaignStatus::Draft) {
            throw new RuntimeException('Only draft campaigns can be edited.');
        }

        $campaign->update([
            ...collect($data)->only([
                'name', 'subject', 'html_body', 'text_body', 'from_name', 'from_email',
                'reply_to', 'email_provider_id', 'ai_enhanced', 'metadata',
            ])->all(),
            'updated_by' => $user?->id,
        ]);

        return $campaign->fresh();
    }

    /**
     * @param  array<int, int>|null  $leadIds
     */
    public function dispatchCampaign(EmailCampaign $campaign, ?array $leadIds = null, ?User $initiator = null): int
    {
        if (! in_array($campaign->status, [EmailCampaignStatus::Draft, EmailCampaignStatus::Scheduled], true)) {
            throw new RuntimeException('This campaign cannot be sent in its current status.');
        }

        $leads = $this->resolveRecipients($leadIds);
        $max = (int) config('email_platform.bulk.max_recipients_per_campaign', 500);

        if ($leads->count() > $max) {
            throw new RuntimeException("Campaign exceeds maximum of {$max} recipients.");
        }

        $campaign->update([
            'status' => EmailCampaignStatus::Sending,
            'sent_at' => now(),
        ]);

        $queued = 0;

        foreach ($leads as $lead) {
            if (blank($lead->email)) {
                continue;
            }

            $send = EmailSend::query()->create([
                'email_campaign_id' => $campaign->id,
                'email_provider_id' => $campaign->email_provider_id,
                'lead_id' => $lead->id,
                'initiated_by' => $initiator?->id,
                'to_email' => $lead->email,
                'to_name' => $lead->full_name,
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'reply_to' => $campaign->reply_to,
                'subject' => $campaign->subject,
                'html_body' => $campaign->html_body,
                'text_body' => $campaign->text_body,
                'status' => EmailSendStatus::Pending,
                'is_test' => false,
            ]);

            $this->gateway->queueSend(
                $send,
                $campaign->provider?->slug,
            );

            $queued++;
        }

        if ($queued === 0) {
            $campaign->update(['status' => EmailCampaignStatus::Draft]);

            throw new RuntimeException('No leads with email addresses were found for this campaign.');
        }

        $campaign->update(['status' => EmailCampaignStatus::Sent]);

        return $queued;
    }

    public function sendTest(EmailCampaign $campaign, string $toEmail, ?User $initiator = null): EmailSend
    {
        return $this->gateway->createAndQueueSingle([
            'email_campaign_id' => $campaign->id,
            'email_provider_id' => $campaign->email_provider_id,
            'to_email' => $toEmail,
            'to_name' => $initiator?->name,
            'from_email' => $campaign->from_email,
            'from_name' => $campaign->from_name,
            'reply_to' => $campaign->reply_to,
            'subject' => '[TEST] '.$campaign->subject,
            'html_body' => $campaign->html_body,
            'text_body' => $campaign->text_body,
            'is_test' => true,
            'metadata' => ['test_for_campaign_id' => $campaign->id],
        ], $initiator);
    }

    /**
     * @return Collection<int, Lead>
     */
    private function resolveRecipients(?array $leadIds): Collection
    {
        $query = Lead::query()->whereNotNull('email')->where('email', '!=', '');

        if ($leadIds !== null && $leadIds !== []) {
            $query->whereIn('id', $leadIds);
        }

        return $query->get();
    }
}
