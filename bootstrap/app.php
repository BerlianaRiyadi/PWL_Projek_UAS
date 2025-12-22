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
    ->withMiddleware(function (Middleware $middleware) {
        // REGISTER CUSTOM MIDDLEWARE
        $middleware->alias([
            'owner' => App\Http\Middleware\OwnerMiddleware::class,
            'kasir' => App\Http\Middleware\KasirMiddleware::class,
            // 'role' => App\Http\Middleware\CheckRole::class, // HAPUS INI JIKA TIDAK DIPAKAI
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();