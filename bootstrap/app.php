<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('admin/*'),
        );

        // Turn bare HTTP error responses into a friendly, on-brand Inertia page
        // instead of Laravel's default. A denied authorisation (403) carries the
        // policy's own message (e.g. "You're not part of this event."). Kept out
        // of local/testing so dev keeps the detailed error page and feature
        // tests keep asserting raw status codes.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (app()->environment(['local', 'testing'])) {
                return $response;
            }

            if ($request->is('api/*') || $request->is('admin/*')) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $status,
                // Only surface the exception message for the "safe" statuses;
                // 5xx keeps a generic line so we never leak internals.
                'message' => in_array($status, [403, 404], true) ? $exception->getMessage() : null,
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
