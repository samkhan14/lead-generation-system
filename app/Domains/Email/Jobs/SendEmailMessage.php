<?php

namespace App\Domains\Email\Jobs;

use App\Domains\Email\Models\EmailSend;
use App\Domains\Email\Services\EmailGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendEmailMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public int $timeout = 120;

    public function __construct(
        public int $emailSendId,
        public ?string $preferredProviderSlug = null,
    ) {
        $this->onQueue(config('email_platform.queue', 'email'));
    }

    public function handle(EmailGateway $gateway): void
    {
        $send = EmailSend::query()->find($this->emailSendId);

        if ($send === null || $send->isTerminal()) {
            return;
        }

        $gateway->executeSend($send, $this->preferredProviderSlug);
    }

    public function failed(?Throwable $exception): void
    {
        $send = EmailSend::query()->find($this->emailSendId);

        if ($send === null || $send->isTerminal()) {
            return;
        }

        $send->update([
            'status' => \App\Domains\Email\Enums\EmailSendStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'Email job failed after retries.',
        ]);
    }
}
