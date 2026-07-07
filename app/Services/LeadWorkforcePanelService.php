<?php

namespace App\Services;

use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Models\Lead;
use App\Models\User;

class LeadWorkforcePanelService
{
    public function __construct(
        private LeadPitchService $pitchService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forLead(Lead $lead, ?User $viewer = null): array
    {
        $canViewAi = $viewer?->can('ai.logs.view') ?? false;
        $canViewVoice = $viewer?->can('voice.calls.view') ?? false;
        $canStartVoice = $viewer?->can('voice.calls.create') ?? false;

        $voiceEmployees = $this->voiceEmployees();
        $defaultEmployee = $this->resolveDefaultEmployee($voiceEmployees, $lead);
        $usableProviders = VoiceProvider::query()->selectable()->get()->filter->isUsable();

        $aiActivities = $canViewAi ? $this->aiActivities($lead) : [];
        $voiceCalls = $canViewVoice ? $this->voiceCalls($lead) : [];
        $timeline = $canViewAi || $canViewVoice
            ? $this->buildTimeline($aiActivities, $voiceCalls)
            : [];
        $costs = $canViewAi || $canViewVoice
            ? $this->buildCosts($lead, $canViewAi, $canViewVoice)
            : null;

        return [
            'can_view_ai' => $canViewAi,
            'can_view_voice' => $canViewVoice,
            'can_start_voice_call' => $canStartVoice
                && filled($lead->phone)
                && $defaultEmployee !== null
                && $usableProviders->isNotEmpty(),
            'voice_call_blockers' => $this->voiceCallBlockers($lead, $canStartVoice, $defaultEmployee, $usableProviders->isNotEmpty()),
            'voice_employees' => $voiceEmployees,
            'default_employee_id' => $defaultEmployee['id'] ?? null,
            'ai_activities' => $aiActivities,
            'voice_calls' => $voiceCalls,
            'timeline' => $timeline,
            'costs' => $costs,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aiActivities(Lead $lead): array
    {
        return AiLog::query()
            ->where('lead_id', $lead->id)
            ->with(['employee:id,name', 'provider:id,name,slug', 'model:id,name,slug'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (AiLog $log) => [
                'id' => $log->id,
                'uuid' => $log->uuid,
                'request_type' => $log->request_type?->value ?? $log->request_type,
                'status' => $log->status?->value ?? $log->status,
                'prompt_tokens' => $log->prompt_tokens,
                'completion_tokens' => $log->completion_tokens,
                'total_tokens' => $log->total_tokens,
                'cost_usd' => $log->cost_usd,
                'latency_ms' => $log->latency_ms,
                'error_message' => $log->error_message,
                'employee' => $log->employee ? ['id' => $log->employee->id, 'name' => $log->employee->name] : null,
                'provider' => $log->provider ? ['id' => $log->provider->id, 'name' => $log->provider->name] : null,
                'model' => $log->model ? ['id' => $log->model->id, 'name' => $log->model->name] : null,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function voiceCalls(Lead $lead): array
    {
        return VoiceCall::query()
            ->where('lead_id', $lead->id)
            ->with(['employee:id,name', 'provider:id,name,slug'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (VoiceCall $call) => [
                'id' => $call->id,
                'uuid' => $call->uuid,
                'external_call_id' => $call->external_call_id,
                'direction' => $call->direction?->value ?? $call->direction,
                'status' => $call->status?->value ?? $call->status,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'duration_seconds' => $call->duration_seconds,
                'cost_usd' => $call->cost_usd,
                'summary' => $call->summary,
                'error_message' => $call->error_message,
                'employee' => $call->employee ? ['id' => $call->employee->id, 'name' => $call->employee->name] : null,
                'provider' => $call->provider ? ['id' => $call->provider->id, 'name' => $call->provider->name] : null,
                'created_at' => $call->created_at?->toIso8601String(),
                'started_at' => $call->started_at?->toIso8601String(),
                'ended_at' => $call->ended_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $aiActivities
     * @param  array<int, array<string, mixed>>  $voiceCalls
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(array $aiActivities, array $voiceCalls): array
    {
        $events = collect($aiActivities)->map(fn (array $activity) => [
            'id' => 'ai_'.$activity['id'],
            'type' => 'ai_activity',
            'occurred_at' => $activity['created_at'],
            'title' => sprintf(
                'AI %s — %s',
                str_replace('_', ' ', (string) $activity['request_type']),
                $activity['employee']['name'] ?? 'Unknown agent',
            ),
            'status' => $activity['status'],
            'cost_usd' => $activity['cost_usd'],
            'detail' => $activity['error_message'],
        ]);

        $events = $events->merge(collect($voiceCalls)->map(fn (array $call) => [
            'id' => 'voice_'.$call['id'],
            'type' => 'voice_call',
            'occurred_at' => $call['created_at'],
            'title' => sprintf(
                'Voice call — %s',
                $call['employee']['name'] ?? 'Unknown agent',
            ),
            'status' => $call['status'],
            'cost_usd' => $call['cost_usd'],
            'detail' => $call['summary'] ?? $call['error_message'],
        ]));

        return $events
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCosts(Lead $lead, bool $includeAi, bool $includeVoice): array
    {
        $aiTotal = $includeAi
            ? (float) AiLog::query()->where('lead_id', $lead->id)->sum('cost_usd')
            : 0.0;

        $voiceTotal = $includeVoice
            ? (float) VoiceCall::query()->where('lead_id', $lead->id)->sum('cost_usd')
            : 0.0;

        return [
            'ai_total_usd' => round($aiTotal, 6),
            'voice_total_usd' => round($voiceTotal, 6),
            'combined_total_usd' => round($aiTotal + $voiceTotal, 6),
            'ai_by_employee' => $includeAi ? $this->aiCostsByEmployee($lead) : [],
            'voice_by_provider' => $includeVoice ? $this->voiceCostsByProvider($lead) : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aiCostsByEmployee(Lead $lead): array
    {
        return AiLog::query()
            ->where('lead_id', $lead->id)
            ->with('employee:id,name')
            ->get()
            ->groupBy('ai_employee_id')
            ->map(fn ($logs, $employeeId) => [
                'employee_id' => (int) $employeeId,
                'employee_name' => $logs->first()?->employee?->name ?? 'Unknown',
                'total_cost_usd' => round((float) $logs->sum('cost_usd'), 6),
                'interactions' => $logs->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function voiceCostsByProvider(Lead $lead): array
    {
        return VoiceCall::query()
            ->where('lead_id', $lead->id)
            ->with('provider:id,name')
            ->get()
            ->groupBy('voice_provider_id')
            ->map(fn ($calls, $providerId) => [
                'provider_id' => (int) $providerId,
                'provider_name' => $calls->first()?->provider?->name ?? 'Unknown',
                'total_cost_usd' => round((float) $calls->sum(fn ($call) => (float) ($call->cost_usd ?? 0)), 6),
                'calls' => $calls->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, role: string}>
     */
    private function voiceEmployees(): array
    {
        $eligibleRoles = config('voice_platform.eligible_employee_roles', []);

        return AiEmployee::query()
            ->whereIn('role', $eligibleRoles)
            ->where('status', '!=', AiEmployeeStatus::Disabled)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'status'])
            ->map(fn (AiEmployee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role?->value ?? $employee->role,
                'role_label' => $employee->role?->label(),
                'status' => $employee->status?->value ?? $employee->status,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id: int, name: string}>  $employees
     * @return array{id: int, name: string}|null
     */
    private function resolveDefaultEmployee(array $employees, ?Lead $lead = null): ?array
    {
        if ($employees === []) {
            return null;
        }

        if ($lead !== null) {
            $pitchPreferred = $this->resolvePitchPreferredEmployeeName($lead);

            if ($pitchPreferred !== null) {
                $match = collect($employees)->firstWhere('name', $pitchPreferred);

                if ($match !== null) {
                    return ['id' => $match['id'], 'name' => $match['name']];
                }
            }
        }

        $preferredName = config('voice_platform.default_voice_employee_name');

        if (is_string($preferredName) && $preferredName !== '') {
            $match = collect($employees)->firstWhere('name', $preferredName);

            if ($match !== null) {
                return ['id' => $match['id'], 'name' => $match['name']];
            }
        }

        $first = $employees[0];

        return ['id' => $first['id'], 'name' => $first['name']];
    }

    private function resolvePitchPreferredEmployeeName(Lead $lead): ?string
    {
        $primary = $this->pitchService->primaryRecommendation($lead);

        if ($primary === null) {
            return null;
        }

        $marketingTypes = (array) config('lead_pitches.marketing_pitch_types', []);

        if (in_array($primary['type'], $marketingTypes, true)) {
            return config('lead_pitches.preferred_employee.marketing');
        }

        return config('lead_pitches.preferred_employee.default');
    }

    /**
     * @return array<int, string>
     */
    private function voiceCallBlockers(
        Lead $lead,
        bool $canStartVoice,
        ?array $defaultEmployee,
        bool $hasUsableProvider,
    ): array {
        $blockers = [];

        if (! $canStartVoice) {
            $blockers[] = 'You do not have permission to start voice calls.';
        }

        if (! filled($lead->phone)) {
            $blockers[] = 'Lead has no phone number.';
        }

        if ($defaultEmployee === null) {
            $blockers[] = 'No voice-capable AI employee is configured.';
        }

        if (! $hasUsableProvider) {
            $blockers[] = 'No active voice provider with API credentials.';
        }

        return $blockers;
    }

    /**
     * Shared voice-call options for lead list bulk actions and detail panel.
     *
     * @return array<string, mixed>
     */
    public function voiceCallOptions(?User $viewer = null): array
    {
        $canStartVoice = $viewer?->can('voice.calls.create') ?? false;
        $voiceEmployees = $this->voiceEmployees();
        $defaultEmployee = $this->resolveDefaultEmployee($voiceEmployees);
        $usableProviders = VoiceProvider::query()->selectable()->get()->filter->isUsable();

        return [
            'can_start_voice_call' => $canStartVoice
                && $defaultEmployee !== null
                && $usableProviders->isNotEmpty(),
            'voice_call_blockers' => $this->bulkVoiceCallBlockers(
                $viewer,
                $canStartVoice,
                $defaultEmployee,
                $usableProviders->isNotEmpty(),
            ),
            'voice_employees' => $voiceEmployees,
            'default_employee_id' => $defaultEmployee['id'] ?? null,
            'max_bulk_leads' => (int) config('voice_platform.bulk.max_leads_per_request', 50),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function bulkVoiceCallBlockers(
        ?User $viewer,
        ?bool $canStartVoice = null,
        ?array $defaultEmployee = null,
        ?bool $hasUsableProvider = null,
    ): array {
        $canStartVoice ??= $viewer?->can('voice.calls.create') ?? false;
        $voiceEmployees = $this->voiceEmployees();
        $defaultEmployee ??= $this->resolveDefaultEmployee($voiceEmployees);
        $hasUsableProvider ??= VoiceProvider::query()->selectable()->get()->filter->isUsable()->isNotEmpty();

        $blockers = [];

        if (! $canStartVoice) {
            $blockers[] = 'You do not have permission to start voice calls.';
        }

        if ($defaultEmployee === null) {
            $blockers[] = 'No voice-capable AI employee is configured.';
        }

        if (! $hasUsableProvider) {
            $blockers[] = 'No active voice provider with API credentials.';
        }

        return $blockers;
    }

    public function resolveEmployee(?int $employeeId): AiEmployee
    {
        $eligibleRoles = config('voice_platform.eligible_employee_roles', []);

        if ($employeeId !== null) {
            $employee = AiEmployee::query()
                ->whereKey($employeeId)
                ->whereIn('role', $eligibleRoles)
                ->first();

            if ($employee === null) {
                throw new \RuntimeException('Selected AI employee is not eligible for voice calls.');
            }

            return $employee;
        }

        $preferredName = config('voice_platform.default_voice_employee_name');
        $query = AiEmployee::query()->whereIn('role', $eligibleRoles);

        if (is_string($preferredName) && $preferredName !== '') {
            $employee = (clone $query)->where('name', $preferredName)->first();

            if ($employee !== null) {
                return $employee;
            }
        }

        $employee = $query->orderBy('name')->first();

        if ($employee === null) {
            throw new \RuntimeException('No voice-capable AI employee is configured.');
        }

        return $employee;
    }
}
