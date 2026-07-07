<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Email\Enums\EmailProviderStatus;
use App\Domains\Email\Models\EmailProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmailProviderRequest;
use App\Http\Requests\Admin\UpdateEmailProviderRequest;
use App\Http\Resources\Admin\EmailProviderResource;
use App\Support\EmailAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:email.providers.view')->only(['index', 'show']);
        $this->middleware('permission:email.providers.create')->only(['store']);
        $this->middleware('permission:email.providers.update')->only(['update']);
        $this->middleware('permission:email.providers.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = EmailProvider::query()->withCount(['sends', 'campaigns']);

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

        return Inertia::render('Admin/Email/Providers/Index', [
            'providers' => EmailProviderResource::collection($providers),
            'filters' => array_map(fn ($value) => $value === '' ? null : $value, $filters),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailProviderStatus::class),
            'slugOptions' => EmailAdminOptions::providerSlugs(),
        ]);
    }

    public function show(EmailProvider $emailProvider): Response
    {
        $emailProvider->loadCount(['sends', 'campaigns']);

        return Inertia::render('Admin/Email/Providers/Show', [
            'provider' => EmailProviderResource::make($emailProvider)->resolve(),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailProviderStatus::class),
            'slugOptions' => EmailAdminOptions::providerSlugs(),
        ]);
    }

    public function store(StoreEmailProviderRequest $request): RedirectResponse
    {
        $provider = EmailProvider::query()->create($request->validated());

        return redirect()
            ->route('admin.email.providers.show', $provider)
            ->with('success', 'Email provider created successfully.');
    }

    public function update(UpdateEmailProviderRequest $request, EmailProvider $emailProvider): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $emailProvider->update($data);

        return redirect()
            ->route('admin.email.providers.show', $emailProvider)
            ->with('success', 'Email provider updated successfully.');
    }

    public function destroy(EmailProvider $emailProvider): RedirectResponse
    {
        $emailProvider->delete();

        return redirect()
            ->route('admin.email.providers.index')
            ->with('success', 'Email provider deleted successfully.');
    }
}
