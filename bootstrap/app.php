<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'accounting.access' => \App\Http\Middleware\CheckAccountingAccess::class,
            'admin.access' => \App\Http\Middleware\CheckAdminAccess::class,
            'workshop.access' => \App\Http\Middleware\CheckWorkshopAccess::class,
            'operator.access' => \App\Http\Middleware\CheckOperatorAccess::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
