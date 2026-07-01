<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Models\KnowledgeBase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKnowledgeBaseRequest;
use App\Http\Requests\Admin\UpdateKnowledgeBaseRequest;
use App\Http\Resources\Admin\KnowledgeBaseResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.knowledge.view')->only(['index', 'show']);
        $this->middleware('permission:ai.knowledge.create')->only(['store']);
        $this->middleware('permission:ai.knowledge.update')->only(['update']);
        $this->middleware('permission:ai.knowledge.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = KnowledgeBase::query();

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

        $entries = $query
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Knowledge/Index', [
            'entries' => KnowledgeBaseResource::collection($entries),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(KnowledgeBaseStatus::class),
            'categoryOptions' => AiAdminOptions::enumOptions(KnowledgeBaseCategory::class),
        ]);
    }

    public function show(KnowledgeBase $knowledgeBase): Response
    {
        return Inertia::render('Admin/AI/Knowledge/Show', [
            'entry' => KnowledgeBaseResource::make($knowledgeBase)->resolve(),
            'statusOptions' => AiAdminOptions::enumOptions(KnowledgeBaseStatus::class),
            'categoryOptions' => AiAdminOptions::enumOptions(KnowledgeBaseCategory::class),
        ]);
    }

    public function store(StoreKnowledgeBaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $entry = KnowledgeBase::query()->create($data);

        return redirect()
            ->route('admin.ai.knowledge.show', $entry)
            ->with('success', 'Knowledge base entry created successfully.');
    }

    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $knowledgeBase->update($data);

        return redirect()
            ->route('admin.ai.knowledge.show', $knowledgeBase)
            ->with('success', 'Knowledge base entry updated successfully.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $knowledgeBase->delete();

        return redirect()
            ->route('admin.ai.knowledge.index')
            ->with('success', 'Knowledge base entry deleted successfully.');
    }
}
