<?php

namespace App\Http\Controllers;

use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Services\VoiceGateway;
use App\Http\Requests\StartLeadVoiceCallRequest;
use App\Models\Lead;
use App\Services\LeadWorkforcePanelService;
use Illuminate\Http\RedirectResponse;

class LeadVoiceCallController extends Controller
{
    public function __construct(
        private VoiceGateway $voiceGateway,
        private LeadWorkforcePanelService $workforcePanel,
    ) {
        $this->middleware('permission:voice.calls.create')->only('store');
    }

    public function store(StartLeadVoiceCallRequest $request, Lead $lead): RedirectResponse
    {
        $employee = $this->workforcePanel->resolveEmployee(
            $request->validated('ai_employee_id'),
        );

        $call = $this->voiceGateway->initiateOutbound(
            $employee,
            $lead,
            $request->user(),
        );

        if ($call->status === VoiceCallStatus::Failed) {
            return redirect()
                ->route('leads.show', $lead)
                ->withErrors(['voice_call' => $call->error_message ?? 'Voice call could not be started.']);
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('voice_call_started', $call->uuid);
    }
}
