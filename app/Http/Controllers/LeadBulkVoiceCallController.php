<?php

namespace App\Http\Controllers;

use App\Domains\Voice\Services\VoiceCallDispatcher;
use App\Http\Requests\StartBulkLeadVoiceCallRequest;
use App\Models\Lead;
use App\Services\LeadWorkforcePanelService;
use Illuminate\Http\RedirectResponse;

class LeadBulkVoiceCallController extends Controller
{
    public function __construct(
        private VoiceCallDispatcher $voiceCallDispatcher,
        private LeadWorkforcePanelService $workforcePanel,
    ) {
        $this->middleware('permission:voice.calls.create');
    }

    public function store(StartBulkLeadVoiceCallRequest $request): RedirectResponse
    {
        $employee = $this->workforcePanel->resolveEmployee(
            $request->validated('ai_employee_id'),
        );

        $blockers = $this->workforcePanel->bulkVoiceCallBlockers($request->user());

        if ($blockers !== []) {
            return redirect()
                ->back()
                ->withErrors(['bulk_voice_call' => implode(' ', $blockers)]);
        }

        $leadIds = $request->validated('lead_ids');
        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        $result = $this->voiceCallDispatcher->queueBulkOutbound(
            $employee,
            $leads,
            $request->user(),
        );

        return redirect()
            ->back()
            ->with('bulk_voice_call_result', $result->toArray());
    }
}
