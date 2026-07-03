<?php

namespace App\Http\Controllers;

use App\Domains\Voice\Services\VoiceCallDispatcher;
use App\Http\Requests\StartLeadVoiceCallRequest;
use App\Models\Lead;
use App\Services\LeadWorkforcePanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class LeadVoiceCallController extends Controller
{
    public function __construct(
        private VoiceCallDispatcher $voiceCallDispatcher,
        private LeadWorkforcePanelService $workforcePanel,
    ) {
        $this->middleware('permission:voice.calls.create')->only('store');
    }

    public function store(StartLeadVoiceCallRequest $request, Lead $lead): RedirectResponse
    {
        $employee = $this->workforcePanel->resolveEmployee(
            $request->validated('ai_employee_id'),
        );

        try {
            $call = $this->voiceCallDispatcher->queueOutbound(
                $employee,
                $lead,
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('leads.show', $lead)
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('voice_call_started', $call->uuid);
    }
}
