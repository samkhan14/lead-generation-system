<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Models\Quote;
use App\Domains\Crm\Services\QuoteGeneratorService;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadQuoteController extends Controller
{
    public function __construct(private QuoteGeneratorService $quotes)
    {
        $this->middleware('permission:crm.quotes.manage');
    }

    public function store(StoreQuoteRequest $request, Lead $lead): RedirectResponse
    {
        $quote = $this->quotes->generate(
            lead: $lead,
            serviceIds: $request->validated('service_ids'),
            user: $request->user(),
            dealId: $request->validated('deal_id'),
            notes: $request->validated('notes'),
        );

        return redirect()->route('leads.quotes.show', [$lead, $quote])
            ->with('success', 'Quote generated.');
    }

    public function show(Lead $lead, Quote $quote): Response
    {
        abort_unless($quote->lead_id === $lead->id, 404);

        return Inertia::render('Leads/Quotes/Show', [
            'lead' => [
                'id' => $lead->id,
                'full_name' => $lead->company ?: $lead->full_name,
            ],
            'quote' => [
                'id' => $quote->id,
                'uuid' => $quote->uuid,
                'title' => $quote->title,
                'subject' => $quote->subject,
                'html_body' => $quote->html_body,
                'text_body' => $quote->text_body,
                'status' => $quote->status->value,
                'status_label' => $quote->status->label(),
                'sent_at' => $quote->sent_at?->toIso8601String(),
                'created_at' => $quote->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function markSent(Lead $lead, Quote $quote): RedirectResponse
    {
        abort_unless($quote->lead_id === $lead->id, 404);

        $this->quotes->markSent($quote, request()->user());

        return redirect()->back()->with('success', 'Quote marked as sent.');
    }
}
