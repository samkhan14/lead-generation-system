<?php

namespace App\Http\Controllers;

use App\Enums\ScrapeJobStatus;
use App\Http\Requests\StoreScrapeJobRequest;
use App\Jobs\ProcessScrapeJob;
use App\Models\ScrapeJob;
use App\Support\ScraperChannels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScraperController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:scraper.view')->only(['index', 'show', 'statusPoll']);
        $this->middleware('permission:scraper.run')->only(['store']);
    }

    public function index(Request $request): Response
    {

        $jobs = ScrapeJob::query()
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        $channelGroups = ScraperChannels::groupedForUi();

        return Inertia::render('Scraper/Index', [
            'jobs' => $jobs,
            'countries' => config('countries.list'),
            'channels' => ScraperChannels::forUi(),
            'channel_groups' => $channelGroups,
            'default_channel' => config('scraper.default_channel'),
        ]);
    }

    public function store(StoreScrapeJobRequest $request): RedirectResponse
    {
        $job = ScrapeJob::query()->create([
            ...$request->validated(),
            'source_channel' => $request->input('source_channel', config('scraper.default_channel')),
            'max_results' => $request->integer('max_results', 20),
            'created_by' => $request->user()->id,
        ]);

        ProcessScrapeJob::dispatch($job);

        return redirect()
            ->route('scraper.show', $job)
            ->with('success', 'Scrape job queued successfully.');
    }

    public function show(ScrapeJob $scrapeJob): Response
    {

        $scrapeJob->load('creator:id,name');

        $logs = $scrapeJob->logs()
            ->orderBy('created_at')
            ->get(['id', 'level', 'message', 'context', 'created_at']);

        return Inertia::render('Scraper/Show', [
            'job' => [
                'uuid' => $scrapeJob->uuid,
                'source_channel' => $scrapeJob->source_channel,
                'source_label' => config("scraper.channels.{$scrapeJob->source_channel}.label", $scrapeJob->source_channel),
                'keyword' => $scrapeJob->keyword,
                'industry' => $scrapeJob->industry,
                'country' => $scrapeJob->country,
                'city' => $scrapeJob->city,
                'area' => $scrapeJob->area,
                'status' => $scrapeJob->status->value,
                'scraper_used' => $scrapeJob->scraper_used,
                'max_results' => $scrapeJob->max_results,
                'total_found' => $scrapeJob->total_found,
                'created_count' => $scrapeJob->created_count,
                'duplicate_count' => $scrapeJob->duplicate_count,
                'failed_count' => $scrapeJob->failed_count,
                'error_message' => $scrapeJob->error_message,
                'success_ratio' => $scrapeJob->successRatio(),
                'duration_seconds' => $scrapeJob->durationSeconds(),
                'search_label' => $scrapeJob->searchLabel(),
                'started_at' => $scrapeJob->started_at?->toISOString(),
                'completed_at' => $scrapeJob->completed_at?->toISOString(),
                'created_at' => $scrapeJob->created_at->toISOString(),
                'creator' => $scrapeJob->creator?->only('id', 'name'),
            ],
            'logs' => $logs,
        ]);
    }

    public function statusPoll(ScrapeJob $scrapeJob): \Illuminate\Http\JsonResponse
    {

        return response()->json([
            'status' => $scrapeJob->status->value,
            'created_count' => $scrapeJob->created_count,
            'duplicate_count' => $scrapeJob->duplicate_count,
            'failed_count' => $scrapeJob->failed_count,
            'total_found' => $scrapeJob->total_found,
            'scraper_used' => $scrapeJob->scraper_used,
            'is_terminal' => $scrapeJob->status->isTerminal(),
        ]);
    }
}
