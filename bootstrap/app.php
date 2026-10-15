<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active' => \App\Http\Middleware\EnsureAccountActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The mobile API always answers in the { success, message, ... } envelope, even when the
        // client forgets the Accept: application/json header.
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());

        $exceptions->render(function (Throwable $e, Request $r) {
            if (! $r->is('api/*')) {
                return null;
            }
            $fail = fn (string $msg, int $code, array $extra = []) => response()->json(array_merge(['success' => false, 'message' => $msg], $extra), $code);

            return match (true) {
                $e instanceof ValidationException => $fail('Validation failed', 422, ['errors' => $e->errors()]),
                $e instanceof AuthenticationException => $fail('Unauthenticated', 401),
                $e instanceof InvalidSignatureException => $fail('Link expired or invalid', 403),
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => $fail('Not found', 404),
                $e instanceof ThrottleRequestsException => $fail('Too many requests. Try again shortly.', 429),
                $e instanceof HttpExceptionInterface => $fail($e->getMessage() ?: 'Request failed', $e->getStatusCode()),
                default => config('app.debug') ? null : $fail('Server error', 500),
            };
        });
    })->create();
