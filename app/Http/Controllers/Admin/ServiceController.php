<?php

namespace App\Http\Controllers\Admin;

use App\Domains\BusinessKnowledge\Enums\ServiceComplexity;
use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Http\Resources\Admin\ServiceResource;
use App\Services\ServiceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        private ServiceCatalogService $catalog,
    ) {
        $this->middleware('permission:services.view')->only(['index', 'show']);
        $this->middleware('permission:services.create')->only(['store']);
        $this->middleware('permission:services.update')->only(['update']);
        $this->middleware('permission:services.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'tag' => trim((string) $request->query('tag', '')),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $services = $this->catalog->paginate($filters);

        return Inertia::render('Admin/Services/Index', [
            'services' => ServiceResource::collection($services),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => collect(ServiceStatus::cases())
                ->map(fn (ServiceStatus $status) => [
                    'value' => $status->value,
                    'label' => ucfirst($status->value),
                ])
                ->values()
                ->all(),
            'serviceOptions' => Service::query()
                ->ordered()
                ->get(['id', 'name', 'slug', 'status'])
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'status' => $service->status?->value ?? $service->status,
                ])
                ->values()
                ->all(),
            'complexityOptions' => $this->complexityOptions(),
        ]);
    }

    public function show(Service $service): Response
    {
        $service->load(['creator:id,name', 'updater:id,name']);

        return Inertia::render('Admin/Services/Show', [
            'service' => ServiceResource::make($service)->resolve(),
            'serviceOptions' => Service::query()
                ->whereKeyNot($service->id)
                ->ordered()
                ->get(['id', 'name', 'slug', 'status'])
                ->map(fn (Service $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'status' => $item->status?->value ?? $item->status,
                ])
                ->values()
                ->all(),
            'statusOptions' => collect(ServiceStatus::cases())
                ->map(fn (ServiceStatus $status) => [
                    'value' => $status->value,
                    'label' => ucfirst($status->value),
                ])
                ->values()
                ->all(),
            'complexityOptions' => $this->complexityOptions(),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function complexityOptions(): array
    {
        return collect(ServiceComplexity::cases())
            ->map(fn (ServiceComplexity $level) => [
                'value' => $level->value,
                'label' => $level->label(),
            ])
            ->values()
            ->all();
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = $this->catalog->create(
            $request->validated(),
            $request->user()->id,
        );

        return redirect()
            ->route('admin.services.show', $service)
            ->with('success', 'Service created successfully.');
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->catalog->update(
            $service,
            $request->validated(),
            $request->user()->id,
        );

        return redirect()
            ->route('admin.services.show', $service)
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->catalog->delete($service);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
