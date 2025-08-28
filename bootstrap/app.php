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

    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\TranslateJsonResponse::class);

    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('trips:update-status')->everyMinute();
        $schedule->command('events:update-status')->everyMinute();
        $schedule->command('bookings:delete-unpaid')->everyMinute();
        $schedule->command('notify:before-trip')->dailyAt('00:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
