<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Enums\LeadActivityType;
use App\Domains\Crm\Services\LeadActivityService;
use App\Http\Requests\StoreLeadActivityRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadActivityController extends Controller
{
    public function __construct(private LeadActivityService $activities)
    {
        $this->middleware('permission:leads.update');
    }

    public function store(StoreLeadActivityRequest $request, Lead $lead): RedirectResponse
    {
        $this->activities->logManual(
            lead: $lead,
            type: LeadActivityType::from($request->validated('type')),
            subject: $request->validated('subject'),
            body: $request->validated('body'),
            user: $request->user(),
        );

        return redirect()->back()->with('success', 'Activity logged.');
    }
}
