<?php

use App\Http\Middleware\EnsureLegalConsent;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Server-to-server webhooks: no session, cookies or CSRF, but throttled.
            Route::middleware('throttle:120,1')->group(base_path('routes/payments.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        // The cookie banner choice is written by resources/js/cookie-consent.js,
        // so it must stay readable (unencrypted). Keep in sync with config/legal.php.
        $middleware->encryptCookies(except: ['skular_cookie_consent']);

        $middleware->alias([
            'legal.consent' => EnsureLegalConsent::class,
        ]);

        // Keep the readiness endpoint reachable while the app is in
        // maintenance mode; the liveness route (/up) is excluded by Laravel.
        $middleware->preventRequestsDuringMaintenance(['health']);

        // Behind a load balancer or reverse proxy, Laravel must trust the
        // proxy headers to detect HTTPS, generate secure URLs and set secure
        // cookies. This stays empty by default so a misconfigured deployment
        // cannot trust arbitrary X-Forwarded-* headers.
        // The closure also runs for the console kernel, before the config
        // repository exists, so fall back to the process environment there.
        $trusted_proxies = app()->bound('config')
            ? config('app.trusted_proxies')
            : getenv('TRUSTED_PROXIES');

        if (is_string($trusted_proxies) && $trusted_proxies !== '') {
            $middleware->trustProxies(
                at: $trusted_proxies === '*'
                    ? '*'
                    : array_map('trim', explode(',', $trusted_proxies)),
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
