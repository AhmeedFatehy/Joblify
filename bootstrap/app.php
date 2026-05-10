<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\CheckRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\PrefersJsonResponses;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force JSON responses for API requests
        $middleware->api(prepend: [
            HandleCors::class,
            PrefersJsonResponses::class,
        ]);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Convert all exceptions to JSON for API-only app
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return true;
        });

        // ValidationException -> 422
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        // ModelNotFoundException -> 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        });

        // NotFoundHttpException -> 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found',
            ], 404);
        });

        // MethodNotAllowedHttpException -> 405
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed',
            ], 405);
        });

        // AuthenticationException -> 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        });

        // AuthorizationException -> 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'This action is unauthorized',
            ], 403);
        });

        // AccessDeniedHttpException -> 403
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'This action is unauthorized',
            ], 403);
        });

        // Custom ApiException
        $exceptions->render(function (ApiException $e, Request $request) {
            return $e->render();
        });

        // Fallback for all other exceptions
        $exceptions->render(function (Throwable $e, Request $request) {
            $isDebug = config('app.debug');

            $response = [
                'success' => false,
                'message' => 'Server error',
            ];

            if ($isDebug) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                        return [
                            'file' => $trace['file'] ?? null,
                            'line' => $trace['line'] ?? null,
                            'function' => $trace['function'] ?? null,
                        ];
                    }),
                ];
            }

            return response()->json($response, 500);
        });
    })->create();
