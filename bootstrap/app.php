<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckTherapist;
use App\Http\Middleware\CheckPatient;
use App\Http\Middleware\CheckAdminOrTherapist;
use App\Http\Middleware\DemoReadOnly;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rate limiting global de la API (red de seguridad ante abuso/DoS).
        // El limiter 'api' se define en AppServiceProvider (120 req/min por IP).
        $middleware->api(append: [
            'throttle:api',
        ]);

        $middleware->alias([
            'admin' => CheckAdmin::class,
            'therapist' => CheckTherapist::class,
            'adminOrTherapist' => CheckAdminOrTherapist::class,
            'patient' => CheckPatient::class,
            'demo.readonly' => DemoReadOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
