<?php

namespace App\Console\Commands;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Domains\Voice\Models\VoiceProvider;
use App\Domains\Voice\Services\VoiceContextBuilder;
use App\Domains\Voice\Services\VoiceGateway;
use App\Models\Lead;
use Illuminate\Console\Command;

class VoiceSmokeTestCommand extends Command
{
    protected $signature = 'voice:smoke-test
                            {lead? : Lead ID to validate against}
                            {--employee= : AI employee ID (defaults to configured voice employee)}
                            {--call : Place a real outbound call when configuration passes}';

    protected $description = 'Validate voice platform configuration for live Retell smoke testing';

    public function handle(
        VoiceGateway $voiceGateway,
        VoiceContextBuilder $contextBuilder,
    ): int {
        $this->components->info('Voice platform smoke test');
        $passed = 0;
        $failed = 0;

        $providers = VoiceProvider::query()->get()->filter(fn (VoiceProvider $p) => $p->isUsable());

        if ($providers->isEmpty()) {
            $this->components->error('No usable voice providers (need active status + api_key + base URL).');
            $failed++;
        } else {
            $this->components->twoColumnDetail('Voice providers', $providers->pluck('slug')->implode(', '));
            $passed++;
        }

        $employee = $this->resolveEmployee();

        if ($employee === null) {
            $this->components->error('No eligible AI employee found for voice calls.');
            $failed++;
        } else {
            $this->components->twoColumnDetail('AI employee', $employee->name.' (voice_id: '.($employee->voice_id ?: 'missing').')');
            $employee->voice_id ? $passed++ : $failed++;
        }

        $activeServices = Service::query()->active()->count();
        $this->components->twoColumnDetail('Active services', (string) $activeServices);
        $activeServices > 0 ? $passed++ : $failed++;

        $lead = $this->resolveLead();

        if ($lead === null && $this->argument('lead')) {
            $this->components->error('Lead not found.');
            $failed++;

            return self::FAILURE;
        }

        if ($lead !== null) {
            try {
                $phone = $voiceGateway->resolvePhoneForLead($lead);
                $this->components->twoColumnDetail('Lead phone (E.164)', $phone);
                $passed++;
            } catch (\Throwable $exception) {
                $this->components->error('Lead phone: '.$exception->getMessage());
                $failed++;
            }

            if ($employee !== null) {
                $variables = $contextBuilder->dynamicVariables($employee, $lead);
                $this->components->twoColumnDetail('Dynamic variables', (string) count($variables));
                foreach (['services_summary', 'discovery_questions', 'objection_responses'] as $key) {
                    if (filled($variables[$key] ?? null)) {
                        $this->line("  · {$key}: ".str($variables[$key])->limit(80));
                    }
                }
                count($variables) > 0 ? $passed++ : $failed++;
            }
        } else {
            $this->components->warn('No lead provided — skipping phone and dynamic variable checks.');
        }

        $this->newLine();
        $this->components->twoColumnDetail('Checks passed', (string) $passed);
        $this->components->twoColumnDetail('Checks failed', (string) $failed);

        if ($failed > 0) {
            $this->components->warn('Fix failed checks before live calling.');

            if (! $this->option('call')) {
                return self::FAILURE;
            }
        }

        if ($this->option('call')) {
            if ($employee === null || $lead === null) {
                $this->components->error('--call requires a valid lead and AI employee.');

                return self::FAILURE;
            }

            $this->components->info('Initiating live outbound call (sync)...');
            $call = $voiceGateway->initiateOutbound($employee, $lead);
            $this->components->twoColumnDetail('Call UUID', $call->uuid);
            $this->components->twoColumnDetail('Status', $call->status->value);
            $this->components->twoColumnDetail('External ID', $call->external_call_id ?? '—');

            if ($call->isTerminal() && $call->status->value === 'failed') {
                $this->components->error($call->error_message ?? 'Call failed.');

                return self::FAILURE;
            }
        }

        $this->components->success('Smoke test complete.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolveEmployee(): ?AiEmployee
    {
        if ($this->option('employee')) {
            return AiEmployee::query()->find($this->option('employee'));
        }

        $name = config('voice_platform.default_voice_employee_name');

        return AiEmployee::query()->where('name', $name)->first()
            ?? AiEmployee::query()->whereNotNull('voice_id')->first();
    }

    private function resolveLead(): ?Lead
    {
        $leadId = $this->argument('lead');

        return $leadId ? Lead::query()->find($leadId) : null;
    }
}
