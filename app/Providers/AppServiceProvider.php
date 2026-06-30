<?php

namespace App\Providers;

use App\Domains\AI\Contracts\AiTextGeneratorInterface;
use App\Domains\AI\Events\AiResponseReceived;
use App\Domains\AI\Listeners\LogAiInteraction;
use App\Domains\AI\Services\LaravelAiConnector;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AiTextGeneratorInterface::class,
            LaravelAiConnector::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Event::listen(AiResponseReceived::class, LogAiInteraction::class);

        RateLimiter::for('ingest', function (Request $request) {
            return Limit::perMinute((int) config('ingest.rate_limit', 120))
                ->by($request->ip());
        });

        Vite::prefetch(concurrency: 3);
    }
}
