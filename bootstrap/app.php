<?php

use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => CheckUserStatus::class,
            'mfa' => EnsureTwoFactorAuthenticated::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                if (in_array($response->getStatusCode(), [419, 422])) {
                    return back()->withErrors(['workflow' => $response->getStatusCode() === 419
                        ? 'Sesi formulir berakhir. Muat ulang halaman dan coba lagi.'
                        : $exception->getMessage()]);
                }
                if (in_array($response->getStatusCode(), [403, 404, 429, 500, 503])) {
                    return Inertia::render('Error', ['status' => $response->getStatusCode()])
                        ->toResponse($request)->setStatusCode($response->getStatusCode());
                }
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
