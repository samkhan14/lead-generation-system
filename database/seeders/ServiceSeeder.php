<?php

namespace Database\Seeders;

use App\Domains\BusinessKnowledge\Enums\ServiceComplexity;
use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');
        $definitions = $this->loadDefinitions();

        $activeSlugs = collect($definitions)->pluck('slug')->all();

        foreach ($definitions as $definition) {
            $payload = $this->mapDefinitionToPayload($definition, $authorId);

            Service::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                $payload,
            );
        }

        $servicesBySlug = Service::query()->pluck('id', 'slug');

        foreach ($definitions as $definition) {
            Service::query()
                ->where('slug', $definition['slug'])
                ->update([
                    'cross_sell_ids' => $this->resolveSlugIds($definition['cross_sell_slugs'] ?? [], $servicesBySlug),
                    'upsell_ids' => $this->resolveSlugIds($definition['upsell_slugs'] ?? [], $servicesBySlug),
                    'related_service_ids' => $this->resolveSlugIds($definition['related_slugs'] ?? [], $servicesBySlug),
                ]);
        }

        Service::query()
            ->whereNotIn('slug', $activeSlugs)
            ->where('status', '!=', ServiceStatus::Archived)
            ->update(['status' => ServiceStatus::Archived]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadDefinitions(): array
    {
        $paths = glob(__DIR__.'/data/business_services/*.php') ?: [];

        sort($paths);

        $definitions = [];

        foreach ($paths as $path) {
            $definitions = array_merge($definitions, require $path);
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function mapDefinitionToPayload(array $definition, ?int $authorId): array
    {
        $complexity = $definition['complexity_level'] ?? null;

        return [
            'slug' => $definition['slug'],
            'name' => $definition['name'],
            'short_description' => $definition['short_description'] ?? null,
            'description' => $definition['description'] ?? $definition['short_description'] ?? null,
            'detailed_description' => $definition['detailed_description'] ?? null,
            'target_audience' => $definition['target_audience'] ?? [],
            'ideal_customer_profile' => $definition['ideal_customer_profile'] ?? null,
            'problems_solved' => $definition['problems_solved'] ?? [],
            'features' => $definition['features'] ?? [],
            'benefits' => $definition['benefits'] ?? [],
            'deliverables' => $definition['deliverables'] ?? [],
            'typical_timeline' => $definition['typical_timeline'] ?? null,
            'complexity_level' => $complexity instanceof ServiceComplexity
                ? $complexity
                : (filled($complexity) ? ServiceComplexity::from($complexity) : null),
            'pricing_notes' => $definition['pricing_notes'] ?? null,
            'faqs' => $definition['faqs'] ?? [],
            'objections' => $definition['objections'] ?? [],
            'discovery_questions' => $definition['discovery_questions'] ?? [],
            'quotation_requirements' => $definition['quotation_requirements'] ?? [],
            'technologies' => $definition['technologies'] ?? [],
            'tags' => $definition['tags'] ?? [],
            'sort_order' => (int) ($definition['sort_order'] ?? 0),
            'status' => $this->resolveStatus($definition['status'] ?? ServiceStatus::Active),
            'cross_sell_ids' => [],
            'upsell_ids' => [],
            'related_service_ids' => [],
            'created_by' => $authorId,
            'updated_by' => $authorId,
        ];
    }

    private function resolveStatus(mixed $status): ServiceStatus
    {
        if ($status instanceof ServiceStatus) {
            return $status;
        }

        return ServiceStatus::from((string) $status);
    }

    /**
     * @param  list<string>  $slugs
     * @param  \Illuminate\Support\Collection<string, int>  $servicesBySlug
     * @return list<int>
     */
    private function resolveSlugIds(array $slugs, $servicesBySlug): array
    {
        return collect($slugs)
            ->map(fn (string $slug) => $servicesBySlug[$slug] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
