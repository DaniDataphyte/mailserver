<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        // Allow max 15 concurrent email send jobs at a time
        // (Elastic Email recommendation for smooth delivery)
        RateLimiter::for('newsletter-emails', function () {
            return Limit::perMinute(300); // 300/min = 5/sec sustained
        });
    }
}

