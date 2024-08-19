<?php

use App\Http\Middleware\TelegramAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: '/api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['telegram.auth' => TelegramAuth::class]);
        $middleware->validateCsrfTokens(except: [
            '/webhook/telegram',
            '/payment/return/*',
            '/webhook/payment/success',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
