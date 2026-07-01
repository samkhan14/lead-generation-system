<?php

namespace App\Support;

use BackedEnum;

class AiAdminOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string}>
     */
    public static function enumOptions(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->map(function (BackedEnum $case) {
                $label = method_exists($case, 'label')
                    ? $case->label()
                    : ucwords(str_replace('_', ' ', $case->value));

                return [
                    'value' => $case->value,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function providerSlugs(): array
    {
        return collect(array_keys(config('ai_platform.provider_drivers', [])))
            ->map(fn (string $slug) => [
                'value' => $slug,
                'label' => strtoupper($slug === 'xai' ? 'Grok (xAI)' : $slug),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function knowledgeSources(): array
    {
        return collect(config('ai_platform.knowledge_sources', []))
            ->map(fn (string $source) => [
                'value' => $source,
                'label' => ucwords(str_replace('_', ' ', $source)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, ai_provider_id: int}>
     */
    public static function modelOptions(?int $providerId = null): array
    {
        $query = \App\Domains\AI\Models\AiModel::query()
            ->with('provider:id,name,slug')
            ->orderBy('name');

        if ($providerId) {
            $query->where('ai_provider_id', $providerId);
        }

        return $query->get(['id', 'name', 'slug', 'ai_provider_id'])
            ->map(fn ($model) => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'ai_provider_id' => $model->ai_provider_id,
                'provider_label' => $model->provider?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public static function providerOptions(): array
    {
        return \App\Domains\AI\Models\AiProvider::query()
            ->orderBy('priority')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn ($provider) => [
                'id' => $provider->id,
                'name' => $provider->name,
                'slug' => $provider->slug,
                'status' => $provider->status?->value ?? $provider->status,
            ])
            ->values()
            ->all();
    }
}
