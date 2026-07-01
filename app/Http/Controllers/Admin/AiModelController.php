<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\AiModelStatus;
use App\Domains\AI\Models\AiModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAiModelRequest;
use App\Http\Requests\Admin\UpdateAiModelRequest;
use App\Http\Resources\Admin\AiModelResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.models.view')->only(['index', 'show']);
        $this->middleware('permission:ai.models.create')->only(['store']);
        $this->middleware('permission:ai.models.update')->only(['update']);
        $this->middleware('permission:ai.models.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'ai_provider_id' => $request->query('ai_provider_id'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = AiModel::query()->with('provider:id,name,slug');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['ai_provider_id'])) {
            $query->where('ai_provider_id', $filters['ai_provider_id']);
        }

        $models = $query
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Models/Index', [
            'models' => AiModelResource::collection($models),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(AiModelStatus::class),
            'providerOptions' => AiAdminOptions::providerOptions(),
        ]);
    }

    public function show(AiModel $aiModel): Response
    {
        $aiModel->load('provider:id,name,slug');

        return Inertia::render('Admin/AI/Models/Show', [
            'model' => AiModelResource::make($aiModel)->resolve(),
            'statusOptions' => AiAdminOptions::enumOptions(AiModelStatus::class),
            'providerOptions' => AiAdminOptions::providerOptions(),
        ]);
    }

    public function store(StoreAiModelRequest $request): RedirectResponse
    {
        $model = AiModel::query()->create($request->validated());

        return redirect()
            ->route('admin.ai.models.show', $model)
            ->with('success', 'AI model created successfully.');
    }

    public function update(UpdateAiModelRequest $request, AiModel $aiModel): RedirectResponse
    {
        $aiModel->update($request->validated());

        return redirect()
            ->route('admin.ai.models.show', $aiModel)
            ->with('success', 'AI model updated successfully.');
    }

    public function destroy(AiModel $aiModel): RedirectResponse
    {
        $aiModel->delete();

        return redirect()
            ->route('admin.ai.models.index')
            ->with('success', 'AI model deleted successfully.');
    }
}
