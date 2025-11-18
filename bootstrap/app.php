<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Run complete system backup every night at 2:00 AM
        $schedule->command('backup:complete --type=complete --keep-days=7')
            ->dailyAt('02:00')
            ->timezone('Asia/Manila') // Adjust to your timezone
            ->emailOutputOnFailure(env('BACKUP_NOTIFICATION_EMAIL'));

        // Alternative: Run database-only backup at midnight
        // $schedule->command('backup:complete --type=database --keep-days=7')
        //     ->dailyAt('00:00')
        //     ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
