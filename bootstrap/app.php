<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render/Vercel sit behind a proxy. Without this, $request->ip() is the
        // proxy's IP and every visitor would share one brute-force counter.
        $middleware->trustProxies(at: '*');

        // `auth` and `verified` aliases are built into Laravel 11.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
