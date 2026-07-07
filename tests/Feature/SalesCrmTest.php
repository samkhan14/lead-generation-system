<?php

use App\Domains\BusinessKnowledge\Models\Service;
use App\Domains\Crm\Enums\QuoteStatus;
use App\Domains\Crm\Enums\TaskStatus;
use App\Domains\Crm\Models\Deal;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\Quote;
use App\Domains\Crm\Models\Task;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServiceSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('agent can edit lead and update pipeline stage with activity log', function () {
    $agent = createAgent();
    $lead = createLead(['status' => 'new']);

    $this->actingAs($agent)
        ->put(route('leads.update', $lead), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => 'contacted',
        ])
        ->assertRedirect(route('leads.show', $lead));

    $lead->refresh();

    expect($lead->first_name)->toBe('Jane')
        ->and($lead->status)->toBe('contacted')
        ->and(LeadActivity::query()->where('lead_id', $lead->id)->where('type', 'status_change')->exists())->toBeTrue();
});

test('marking lead as lost requires lost reason', function () {
    $agent = createAgent();
    $lead = createLead(['status' => 'qualified']);

    $this->actingAs($agent)
        ->put(route('leads.update', $lead), [
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => 'lost',
        ])
        ->assertSessionHasErrors('lost_reason');
});

test('agent can log manual lead activity', function () {
    $agent = createAgent();
    $lead = createLead();

    $this->actingAs($agent)
        ->post(route('leads.activities.store', $lead), [
            'type' => 'call',
            'subject' => 'Intro call',
            'body' => 'Left voicemail.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(LeadActivity::query()->where('lead_id', $lead->id)->where('type', 'call')->exists())->toBeTrue();
});

test('agent can create and update deal on lead', function () {
    $agent = createAgent();
    $lead = createLead();

    $this->actingAs($agent)
        ->post(route('leads.deals.store', $lead), [
            'title' => 'Website redesign',
            'stage' => 'qualified',
            'value' => 5000,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $deal = Deal::query()->where('lead_id', $lead->id)->first();
    expect($deal)->not->toBeNull()
        ->and($deal->title)->toBe('Website redesign');

    $this->actingAs($agent)
        ->put(route('leads.deals.update', [$lead, $deal]), [
            'stage' => 'won',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($deal->fresh()->stage)->toBe('won')
        ->and($deal->fresh()->won_at)->not->toBeNull();
});

test('agent can create complete and list tasks', function () {
    $agent = createAgent();
    $lead = createLead();

    $this->actingAs($agent)
        ->post(route('leads.tasks.store', $lead), [
            'title' => 'Follow up call',
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'priority' => 'high',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $task = Task::query()->where('lead_id', $lead->id)->first();
    expect($task)->not->toBeNull()
        ->and($task->status)->toBe(TaskStatus::Pending);

    $this->actingAs($agent)
        ->post(route('tasks.complete', $task))
        ->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);

    $this->actingAs($agent)
        ->get(route('tasks.index'))
        ->assertOk();
});

test('agent can generate quote from service catalog', function () {
    $this->seed(ServiceSeeder::class);

    $agent = createAgent();
    $lead = createLead();
    $service = Service::query()->active()->first();

    expect($service)->not->toBeNull();

    $this->actingAs($agent)
        ->post(route('leads.quotes.store', $lead), [
            'service_ids' => [$service->id],
            'notes' => 'Focus on local SEO wins.',
        ])
        ->assertRedirect();

    $quote = Quote::query()->where('lead_id', $lead->id)->first();

    expect($quote)->not->toBeNull()
        ->and($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->html_body)->toContain($service->name);

    $this->actingAs($agent)
        ->get(route('leads.quotes.show', [$lead, $quote]))
        ->assertOk();

    $this->actingAs($agent)
        ->post(route('leads.quotes.send', [$lead, $quote]))
        ->assertRedirect();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);
});

test('pipeline page groups leads by stage', function () {
    $agent = createAgent();
    createLead(['status' => 'new', 'email' => 'new@example.com']);
    createLead(['status' => 'contacted', 'email' => 'contacted@example.com', 'phone' => '+15550000001']);

    $this->actingAs($agent)
        ->get(route('leads.pipeline'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Pipeline')
            ->has('columns.new')
            ->has('columns.contacted'));
});

test('lead index supports pipeline stage filter', function () {
    $agent = createAgent();
    createLead(['status' => 'qualified', 'email' => 'qualified@example.com', 'phone' => '+15550000002']);
    createLead(['status' => 'new', 'email' => 'another@example.com', 'phone' => '+15550000003']);

    $this->actingAs($agent)
        ->get(route('leads.index', ['status' => 'qualified']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Index')
            ->where('filters.status', 'qualified'));
});

test('dashboard includes due tasks for agent', function () {
    $agent = createAgent();
    $lead = createLead(['assigned_to' => $agent->id]);

    Task::query()->create([
        'lead_id' => $lead->id,
        'assigned_to' => $agent->id,
        'created_by' => $agent->id,
        'title' => 'Call back',
        'due_at' => now()->addHours(2),
        'status' => TaskStatus::Pending,
        'priority' => 'medium',
    ]);

    $this->actingAs($agent)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('due_tasks', 1)
            ->where('stats.open_tasks', 1));
});
