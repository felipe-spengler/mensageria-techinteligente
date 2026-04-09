<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // ───────────────────────────────────────────────────────────
        // API Rate Limiters  – protects VPS from queue flooding / DDoS
        // ───────────────────────────────────────────────────────────

        // Per-API-key: 60 send requests per minute.
        // Prevents a single customer from flooding the queue.
        RateLimiter::for('api-send', function (Request $request) {
            $apiKey = $request->attributes->get('api_key');
            $key    = $apiKey ? 'apikey:' . $apiKey->id : 'ip:' . $request->ip();

            return Limit::perMinute(60)->by($key)->response(function () {
                return response()->json([
                    'error'       => 'Too many requests. Please wait before sending more messages.',
                    'retry_after' => 60,
                ], 429);
            });
        });

        // Global IP-level guard: 300 req/min per IP (covers unauthenticated endpoints).
        RateLimiter::for('api-global', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip())->response(function () {
                return response()->json(['error' => 'Rate limit exceeded.'], 429);
            });
        });
    }
}
