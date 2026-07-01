<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Domains\AI\Models\PromptTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromptTemplateRequest;
use App\Http\Requests\Admin\UpdatePromptTemplateRequest;
use App\Http\Resources\Admin\PromptTemplateResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.prompts.view')->only(['index', 'show']);
        $this->middleware('permission:ai.prompts.create')->only(['store']);
        $this->middleware('permission:ai.prompts.update')->only(['update']);
        $this->middleware('permission:ai.prompts.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = PromptTemplate::query();

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['q'].'%')
                    ->orWhere('content', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $templates = $query
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Prompts/Index', [
            'templates' => PromptTemplateResource::collection($templates),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(PromptTemplateStatus::class),
            'categoryOptions' => AiAdminOptions::enumOptions(PromptTemplateCategory::class),
        ]);
    }

    public function show(PromptTemplate $promptTemplate): Response
    {
        return Inertia::render('Admin/AI/Prompts/Show', [
            'template' => PromptTemplateResource::make($promptTemplate)->resolve(),
            'statusOptions' => AiAdminOptions::enumOptions(PromptTemplateStatus::class),
            'categoryOptions' => AiAdminOptions::enumOptions(PromptTemplateCategory::class),
        ]);
    }

    public function store(StorePromptTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $template = PromptTemplate::query()->create($data);

        return redirect()
            ->route('admin.ai.prompts.show', $template)
            ->with('success', 'Prompt template created successfully.');
    }

    public function update(UpdatePromptTemplateRequest $request, PromptTemplate $promptTemplate): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $promptTemplate->update($data);

        return redirect()
            ->route('admin.ai.prompts.show', $promptTemplate)
            ->with('success', 'Prompt template updated successfully.');
    }

    public function destroy(PromptTemplate $promptTemplate): RedirectResponse
    {
        $promptTemplate->delete();

        return redirect()
            ->route('admin.ai.prompts.index')
            ->with('success', 'Prompt template deleted successfully.');
    }
}
