<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,

        ]);

        $middleware->validateCsrfTokens(
            // Exclude payment endpoints from CSRF protection because
            // callbacks are triggered by the external gateway (not by
            // a browser session that holds our CSRF token).
            except: [
                'api/webhooks/bunny',
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
