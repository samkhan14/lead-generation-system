<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Models\Deal;
use App\Domains\Crm\Services\DealService;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadDealController extends Controller
{
    public function __construct(private DealService $deals)
    {
        $this->middleware('permission:crm.deals.manage');
    }

    public function store(StoreDealRequest $request, Lead $lead): RedirectResponse
    {
        $this->deals->create($lead, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Deal created.');
    }

    public function update(UpdateDealRequest $request, Lead $lead, Deal $deal): RedirectResponse
    {
        abort_unless($deal->lead_id === $lead->id, 404);

        $this->deals->update($deal, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Deal updated.');
    }

    public function destroy(Lead $lead, Deal $deal): RedirectResponse
    {
        abort_unless($deal->lead_id === $lead->id, 404);

        $deal->delete();

        return redirect()->back()->with('success', 'Deal removed.');
    }
}
