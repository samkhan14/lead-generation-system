<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Models\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAiProviderRequest;
use App\Http\Requests\Admin\UpdateAiProviderRequest;
use App\Http\Resources\Admin\AiProviderResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.providers.view')->only(['index', 'show']);
        $this->middleware('permission:ai.providers.create')->only(['store']);
        $this->middleware('permission:ai.providers.update')->only(['update']);
        $this->middleware('permission:ai.providers.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = AiProvider::query()->withCount('models');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $providers = $query
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Providers/Index', [
            'providers' => AiProviderResource::collection($providers),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(AiProviderStatus::class),
            'slugOptions' => AiAdminOptions::providerSlugs(),
        ]);
    }

    public function show(AiProvider $aiProvider): Response
    {
        $aiProvider->loadCount('models');

        return Inertia::render('Admin/AI/Providers/Show', [
            'provider' => AiProviderResource::make($aiProvider)->resolve(),
            'statusOptions' => AiAdminOptions::enumOptions(AiProviderStatus::class),
            'slugOptions' => AiAdminOptions::providerSlugs(),
        ]);
    }

    public function store(StoreAiProviderRequest $request): RedirectResponse
    {
        $provider = AiProvider::query()->create($request->validated());

        return redirect()
            ->route('admin.ai.providers.show', $provider)
            ->with('success', 'AI provider created successfully.');
    }

    public function update(UpdateAiProviderRequest $request, AiProvider $aiProvider): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $aiProvider->update($data);

        return redirect()
            ->route('admin.ai.providers.show', $aiProvider)
            ->with('success', 'AI provider updated successfully.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        $aiProvider->delete();

        return redirect()
            ->route('admin.ai.providers.index')
            ->with('success', 'AI provider deleted successfully.');
    }
}
