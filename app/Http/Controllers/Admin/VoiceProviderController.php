<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Voice\Enums\VoiceProviderStatus;
use App\Domains\Voice\Models\VoiceProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoiceProviderRequest;
use App\Http\Requests\Admin\UpdateVoiceProviderRequest;
use App\Http\Resources\Admin\VoiceProviderResource;
use App\Support\VoiceAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoiceProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:voice.providers.view')->only(['index', 'show']);
        $this->middleware('permission:voice.providers.create')->only(['store']);
        $this->middleware('permission:voice.providers.update')->only(['update']);
        $this->middleware('permission:voice.providers.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = VoiceProvider::query()->withCount('calls');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $providers = $query->orderBy('priority')->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/Voice/Providers/Index', [
            'providers' => VoiceProviderResource::collection($providers),
            'filters' => array_map(fn ($value) => $value === '' ? null : $value, $filters),
            'statusOptions' => VoiceAdminOptions::enumOptions(VoiceProviderStatus::class),
            'slugOptions' => VoiceAdminOptions::providerSlugs(),
        ]);
    }

    public function show(VoiceProvider $voiceProvider): Response
    {
        $voiceProvider->loadCount('calls');

        return Inertia::render('Admin/Voice/Providers/Show', [
            'provider' => VoiceProviderResource::make($voiceProvider)->resolve(),
            'statusOptions' => VoiceAdminOptions::enumOptions(VoiceProviderStatus::class),
            'slugOptions' => VoiceAdminOptions::providerSlugs(),
        ]);
    }

    public function store(StoreVoiceProviderRequest $request): RedirectResponse
    {
        $provider = VoiceProvider::query()->create($request->validated());

        return redirect()
            ->route('admin.voice.providers.show', $provider)
            ->with('success', 'Voice provider created successfully.');
    }

    public function update(UpdateVoiceProviderRequest $request, VoiceProvider $voiceProvider): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        if (blank($data['webhook_secret'] ?? null)) {
            unset($data['webhook_secret']);
        }

        $voiceProvider->update($data);

        return redirect()
            ->route('admin.voice.providers.show', $voiceProvider)
            ->with('success', 'Voice provider updated successfully.');
    }

    public function destroy(VoiceProvider $voiceProvider): RedirectResponse
    {
        $voiceProvider->delete();

        return redirect()
            ->route('admin.voice.providers.index')
            ->with('success', 'Voice provider deleted successfully.');
    }
}
