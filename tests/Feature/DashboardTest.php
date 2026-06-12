<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected from dashboard to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can view dashboard with stats', function () {
    $user = User::factory()->create();
    $user->assignRole('agent');

    Lead::query()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats', fn ($stats) => $stats
                ->where('total_leads', 1)
                ->etc()
            )
            ->has('recent_leads', 1)
        );
});

test('home redirects guests to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('home redirects authenticated users to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
