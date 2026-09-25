<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/shop.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // NOTE: alias() assigns (not merges) — keep all aliases in ONE call.
        $middleware->alias([
            'admin.can' => \App\Http\Middleware\EnsureAdminPermission::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\PanelLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Shop guests go to shop login, not the staff login.
        $exceptions->respond(function ($response, \Throwable $e, $request) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException && $request->is('shop*')) {
                return redirect()->guest(route('shop.login'));
            }

            return $response;
        });
    })->create();
