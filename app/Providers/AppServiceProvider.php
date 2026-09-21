<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Coarse per-IP ceiling on POST /sign-in (fine-grained limits live in LoginThrottleService).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
