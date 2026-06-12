<?php

namespace App\Http\Controllers\Api;

use App\Enums\ScrapeJobStatus;
use App\Http\Controllers\Controller;
use App\Models\ScrapeJob;
use App\Models\ScrapeRunLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScrapeCallbackController extends Controller
{
    public function start(ScrapeJob $scrapeJob): JsonResponse
    {
        if ($scrapeJob->status !== ScrapeJobStatus::Pending) {
            return response()->json(['error' => 'Job already started'], 409);
        }

        $scrapeJob->update([
            'status' => ScrapeJobStatus::Running,
            'started_at' => now(),
        ]);

        Log::info('ScrapeJob started', ['uuid' => $scrapeJob->uuid]);

        return response()->json(['ok' => true]);
    }

    public function log(Request $request, ScrapeJob $scrapeJob): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'in:info,success,warning,error'],
            'message' => ['required', 'string', 'max:500'],
            'context' => ['nullable', 'array'],
        ]);

        ScrapeRunLog::query()->create([
            'scrape_job_id' => $scrapeJob->id,
            'level' => $validated['level'],
            'message' => $validated['message'],
            'context' => $validated['context'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, ScrapeJob $scrapeJob): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:completed,failed'],
            'scraper_used' => ['nullable', 'string'],
            'total_found' => ['nullable', 'integer', 'min:0'],
            'created_count' => ['nullable', 'integer', 'min:0'],
            'duplicate_count' => ['nullable', 'integer', 'min:0'],
            'failed_count' => ['nullable', 'integer', 'min:0'],
            'error_message' => ['nullable', 'string'],
        ]);

        $scrapeJob->update([
            'status' => $validated['status'] === 'completed'
                ? ScrapeJobStatus::Completed
                : ScrapeJobStatus::Failed,
            'completed_at' => now(),
            'scraper_used' => $validated['scraper_used'] ?? null,
            'total_found' => $validated['total_found'] ?? $scrapeJob->total_found,
            'created_count' => $validated['created_count'] ?? $scrapeJob->created_count,
            'duplicate_count' => $validated['duplicate_count'] ?? $scrapeJob->duplicate_count,
            'failed_count' => $validated['failed_count'] ?? $scrapeJob->failed_count,
            'error_message' => $validated['error_message'] ?? null,
        ]);

        Log::info('ScrapeJob completed', [
            'uuid' => $scrapeJob->uuid,
            'status' => $validated['status'],
            'total_found' => $validated['total_found'] ?? 0,
            'created_count' => $validated['created_count'] ?? 0,
        ]);

        return response()->json(['ok' => true]);
    }
}
