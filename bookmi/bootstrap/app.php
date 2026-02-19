<?php

use App\Exceptions\BookmiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleWithRedis();
        $middleware->alias([
            'admin'             => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'manager'           => \App\Http\Middleware\EnsureUserIsManager::class,
            'paystack-webhook'  => \App\Http\Middleware\ValidatePaystackSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Non authentifié.',
                        'status' => 401,
                        'details' => new \stdClass(),
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (BookmiException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                return response()->json($e->toArray(), $e->getStatusCode());
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_FAILED',
                        'message' => $e->getMessage(),
                        'status' => 422,
                        'details' => [
                            'errors' => $e->errors(),
                        ],
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'La ressource demandée est introuvable.',
                        'status' => 404,
                        'details' => new \stdClass(),
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $e->getMessage() ?: 'Une erreur est survenue.',
                        'status' => $e->getStatusCode(),
                        'details' => new \stdClass(),
                    ],
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
                $statusCode = 500;

                return response()->json([
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => app()->hasDebugModeEnabled()
                            ? $e->getMessage()
                            : 'Une erreur interne est survenue.',
                        'status' => $statusCode,
                        'details' => app()->hasDebugModeEnabled()
                            ? ['exception' => get_class($e), 'trace' => $e->getTraceAsString()]
                            : new \stdClass(),
                    ],
                ], $statusCode);
            }
        });
    })->create();
