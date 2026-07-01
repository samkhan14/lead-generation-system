<?php

namespace App\Domains\Voice\Services;

use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Support\Collection;
use RuntimeException;

class VoiceProviderSelector
{
    /**
     * @return Collection<int, VoiceProvider>
     */
    public function orderedProviders(?string $preferredSlug = null): Collection
    {
        $providers = VoiceProvider::query()
            ->selectable()
            ->get()
            ->filter(fn (VoiceProvider $provider) => $provider->isUsable())
            ->values();

        if ($providers->isEmpty()) {
            throw new RuntimeException('No usable voice providers are configured.');
        }

        if ($preferredSlug === null) {
            return $providers;
        }

        $preferred = $providers->firstWhere('slug', $preferredSlug);

        if ($preferred === null) {
            return $providers;
        }

        return collect([$preferred])->merge(
            $providers->reject(fn (VoiceProvider $provider) => $provider->id === $preferred->id),
        )->values();
    }
}
