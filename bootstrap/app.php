<?php

use App\Http\Middleware\BlockBannedIp;
use App\Http\Middleware\ConfigureSeo;
use App\Http\Middleware\EnsureStoreNotInMaintenance;
use Cog\Laravel\Ban\Http\Middleware\ForbidBannedUser;
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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Payment provider webhooks are server-to-server and carry no CSRF token.
        $middleware->validateCsrfTokens(except: [
            'payments/mpesa/callback',
            'payments/stripe/webhook',
        ]);

        // Apply store-wide SEO defaults before controllers/Livewire run.
        $middleware->web(append: [
            BlockBannedIp::class,
            ConfigureSeo::class,
            EnsureStoreNotInMaintenance::class,
            ForbidBannedUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
