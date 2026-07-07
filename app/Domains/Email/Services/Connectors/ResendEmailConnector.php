<?php

namespace App\Domains\Email\Services\Connectors;

use App\Domains\Email\Contracts\EmailProviderInterface;
use App\Domains\Email\DataTransferObjects\EmailDispatchResult;
use App\Domains\Email\DataTransferObjects\EmailMessagePayload;
use App\Domains\Email\Models\EmailProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ResendEmailConnector implements EmailProviderInterface
{
    public function slug(): string
    {
        return 'resend';
    }

    public function send(EmailProvider $provider, EmailMessagePayload $message): EmailDispatchResult
    {
        $baseUrl = rtrim($provider->api_base_url ?: 'https://api.resend.com', '/');
        $from = $this->formatFrom($message);

        try {
            $response = Http::timeout($provider->timeout_seconds)
                ->withToken((string) $provider->api_key)
                ->acceptJson()
                ->post("{$baseUrl}/emails", array_filter([
                    'from' => $from,
                    'to' => [$message->toEmail],
                    'subject' => $message->subject,
                    'html' => $message->htmlBody,
                    'text' => $message->textBody,
                    'reply_to' => $message->replyTo,
                ]));

            if (! $response->successful()) {
                return new EmailDispatchResult(
                    success: false,
                    errorMessage: $response->json('message') ?? $response->body(),
                    raw: ['status' => $response->status(), 'body' => $response->json()],
                );
            }

            return new EmailDispatchResult(
                success: true,
                providerMessageId: (string) ($response->json('id') ?? 'resend_'.Str::uuid()),
                raw: $response->json() ?? [],
            );
        } catch (Throwable $exception) {
            return new EmailDispatchResult(
                success: false,
                errorMessage: $exception->getMessage(),
            );
        }
    }

    private function formatFrom(EmailMessagePayload $message): string
    {
        if (filled($message->fromName) && filled($message->fromEmail)) {
            return "{$message->fromName} <{$message->fromEmail}>";
        }

        return (string) $message->fromEmail;
    }
}
