<?php

namespace App\Domains\Crm\Services;

use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Services\AiGateway;
use App\Domains\AI\Services\ContextBuilder;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Domains\BusinessKnowledge\Services\ServiceKnowledgeFormatter;
use App\Domains\Crm\Enums\LeadActivityType;
use App\Domains\Crm\Enums\QuoteStatus;
use App\Domains\Crm\Models\Quote;
use App\Models\Lead;
use App\Models\User;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Str;

class QuoteGeneratorService
{
    public function __construct(
        private ServiceCatalogService $catalogService,
        private ServiceKnowledgeFormatter $knowledgeFormatter,
        private AiGateway $aiGateway,
        private ContextBuilder $contextBuilder,
    ) {}

    /**
     * @param  array<int, int>  $serviceIds
     */
    public function generate(
        Lead $lead,
        array $serviceIds,
        User $user,
        ?int $dealId = null,
        ?string $notes = null,
    ): Quote {
        $services = Service::query()
            ->active()
            ->whereIn('id', $serviceIds)
            ->ordered()
            ->get();

        if ($services->isEmpty()) {
            throw new \InvalidArgumentException('Select at least one active service for the quote.');
        }

        $leadName = $lead->company ?: $lead->full_name;
        $title = "Proposal for {$leadName}";
        $subject = "Your custom proposal — {$leadName}";

        $content = $this->buildContent($lead, $services, $notes);

        $quote = Quote::query()->create([
            'lead_id' => $lead->id,
            'deal_id' => $dealId,
            'title' => $title,
            'subject' => $subject,
            'html_body' => $content['html_body'],
            'text_body' => $content['text_body'],
            'service_ids' => $services->pluck('id')->all(),
            'status' => QuoteStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'metadata' => array_filter(['notes' => $notes]),
        ]);

        app(LeadActivityService::class)->log(
            lead: $lead,
            type: LeadActivityType::System,
            subject: 'Quote drafted',
            body: "Quote \"{$quote->title}\" created as draft.",
            user: $user,
            metadata: ['quote_id' => $quote->id],
        );

        return $quote->fresh(['creator']);
    }

    public function markSent(Quote $quote, User $user): Quote
    {
        $quote->update([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
            'updated_by' => $user->id,
        ]);

        app(LeadActivityService::class)->log(
            lead: $quote->lead,
            type: LeadActivityType::QuoteSent,
            subject: 'Quote sent',
            body: "Quote \"{$quote->title}\" marked as sent.",
            user: $user,
            metadata: ['quote_id' => $quote->id],
        );

        return $quote->fresh();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Service>  $services
     * @return array{html_body: string, text_body: string}
     */
    private function buildContent(Lead $lead, $services, ?string $notes): array
    {
        $aiContent = $this->tryAiGeneration($lead, $services, $notes);

        if ($aiContent !== null) {
            return $aiContent;
        }

        return $this->buildTemplateContent($lead, $services, $notes);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Service>  $services
     * @return array{html_body: string, text_body: string}|null
     */
    private function tryAiGeneration(Lead $lead, $services, ?string $notes): ?array
    {
        $employee = AiEmployee::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->first();

        if ($employee === null || ! $employee->isOperational()) {
            return null;
        }

        $knowledge = $this->catalogService->activeKnowledgeForAi();
        $filtered = collect($knowledge)
            ->filter(fn ($item) => $services->contains('id', $item->id))
            ->values()
            ->all();

        $catalogBlock = $this->knowledgeFormatter->formatCatalogForPrompt($filtered);

        $prompt = implode("\n\n", array_filter([
            'Write a professional sales proposal email in HTML for the lead below.',
            'Use only services from the catalog. Do not invent pricing.',
            'Return JSON with keys: html_body, text_body.',
            "Lead: {$lead->full_name}, company: ".($lead->company ?: 'N/A').", website: ".($lead->website ?: 'N/A'),
            filled($notes) ? "Sales notes: {$notes}" : null,
            $catalogBlock,
        ]));

        $context = $this->contextBuilder->build(
            employee: $employee,
            userMessage: $prompt,
            metadata: ['feature' => 'quote_generation', 'lead_id' => $lead->id],
        );

        $response = $this->aiGateway->send($employee, $context, AiRequestType::Completion);

        if (! $response->success || blank($response->content)) {
            return null;
        }

        $json = $this->extractJson($response->content);

        if (! is_array($json) || blank($json['html_body'] ?? null)) {
            return null;
        }

        return [
            'html_body' => trim((string) $json['html_body']),
            'text_body' => trim((string) ($json['text_body'] ?? strip_tags((string) $json['html_body']))),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Service>  $services
     * @return array{html_body: string, text_body: string}
     */
    private function buildTemplateContent(Lead $lead, $services, ?string $notes): array
    {
        $leadName = $lead->first_name ?: $lead->full_name;
        $serviceItems = $services->map(function (Service $service) {
            $summary = $service->short_description ?: Str::limit(strip_tags($service->description ?? ''), 200);

            return "<li><strong>{$service->name}</strong> — {$summary}</li>";
        })->implode('');

        $notesBlock = filled($notes)
            ? '<p><strong>Notes from our team:</strong> '.e($notes).'</p>'
            : '';

        $html = <<<HTML
<p>Hi {$leadName},</p>
<p>Thank you for your interest. Based on what we know about your business, we recommend the following services:</p>
<ul>{$serviceItems}</ul>
{$notesBlock}
<p>We would love to schedule a brief call to walk through scope, timeline, and next steps.</p>
<p>Best regards,<br>Sales Team</p>
HTML;

        return [
            'html_body' => $html,
            'text_body' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
