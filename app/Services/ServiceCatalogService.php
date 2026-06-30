<?php

namespace App\Services;

use App\Domains\BusinessKnowledge\DataTransferObjects\ServiceKnowledgeItem;
use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ServiceCatalogService
{
    /**
     * @param  array{
     *     q?: string|null,
     *     status?: string|null,
     *     tag?: string|null,
     *     per_page?: int
     * }  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = in_array($filters['per_page'] ?? 15, [10, 15, 25, 50], true)
            ? (int) ($filters['per_page'] ?? 15)
            : 15;

        $query = Service::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->latest('updated_at');

        if (filled($filters['q'] ?? null)) {
            $query->search($filters['q']);
        }

        if (filled($filters['status'] ?? null) && in_array($filters['status'], ServiceStatus::values(), true)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['tag'] ?? null)) {
            $tag = trim((string) $filters['tag']);
            $query->whereJsonContains('tags', $tag);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, Service>
     */
    public function activeServices(): Collection
    {
        return Service::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, ServiceKnowledgeItem>
     */
    public function activeKnowledgeForAi(): array
    {
        $services = $this->activeServices();
        $lookup = $services->keyBy('id');

        return $services
            ->map(fn (Service $service) => $this->toKnowledgeItem($service, $lookup))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): Service
    {
        $payload = $this->normalizePayload($data);
        $payload['created_by'] = $userId;
        $payload['updated_by'] = $userId;
        $payload['version'] = 1;

        if (blank($payload['slug'] ?? null) && filled($payload['name'] ?? null)) {
            $payload['slug'] = Service::uniqueSlug(Str::slug($payload['name']));
        }

        return Service::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Service $service, array $data, ?int $userId = null): Service
    {
        $payload = $this->normalizePayload($data);
        $payload['updated_by'] = $userId;

        if ($this->shouldBumpVersion($service, $payload)) {
            $payload['version'] = $service->version + 1;
        }

        if (blank($payload['slug'] ?? null)) {
            unset($payload['slug']);
        } elseif ($payload['slug'] !== $service->slug) {
            $payload['slug'] = Service::uniqueSlug($payload['slug']);
        }

        $service->update($payload);

        return $service->fresh(['creator', 'updater']);
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizePayload(array $data): array
    {
        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'slug' => filled($data['slug'] ?? null) ? Str::slug((string) $data['slug']) : null,
            'description' => $this->nullableString($data['description'] ?? null),
            'features' => $this->normalizeStringList($data['features'] ?? []),
            'benefits' => $this->normalizeStringList($data['benefits'] ?? []),
            'deliverables' => $this->normalizeStringList($data['deliverables'] ?? []),
            'pricing_notes' => $this->nullableString($data['pricing_notes'] ?? null),
            'faqs' => $this->normalizeFaqs($data['faqs'] ?? []),
            'objections' => $this->normalizeObjections($data['objections'] ?? []),
            'cross_sell_ids' => $this->normalizeIdList($data['cross_sell_ids'] ?? []),
            'upsell_ids' => $this->normalizeIdList($data['upsell_ids'] ?? []),
            'tags' => $this->normalizeStringList($data['tags'] ?? []),
            'status' => $data['status'] ?? ServiceStatus::Draft->value,
        ];
    }

    /**
     * @param  Collection<int, Service>|null  $lookup
     */
    public function toKnowledgeItem(Service $service, ?Collection $lookup = null): ServiceKnowledgeItem
    {
        $lookup ??= Service::query()->whereIn('id', array_merge(
            $service->cross_sell_ids ?? [],
            $service->upsell_ids ?? [],
        ))->get()->keyBy('id');

        return new ServiceKnowledgeItem(
            id: $service->id,
            uuid: $service->uuid,
            name: $service->name,
            slug: $service->slug,
            description: $service->description,
            features: $service->features ?? [],
            benefits: $service->benefits ?? [],
            deliverables: $service->deliverables ?? [],
            pricingNotes: $service->pricing_notes,
            faqs: $this->normalizeFaqs($service->faqs ?? []),
            objections: $this->normalizeObjections($service->objections ?? []),
            tags: $service->tags ?? [],
            crossSells: $this->relatedServiceSummaries($service->cross_sell_ids ?? [], $lookup),
            upsells: $this->relatedServiceSummaries($service->upsell_ids ?? [], $lookup),
            version: $service->version,
        );
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private function normalizeStringList(array $items): array
    {
        return collect($items)
            ->flatMap(function ($item) {
                if (is_string($item) && str_contains($item, "\n")) {
                    return preg_split('/\r\n|\r|\n/', $item) ?: [];
                }

                return [$item];
            })
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{question: string, answer: string}>
     */
    private function normalizeFaqs(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $question = trim((string) ($item['question'] ?? $item['q'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? $item['a'] ?? ''));

                if ($question === '' || $answer === '') {
                    return null;
                }

                return ['question' => $question, 'answer' => $answer];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{objection: string, response: string}>
     */
    private function normalizeObjections(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $objection = trim((string) ($item['objection'] ?? ''));
                $response = trim((string) ($item['response'] ?? ''));

                if ($objection === '' || $response === '') {
                    return null;
                }

                return ['objection' => $objection, 'response' => $response];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, int>
     */
    private function normalizeIdList(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldBumpVersion(Service $service, array $payload): bool
    {
        $tracked = [
            'name', 'description', 'features', 'benefits', 'deliverables',
            'pricing_notes', 'faqs', 'objections', 'cross_sell_ids', 'upsell_ids', 'tags',
        ];

        foreach ($tracked as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            if ($this->valuesDiffer($service->{$field}, $payload[$field])) {
                return true;
            }
        }

        return false;
    }

    private function valuesDiffer(mixed $current, mixed $incoming): bool
    {
        if (is_array($current) || is_array($incoming)) {
            return $this->normalizeComparable($current) !== $this->normalizeComparable($incoming);
        }

        return $current !== $incoming;
    }

    private function normalizeComparable(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = Arr::sortRecursive($value);

        return json_encode($normalized);
    }

    /**
     * @param  array<int, int>  $ids
     * @param  Collection<int, Service>  $lookup
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function relatedServiceSummaries(array $ids, Collection $lookup): array
    {
        return collect($ids)
            ->map(fn (int $id) => $lookup->get($id))
            ->filter()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
            ])
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
