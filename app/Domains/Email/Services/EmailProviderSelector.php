<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailProvider;
use Illuminate\Support\Collection;
use RuntimeException;

class EmailProviderSelector
{
    /**
     * @return Collection<int, EmailProvider>
     */
    public function orderedProviders(?string $preferredSlug = null): Collection
    {
        $providers = EmailProvider::query()->selectable()->get()->filter->isUsable();

        if ($providers->isEmpty()) {
            throw new RuntimeException('No usable email providers are configured.');
        }

        if (filled($preferredSlug)) {
            $preferred = $providers->firstWhere('slug', $preferredSlug);

            if ($preferred !== null) {
                return collect([$preferred])->merge(
                    $providers->reject(fn (EmailProvider $provider) => $provider->is($preferred)),
                )->values();
            }
        }

        return $providers->values();
    }
}
