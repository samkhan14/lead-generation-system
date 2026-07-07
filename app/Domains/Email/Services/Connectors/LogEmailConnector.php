<?php

namespace App\Domains\Email\Services\Connectors;

use App\Domains\Email\Contracts\EmailProviderInterface;
use App\Domains\Email\DataTransferObjects\EmailDispatchResult;
use App\Domains\Email\DataTransferObjects\EmailMessagePayload;
use App\Domains\Email\Models\EmailProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogEmailConnector implements EmailProviderInterface
{
    public function slug(): string
    {
        return 'log';
    }

    public function send(EmailProvider $provider, EmailMessagePayload $message): EmailDispatchResult
    {
        $messageId = 'log_'.Str::uuid()->toString();

        Log::channel('stack')->info('Email (log driver)', [
            'provider' => $provider->slug,
            'to' => $message->toEmail,
            'subject' => $message->subject,
            'from' => $message->fromEmail,
            'html_preview' => Str::limit(strip_tags($message->htmlBody), 500),
        ]);

        return new EmailDispatchResult(
            success: true,
            providerMessageId: $messageId,
            raw: ['driver' => 'log'],
        );
    }
}
