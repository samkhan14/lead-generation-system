<?php

namespace App\Domains\Email\Services;

use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Services\AiGateway;
use App\Domains\AI\Services\ContextBuilder;
use App\Domains\Email\Models\EmailCampaign;
use Illuminate\Support\Str;
use RuntimeException;

class EmailContentEnhancer
{
    public function __construct(
        private AiGateway $aiGateway,
        private ContextBuilder $contextBuilder,
    ) {}

    /**
     * @return array{subject: string, html_body: string, text_body: string|null}
     */
    public function enhance(string $subject, string $htmlBody, ?string $textBody = null, ?string $tone = null): array
    {
        $employee = $this->resolveEmployee();

        if (! $employee->isOperational()) {
            throw new RuntimeException('No usable AI employee is configured for email enhancement. Add an AI provider API key.');
        }

        $prompt = $this->buildPrompt($subject, $htmlBody, $textBody, $tone);
        $context = $this->contextBuilder->build(
            employee: $employee,
            userMessage: $prompt,
            metadata: ['feature' => 'email_enhancement'],
        );

        $response = $this->aiGateway->send($employee, $context, AiRequestType::Completion);

        if (! $response->success || blank($response->content)) {
            throw new RuntimeException($response->errorMessage ?? 'AI enhancement failed.');
        }

        return $this->parseResponse($response->content, $subject, $htmlBody, $textBody);
    }

    /**
     * @return array{subject: string, html_body: string, text_body: string|null}
     */
    private function parseResponse(string $content, string $subject, string $htmlBody, ?string $textBody): array
    {
        $json = $this->extractJson($content);

        if (is_array($json)) {
            return [
                'subject' => trim((string) ($json['subject'] ?? $subject)),
                'html_body' => trim((string) ($json['html_body'] ?? $htmlBody)),
                'text_body' => filled($json['text_body'] ?? null)
                    ? trim((string) $json['text_body'])
                    : strip_tags((string) ($json['html_body'] ?? $htmlBody)),
            ];
        }

        return [
            'subject' => $subject,
            'html_body' => trim($content),
            'text_body' => strip_tags(trim($content)),
        ];
    }

    private function buildPrompt(string $subject, string $htmlBody, ?string $textBody, ?string $tone): string
    {
        $toneLine = filled($tone) ? "Tone: {$tone}." : 'Tone: professional and concise.';

        return implode("\n\n", [
            config('email_platform.ai_enhancement.system_hint'),
            $toneLine,
            'Return valid JSON only with keys: subject, html_body, text_body.',
            'Current subject: '.$subject,
            'Current HTML body: '.$htmlBody,
            filled($textBody) ? 'Current plain text: '.$textBody : '',
        ]);
    }

    private function resolveEmployee(): AiEmployee
    {
        $preferred = config('email_platform.ai_enhancement.default_employee_name');

        $employee = AiEmployee::query()
            ->where('name', $preferred)
            ->first();

        if ($employee === null) {
            $employee = AiEmployee::query()->where('status', 'active')->orderBy('name')->first();
        }

        if ($employee === null) {
            throw new RuntimeException('No AI employee configured for email enhancement.');
        }

        return $employee;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $content): ?array
    {
        $content = trim($content);

        if (str_starts_with($content, '{')) {
            $decoded = json_decode($content, true);

            return is_array($decoded) ? $decoded : null;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    public function applyToCampaign(EmailCampaign $campaign, array $enhanced): EmailCampaign
    {
        $campaign->update([
            'subject' => $enhanced['subject'],
            'html_body' => $enhanced['html_body'],
            'text_body' => $enhanced['text_body'],
            'ai_enhanced' => true,
        ]);

        return $campaign->fresh();
    }
}
