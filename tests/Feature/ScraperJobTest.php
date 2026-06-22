<?php

use App\Enums\ScrapeJobStatus;
use App\Jobs\ProcessScrapeJob;
use App\Models\ScrapeJob;
use App\Models\ScrapeRunLog;
use App\Models\User;
use App\Support\ScraperChannels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createSuperAdmin(): User
{
    $user = User::factory()->create();
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    return $user;
}

function createAgentUser(): User
{
    $permissions = ['leads.view', 'leads.create', 'leads.update', 'leads.delete', 'scraper.view', 'scraper.run'];
    foreach ($permissions as $p) {
        Permission::findOrCreate($p);
    }

    $user = User::factory()->create();
    $role = Role::findOrCreate('agent');
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

it('agent can access scraper index', function () {
    actingAs(createAgentUser())
        ->get(route('scraper.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Scraper/Index')
            ->has('channel_groups.warm')
            ->has('scrape_options.business_type_groups')
            ->where('filters.per_page', 15)
        );
});

it('scraper index respects per_page query', function () {
    ScrapeJob::factory()->count(12)->create();

    actingAs(createAgentUser())
        ->get(route('scraper.index', ['per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.per_page', 10)
            ->has('jobs.data', 10)
            ->where('jobs.per_page', 10)
        );
});

it('reddit is disabled by default in runnable channels', function () {
    expect(ScraperChannels::runnableKeys())->toContain('google_maps')
        ->and(ScraperChannels::runnableKeys())->toContain('yelp')
        ->and(ScraperChannels::runnableKeys())->toContain('openstreetmap')
        ->and(ScraperChannels::runnableKeys())->toContain('bing_places')
        ->and(ScraperChannels::runnableKeys())->not->toContain('reddit');
});

it('process scrape job fails fast when srp service is down', function () {
    Http::fake([
        '*/health' => Http::response(null, 500),
    ]);

    $job = ScrapeJob::factory()->create([
        'status' => ScrapeJobStatus::Pending,
        'source_channel' => 'google_maps',
    ]);

    (new ProcessScrapeJob($job))->handle();

    expect($job->fresh()->status)->toBe(ScrapeJobStatus::Failed);
    expect($job->fresh()->error_message)->toContain('Scraper service');
});

it('marks stale running jobs as failed', function () {
    $job = ScrapeJob::factory()->create([
        'status' => ScrapeJobStatus::Running,
        'started_at' => now()->subHours(2),
    ]);

    $this->artisan('scrape:fail-stale')->assertSuccessful();

    expect($job->fresh()->status)->toBe(ScrapeJobStatus::Failed);
    expect($job->fresh()->error_message)->toContain('timed out');
});

it('unauthenticated user is redirected from scraper index', function () {
    $this->get(route('scraper.index'))->assertRedirect(route('login'));
});

it('agent can create a scrape job', function () {
    Queue::fake();

    actingAs(createAgentUser())
        ->post(route('scraper.store'), [
            'keyword' => 'dentist',
            'country' => 'Pakistan',
            'city' => 'Karachi',
            'max_results' => 10,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('scrape_jobs', [
        'keyword' => 'dentist',
        'country' => 'Pakistan',
        'city' => 'Karachi',
        'status' => ScrapeJobStatus::Pending->value,
    ]);

    Queue::assertPushed(ProcessScrapeJob::class);
});

it('country is required when creating a scrape job', function () {
    actingAs(createAgentUser())
        ->post(route('scraper.store'), [
            'keyword' => 'dentist',
        ])
        ->assertSessionHasErrors('country');
});

it('keyword is required when creating a scrape job', function () {
    actingAs(createAgentUser())
        ->post(route('scraper.store'), [
            'country' => 'Pakistan',
        ])
        ->assertSessionHasErrors('keyword');
});

it('rejects keyword not in the configured list', function () {
    actingAs(createAgentUser())
        ->post(route('scraper.store'), [
            'keyword' => 'not-a-real-business-type-xyz',
            'country' => 'Pakistan',
        ])
        ->assertSessionHasErrors('keyword');
});

it('max_results defaults to 20', function () {
    Queue::fake();

    actingAs(createAgentUser())
        ->post(route('scraper.store'), [
            'keyword' => 'restaurant',
            'country' => 'United Arab Emirates',
        ]);

    $this->assertDatabaseHas('scrape_jobs', [
        'keyword' => 'restaurant',
        'max_results' => 20,
    ]);
});

it('agent can view scrape job show page', function () {
    $user = createAgentUser();
    $job = ScrapeJob::factory()->create(['created_by' => $user->id]);

    actingAs($user)
        ->get(route('scraper.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Scraper/Show'));
});

it('callback start marks job as running', function () {
    $job = ScrapeJob::factory()->create(['status' => ScrapeJobStatus::Pending]);

    postJson(route('api.scrape.start', $job->uuid), [], [
        'Authorization' => 'Bearer '.config('ingest.token'),
    ])->assertOk();

    expect($job->fresh()->status)->toBe(ScrapeJobStatus::Running);
    expect($job->fresh()->started_at)->not->toBeNull();
});

it('callback log creates a run log entry', function () {
    $job = ScrapeJob::factory()->create(['status' => ScrapeJobStatus::Running]);

    postJson(route('api.scrape.log', $job->uuid), [
        'level' => 'success',
        'message' => 'Created: Dental Care Clinic',
        'context' => ['status' => 'created', 'lead_id' => 42],
    ], ['Authorization' => 'Bearer '.config('ingest.token')])->assertOk();

    $this->assertDatabaseHas('scrape_run_logs', [
        'scrape_job_id' => $job->id,
        'level' => 'success',
        'message' => 'Created: Dental Care Clinic',
    ]);
});

it('callback complete marks job as completed with stats', function () {
    $job = ScrapeJob::factory()->create(['status' => ScrapeJobStatus::Running]);

    postJson(route('api.scrape.complete', $job->uuid), [
        'status' => 'completed',
        'scraper_used' => 'playwright',
        'total_found' => 15,
        'created_count' => 10,
        'duplicate_count' => 4,
        'failed_count' => 1,
    ], ['Authorization' => 'Bearer '.config('ingest.token')])->assertOk();

    $job->refresh();

    expect($job->status)->toBe(ScrapeJobStatus::Completed);
    expect($job->total_found)->toBe(15);
    expect($job->created_count)->toBe(10);
    expect($job->scraper_used)->toBe('playwright');
    expect($job->completed_at)->not->toBeNull();
});

it('callback complete marks job as failed', function () {
    $job = ScrapeJob::factory()->create(['status' => ScrapeJobStatus::Running]);

    postJson(route('api.scrape.complete', $job->uuid), [
        'status' => 'failed',
        'error_message' => 'Playwright and Places API both failed.',
    ], ['Authorization' => 'Bearer '.config('ingest.token')])->assertOk();

    expect($job->fresh()->status)->toBe(ScrapeJobStatus::Failed);
    expect($job->fresh()->error_message)->toBe('Playwright and Places API both failed.');
});

it('callbacks reject requests without valid token', function () {
    $job = ScrapeJob::factory()->create();

    postJson(route('api.scrape.start', $job->uuid), [], [
        'Authorization' => 'Bearer wrong-token',
    ])->assertUnauthorized();
});

it('status poll endpoint returns current job state', function () {
    $user = createAgentUser();
    $job = ScrapeJob::factory()->create([
        'status' => ScrapeJobStatus::Running,
        'created_by' => $user->id,
    ]);

    actingAs($user)
        ->getJson(route('scraper.status', $job->uuid))
        ->assertOk()
        ->assertJsonFragment(['status' => 'running', 'is_terminal' => false]);
});

it('scrape job success ratio is calculated correctly', function () {
    $job = ScrapeJob::factory()->create([
        'total_found' => 10,
        'created_count' => 7,
    ]);

    expect($job->successRatio())->toBe(70.0);
});

it('scrape job search label includes all location parts', function () {
    $job = ScrapeJob::factory()->make([
        'keyword' => 'dentist',
        'city' => 'Karachi',
        'area' => 'Clifton',
        'country' => 'Pakistan',
    ]);

    expect($job->searchLabel())->toBe('dentist, Clifton, Karachi, Pakistan');
});
