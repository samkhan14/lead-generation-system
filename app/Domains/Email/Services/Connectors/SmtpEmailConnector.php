<?php

namespace App\Domains\Email\Services\Connectors;

use App\Domains\Email\Contracts\EmailProviderInterface;
use App\Domains\Email\DataTransferObjects\EmailDispatchResult;
use App\Domains\Email\DataTransferObjects\EmailMessagePayload;
use App\Domains\Email\Models\EmailProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SmtpEmailConnector implements EmailProviderInterface
{
    public function slug(): string
    {
        return 'smtp';
    }

    public function send(EmailProvider $provider, EmailMessagePayload $message): EmailDispatchResult
    {
        try {
            Mail::html($message->htmlBody, function ($mail) use ($message): void {
                $mail->to($message->toEmail, $message->toName)
                    ->subject($message->subject);

                if (filled($message->fromEmail)) {
                    $mail->from($message->fromEmail, $message->fromName);
                }

                if (filled($message->replyTo)) {
                    $mail->replyTo($message->replyTo);
                }

                if (filled($message->textBody)) {
                    $mail->text($message->textBody);
                }
            });

            return new EmailDispatchResult(
                success: true,
                providerMessageId: 'smtp_'.Str::uuid()->toString(),
                raw: ['driver' => 'smtp'],
            );
        } catch (Throwable $exception) {
            return new EmailDispatchResult(
                success: false,
                errorMessage: $exception->getMessage(),
            );
        }
    }
}
