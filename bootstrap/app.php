<?php
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ForceCorrectHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Run first so all URL generation uses the correct public domain
        $middleware->prepend(ForceCorrectHost::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
