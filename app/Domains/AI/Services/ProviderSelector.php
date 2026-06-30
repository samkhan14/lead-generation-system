<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\DataTransferObjects\ProviderSelection;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use RuntimeException;

class ProviderSelector
{
    public function primary(AiEmployee $employee): ProviderSelection
    {
        $employee->loadMissing(['provider', 'model']);

        return $this->resolveSelection(
            $employee->provider,
            $employee->model,
            'primary',
        );
    }

    public function fallback(AiEmployee $employee): ?ProviderSelection
    {
        $employee->loadMissing(['fallbackProvider', 'fallbackModel']);

        if ($employee->fallback_provider_id === null || $employee->fallback_model_id === null) {
            return null;
        }

        return $this->resolveSelection(
            $employee->fallbackProvider,
            $employee->fallbackModel,
            'fallback',
            isFallback: true,
        );
    }

    /**
     * @return array<int, ProviderSelection>
     */
    public function orderedSelections(AiEmployee $employee): array
    {
        $selections = [$this->primary($employee)];

        if ($fallback = $this->fallback($employee)) {
            $selections[] = $fallback;
        }

        return $selections;
    }

    private function resolveSelection(
        ?AiProvider $provider,
        ?AiModel $model,
        string $label,
        bool $isFallback = false,
    ): ProviderSelection {
        if ($provider === null || $model === null) {
            throw new RuntimeException("AI employee is missing {$label} provider or model configuration.");
        }

        if (! $provider->isUsable()) {
            throw new RuntimeException("AI {$label} provider [{$provider->slug}] is not usable.");
        }

        if ($model->ai_provider_id !== $provider->id) {
            throw new RuntimeException("AI {$label} model [{$model->slug}] does not belong to provider [{$provider->slug}].");
        }

        return new ProviderSelection($provider, $model, $isFallback);
    }
}
