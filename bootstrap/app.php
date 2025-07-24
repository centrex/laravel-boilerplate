<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ForceJsonMiddleware;
use Psr\Log\LogLevel;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            ForceJsonMiddleware::class,
        ]);
    })
    ->withEvents(discover: [
        __DIR__ . '/../app/Listeners',
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->level(PDOException::class, LogLevel::CRITICAL);

        if (config('app.env') == 'production') {
            $exceptions->reportable(function (Throwable $e) {
                Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'Line' => $e->getLine(),
                    'code' => $e->getCode(),
                ]);
                auth()->user()->hasRole('developer') ? abort(500, $e->getMessage()) : abort(500, 'Something went wrong!');
            });
        }
    })->create();
