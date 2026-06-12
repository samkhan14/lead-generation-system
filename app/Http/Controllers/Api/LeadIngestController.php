<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestLeadRequest;
use App\Services\LeadIngestionService;
use Illuminate\Http\JsonResponse;

class LeadIngestController extends Controller
{
    public function __construct(
        private LeadIngestionService $ingestionService,
    ) {}

    public function store(IngestLeadRequest $request): JsonResponse
    {
        $result = $this->ingestionService->ingest($request->validated());

        $statusCode = match ($result->status) {
            'created' => 201,
            'duplicate' => 409,
            default => 200,
        };

        return response()->json($result->toArray(), $statusCode);
    }
}
