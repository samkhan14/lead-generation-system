<?php

use App\Domains\Email\Enums\EmailSendStatus;
use App\Domains\Email\Models\EmailCampaign;
use App\Domains\Email\Models\EmailProvider;
use App\Domains\Email\Models\EmailSend;
use App\Domains\Email\Services\EmailGateway;
use Database\Seeders\EmailProviderSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['queue.default' => 'sync']);
});

test('email provider seeder creates log and smtp providers', function () {
    $this->seed(EmailProviderSeeder::class);

    expect(EmailProvider::query()->where('slug', 'log')->exists())->toBeTrue()
        ->and(EmailProvider::query()->where('slug', 'resend')->exists())->toBeTrue();
});

test('log email provider sends synchronously via gateway', function () {
    $provider = EmailProvider::factory()->create([
        'slug' => 'log',
        'metadata' => ['default_from_email' => 'sender@example.com', 'default_from_name' => 'CRM'],
    ]);

    $send = EmailSend::query()->create([
        'email_provider_id' => $provider->id,
        'to_email' => 'recipient@example.com',
        'subject' => 'Test subject',
        'html_body' => '<p>Hello world</p>',
        'text_body' => 'Hello world',
        'from_email' => 'sender@example.com',
        'status' => EmailSendStatus::Queued,
    ]);

    $result = app(EmailGateway::class)->executeSend($send);

    expect($result->status)->toBe(EmailSendStatus::Sent)
        ->and($result->provider_message_id)->not->toBeNull();
});

test('agent can create email campaign and queue test send', function () {
    Queue::fake();

    $this->seed(EmailProviderSeeder::class);
    $agent = createAgent();
    $provider = EmailProvider::query()->where('slug', 'log')->first();

    $this->actingAs($agent)
        ->post(route('admin.email.campaigns.store'), [
            'name' => 'Summer Promo',
            'subject' => 'Grow your traffic',
            'html_body' => '<p>Hi there</p>',
            'email_provider_id' => $provider->id,
        ])
        ->assertRedirect();

    $campaign = EmailCampaign::query()->where('name', 'Summer Promo')->first();
    expect($campaign)->not->toBeNull();

    $this->actingAs($agent)
        ->post(route('admin.email.campaigns.test', $campaign), [
            'to_email' => 'test@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(EmailSend::query()->where('email_campaign_id', $campaign->id)->where('is_test', true)->exists())->toBeTrue();
});

test('agent can queue single email send', function () {
    Queue::fake();

    $this->seed(EmailProviderSeeder::class);
    $agent = createAgent();

    $this->actingAs($agent)
        ->post(route('admin.email.sends.store'), [
            'to_email' => 'lead@example.com',
            'subject' => 'Direct outreach',
            'html_body' => '<p>One-off email</p>',
        ])
        ->assertRedirect();

    expect(EmailSend::query()->where('to_email', 'lead@example.com')->exists())->toBeTrue();
});

test('agent can view email admin pages', function () {
    $this->seed(EmailProviderSeeder::class);
    $agent = createAgent();

    $this->actingAs($agent)
        ->get(route('admin.email.providers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Email/Providers/Index'));

    $this->actingAs($agent)
        ->get(route('admin.email.campaigns.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Email/Campaigns/Index'));

    $this->actingAs($agent)
        ->get(route('admin.email.sends.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Email/Sends/Compose'));
});
