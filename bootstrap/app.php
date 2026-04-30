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
    ->withMiddleware(function (Middleware $middleware): void {
        // Send unauthenticated guests to the right login page based on the URL they tried to access.
        // Anything under /employee goes to the employee login; everything else goes to the admin login.
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('employee*')) {
                return route('employee.login');
            }

            return route('admin.login');
        });

        $middleware->alias([
            'business' => \App\Http\Middleware\EnsureBusinessContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
