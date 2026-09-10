<?php

use App\Http\Middleware\CheckActiveSeason;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\LoggingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(HandleCors::class);

        $middleware->alias([
            'check.active.season' => CheckActiveSeason::class,
            'check_permission' => CheckPermission::class,
            'inventory.log' => LoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (UnauthorizedException $e, $request) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        });
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule){
        $schedule->command('app:complete-ended-seasons')->daily()
        ->appendOutputTo(storage_path('logs/schedule.log'));
        $schedule->command('backup:run')
        ->daily()
        ->appendOutputTo(storage_path('logs/backup.log'));
    })
->create();
