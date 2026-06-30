<?php

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Enums\PromptTemplateCategory;
use Illuminate\Support\Facades\Schema;

test('ai foundation migrations create expected tables', function () {
    expect(Schema::hasTable('ai_providers'))->toBeTrue()
        ->and(Schema::hasTable('ai_models'))->toBeTrue()
        ->and(Schema::hasTable('prompt_templates'))->toBeTrue()
        ->and(Schema::hasTable('prompt_versions'))->toBeTrue()
        ->and(Schema::hasTable('knowledge_bases'))->toBeTrue()
        ->and(Schema::hasTable('ai_employees'))->toBeTrue()
        ->and(Schema::hasTable('ai_logs'))->toBeTrue();
});

test('ai foundation tables have expected columns', function () {
    expect(Schema::hasColumns('ai_providers', [
        'slug', 'name', 'api_key', 'priority', 'timeout_seconds', 'retry_count', 'status',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_models', [
            'ai_provider_id', 'slug', 'capabilities', 'max_tokens', 'input_price_per_1k', 'status',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_employees', [
            'uuid', 'name', 'role', 'system_prompt', 'behavior_prompt', 'knowledge_sources',
            'ai_provider_id', 'ai_model_id', 'fallback_provider_id', 'fallback_model_id', 'status',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('prompt_templates', [
            'slug', 'category', 'content', 'variables', 'status',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('prompt_versions', [
            'prompt_template_id', 'version', 'content', 'approval_status', 'approved_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('knowledge_bases', [
            'slug', 'category', 'content', 'metadata', 'status',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_logs', [
            'uuid', 'ai_employee_id', 'ai_provider_id', 'lead_id', 'request_type',
            'prompt_tokens', 'completion_tokens', 'cost_usd', 'latency_ms', 'status',
        ]))->toBeTrue();
});

test('ai foundation enums expose expected values', function () {
    expect(AiProviderStatus::values())->toBe(['active', 'disabled', 'degraded'])
        ->and(AiEmployeeRole::VoiceSales->value)->toBe('voice_sales')
        ->and(AiLogStatus::values())->toContain('success', 'error', 'timeout', 'rate_limited')
        ->and(PromptTemplateCategory::System->value)->toBe('system');
});
