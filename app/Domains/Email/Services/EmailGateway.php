<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\DataTransferObjects\EmailMessagePayload;
use App\Domains\Email\Enums\EmailSendStatus;
use App\Domains\Email\Models\EmailProvider;
use App\Domains\Email\Models\EmailSend;
use App\Models\User;
use RuntimeException;

class EmailGateway
{
    public function __construct(
        private EmailConnectorRegistry $connectors,
        private EmailProviderSelector $providerSelector,
    ) {}

    public function queueSend(EmailSend $send, ?string $preferredProviderSlug = null): EmailSend
    {
        $send->update([
            'status' => EmailSendStatus::Queued,
            'queued_at' => now(),
        ]);

        \App\Domains\Email\Jobs\SendEmailMessage::dispatch($send->id, $preferredProviderSlug);

        return $send->fresh();
    }

    public function executeSend(EmailSend $send, ?string $preferredProviderSlug = null): EmailSend
    {
        if ($send->isTerminal()) {
            return $send;
        }

        $send->loadMissing('provider');
        $send->update(['status' => EmailSendStatus::Sending]);

        $payload = $this->buildPayload($send);
        $providers = $this->providerSelector->orderedProviders(
            $preferredProviderSlug ?? $send->provider?->slug,
        );

        $lastError = 'No usable email providers.';

        foreach ($providers as $provider) {
            $attempts = max(1, (int) $provider->retry_count + 1);

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                $result = $this->connectors->get($provider->slug)->send($provider, $payload);

                if ($result->success) {
                    $send->update([
                        'email_provider_id' => $provider->id,
                        'status' => EmailSendStatus::Sent,
                        'provider_message_id' => $result->providerMessageId,
                        'error_message' => null,
                        'sent_at' => now(),
                        'metadata' => array_merge($send->metadata ?? [], ['raw' => $result->raw]),
                    ]);

                    return $send->fresh();
                }

                $lastError = $result->errorMessage ?? 'Unknown email provider error.';
            }
        }

        $send->update([
            'status' => EmailSendStatus::Failed,
            'error_message' => $lastError,
        ]);

        return $send->fresh();
    }

    public function createAndQueueSingle(array $data, ?User $initiator = null): EmailSend
    {
        $send = EmailSend::query()->create([
            'email_campaign_id' => $data['email_campaign_id'] ?? null,
            'email_provider_id' => $data['email_provider_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'initiated_by' => $initiator?->id,
            'to_email' => $data['to_email'],
            'to_name' => $data['to_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'subject' => $data['subject'],
            'html_body' => $data['html_body'],
            'text_body' => $data['text_body'] ?? strip_tags($data['html_body']),
            'status' => EmailSendStatus::Pending,
            'is_test' => (bool) ($data['is_test'] ?? false),
            'metadata' => $data['metadata'] ?? [],
        ]);

        $preferredSlug = isset($data['email_provider_id'])
            ? EmailProvider::query()->find($data['email_provider_id'])?->slug
            : null;

        return $this->queueSend($send, $preferredSlug);
    }

    private function buildPayload(EmailSend $send): EmailMessagePayload
    {
        $fromEmail = $send->from_email;
        $fromName = $send->from_name;

        if (blank($fromEmail) && $send->provider !== null) {
            $fromEmail = $send->provider->defaultFromEmail();
            $fromName = $fromName ?: $send->provider->defaultFromName();
        }

        if (blank($fromEmail)) {
            throw new RuntimeException('From email is required to send this message.');
        }

        return new EmailMessagePayload(
            toEmail: $send->to_email,
            subject: $send->subject,
            htmlBody: $send->html_body,
            toName: $send->to_name,
            fromEmail: $fromEmail,
            fromName: $fromName,
            replyTo: $send->reply_to ?: $send->provider?->defaultReplyTo(),
            textBody: $send->text_body,
        );
    }
}
